<?php

declare(strict_types=1);

eval('declare(strict_types=1);namespace SSHClient {?>' . file_get_contents(__DIR__ . '/../libs/helper/BufferHelper.php') . '}');
$AutoLoader = new AutoLoaderSSHClientPHPSecLib('Net\SSH2');
$AutoLoader->register();

/**
 * AutoLoaderSSHClientPHPSecLib
 */
class AutoLoaderSSHClientPHPSecLib
{
    private $namespace;

    public function __construct($namespace = null)
    {
        $this->namespace = $namespace;
    }

    public function register(): void
    {
        spl_autoload_register([$this, 'loadClass']);
    }

    public function loadClass($className): void
    {
        $LibPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'libs' . DIRECTORY_SEPARATOR . 'phpseclib' . DIRECTORY_SEPARATOR;
        $file = $LibPath . str_replace(['\\', 'phpseclib3'], [DIRECTORY_SEPARATOR, 'phpseclib'], $className) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
}

/**
 * SSHClient Klasse für eine SSH Verbindung zu einem Endgerät.
 * Erweitert IPSModule.
 *
 * @author        Michael Tröger <micha@nall-chan.net>
 * @copyright     2022 Michael Tröger
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 *
 * @version       1.10
 *
 * @example <b>Ohne</b>
 *
 * @property string $LastError
 * @property string $TempHostKey
 */
class SSHClient extends IPSModuleStrict
{
    use \SSHClient\BufferHelper;

    protected $ssh;

    /**
     * Create
     *
     * @return void
     */
    public function Create(): void
    {
        //Never delete this line!
        parent::Create();
        $this->RegisterPropertyBoolean('CheckHost', false);
        $this->RegisterPropertyString('Address', '');
        $this->RegisterPropertyString('Username', '');
        $this->RegisterPropertyString('Password', '');
        $this->RegisterPropertyString('KeyFile', '');
        $this->RegisterAttributeString('HostKey', '');
        $this->LastError = '';
    }

    /**
     * ApplyChanges
     *
     * @return void
     */
    public function ApplyChanges(): void
    {
        //Never delete this line!
        parent::ApplyChanges();
        if (!$this->ReadPropertyBoolean('CheckHost')) {
            $this->WriteAttributeString('HostKey', '');
        }
    }

    /**
     * GetConfigurationForm
     *
     * @return string
     */
    public function GetConfigurationForm(): string
    {
        $Form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        if ($this->ReadAttributeString('HostKey') != '' || $this->ReadPropertyBoolean('CheckHost')) {
            $Form['elements'][0]['items'][1]['visible'] = true;
        }
        $this->SendDebug('FORM', json_encode($Form), 0);
        $this->SendDebug('FORM', json_last_error_msg(), 0);
        return json_encode($Form);
    }

    /**
     * RequestAction
     *
     * @param  string $Ident
     * @param  mixed $Value
     * @return void
     */
    public function RequestAction(string $Ident, mixed $Value): void
    {
        if ($Ident == 'LoadHostKey') {
            $this->GetHostKey();
        }
    }

    /**
     * CheckLogin
     *
     * @return void
     */
    public function CheckLogin(): void
    {
        $this->SendDebug(__FUNCTION__, '', 0);
        if ($this->Login()) {
            echo $this->Translate('Login successfully!');
        } else {
            echo $this->Translate('Failed to connect or login!');
        }
        $this->Close();
    }

    /**
     * Execute
     *
     * @param  string $Data
     * @return false|string
     */
    public function Execute(string $Data): false|string
    {
        $this->SendDebug(__FUNCTION__, '', 0);
        if (!$this->Login()) {
            return false;
        }
        $this->ssh->enableQuietMode();
        $ret = $this->ssh->exec($Data);
        $this->LastError = $this->ssh->getStdError();
        $this->Close();
        return $ret;
    }

    /**
     * ExecuteEx
     *
     * @param  array $DataArray
     * @return false|string
     */
    public function ExecuteEx(array $DataArray): false|string
    {
        $this->SendDebug(__FUNCTION__, '', 0);
        $ret = $this->Execute(implode("\n", $DataArray));
        $this->Close();
        return $ret;
    }

    /**
     * GetLastError
     *
     * @return string
     */
    public function GetLastError(): string
    {
        $LastError = $this->LastError;
        $this->LastError = '';
        return $LastError;
    }

    /**
     * SaveHostKey
     *
     * @return void
     */
    public function UISaveHostKey(): void
    {
        if ($this->TempHostKey != '') {
            $this->WriteAttributeString('HostKey', $this->TempHostKey);
            $this->UpdateFormField('CheckHost', 'visible', true);
            $this->UpdateFormField('CheckHost', 'value', true);
            echo $this->Translate('MESSAGE:Saved!');
        } else {
            echo $this->Translate('Failed!');
        }
    }

    /**
     * GetHostKey
     *
     * @return void
     */
    private function GetHostKey(): void
    {
        $this->SendDebug(__FUNCTION__, '', 0);
        $this->ssh = new \phpseclib3\Net\SSH2($this->ReadPropertyString('Address'));
        if (!@$this->ssh->login($this->ReadPropertyString('Username'), $this->ReadPropertyString('Password'))) {
            echo $this->Translate('Failed to connect or login!');
            return;
        }
        $HostKey = $this->ssh->getServerPublicHostKey();
        if ($HostKey === false) {
            echo $this->Translate('Failed to load key from host!');
            @$this->Close();
            return;
        }
        $this->TempHostKey = $HostKey;
        $this->UpdateFormField('PopupKey', 'visible', true);
        $this->Close();
    }

    /**
     * Login
     *
     * @return bool
     */
    private function Login(): bool
    {
        $this->SendDebug(__FUNCTION__, '', 0);
        $this->ssh = new \phpseclib3\Net\SSH2($this->ReadPropertyString('Address'));
        $KeyData = $this->ReadPropertyString('KeyFile');
        if ($KeyData) {
            $pwd = $this->ReadPropertyString('Password');
            if (!$pwd) {
                $pwd = false;
            }
            $key = \phpseclib3\Crypt\PublicKeyLoader::load(base64_decode($KeyData), $pwd);
        } else {
            $key = $this->ReadPropertyString('Password');
        }
        if (!$this->ssh->login($this->ReadPropertyString('Username'), $key)) {
            return false;
        }
        if ($this->ReadPropertyBoolean('CheckHost')) {
            $HostKey = $this->ReadAttributeString('HostKey');
            if ($HostKey == '') {
                return false;
            }
            if ($HostKey = !$this->ssh->getServerPublicHostKey()) {
                return false;
            }
        }
        return true;
    }

    /**
     * Close
     *
     * @return void
     */
    private function Close(): void
    {
        $this->SendDebug(__FUNCTION__, '', 0);
        if ($this->ssh->isConnected()) {
            $this->ssh->disconnect();
        }
    }
}
