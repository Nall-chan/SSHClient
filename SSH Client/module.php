<?php

declare(strict_types=1);

eval('declare(strict_types=1);namespace SSHClient {?>' . file_get_contents(__DIR__ . '/../libs/helper/BufferHelper.php') . '}');
$AutoLoader = new AutoLoaderSSHClientPHPSecLib('Net\SSH2');
$AutoLoader->register();

class AutoLoaderSSHClientPHPSecLib
{
    private $namespace;

    public function __construct($namespace = null)
    {
        $this->namespace = $namespace;
    }

    public function register()
    {
        spl_autoload_register([$this, 'loadClass']);
    }

    public function loadClass($className)
    {
        $LibPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'libs' . DIRECTORY_SEPARATOR . 'phpseclib' . DIRECTORY_SEPARATOR;
        $file = $LibPath . str_replace(['\\', 'phpseclib3'], [DIRECTORY_SEPARATOR, 'phpseclib'], $className) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
}

/**
 * SqueezeboxBattery Klasse für die Stromversorgung einer SqueezeBox als Instanz in IPS.
 * Erweitert IPSModule.
 *
 * @author        Michael Tröger <micha@nall-chan.net>
 * @copyright     2022 Michael Tröger
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 *
 * @version       1.00
 *
 * @example <b>Ohne</b>
 *
 * @property string $LastError
 * @property string $TempHostKey
 */
class SSHClient extends IPSModule
{
    use \SSHClient\BufferHelper;

    protected $ssh;

    public function Create()
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

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'GetHostKey':
            case 'CheckLogin':
            case 'SaveHostKey':
                $this->{$Ident}();
            break;
       }
    }

    private function GetHostKey()
    {
        $this->SendDebug(__FUNCTION__, '', 0);
        $this->ssh = new \phpseclib\Net\SSH2($this->ReadPropertyString('Address'));
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

    private function SaveHostKey()
    {
        if ($this->TempHostKey != '') {
            $this->WriteAttributeString('HostKey', $this->TempHostKey);
            $this->UpdateFormField('CheckHost', 'visible', true);
            $this->UpdateFormField('CheckHost', 'value', true);
            echo $this->Translate('Saved!');
        } else {
            echo $this->Translate('Failed!');
        }
    }

    private function CheckLogin()
    {
        $this->SendDebug(__FUNCTION__, '', 0);
        if ($this->Login()) {
            echo $this->Translate('Login successfully!');
        } else {
            echo $this->Translate('Failed to connect or login!');
        }
        $this->Close();
    }

    public function Execute(string $Data)
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

    public function ExecuteEx(array $DataArray)
    {
        $this->SendDebug(__FUNCTION__, '', 0);
        $ret = $this->Execute(implode("\n", $DataArray));
        $this->Close();
        return $ret;
    }

    public function GetLastError(): string
    {
        $LastError = $this->LastError;
        $this->LastError = '';
        return $LastError;
    }
    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
    }
    public function GetConfigurationForm()
    {
        $Form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        if ($this->ReadAttributeString('HostKey') != '') {
            $Form['elements'][0]['items'][1]['visible'] = true;
        }
        $this->SendDebug('FORM', json_encode($Form), 0);
        $this->SendDebug('FORM', json_last_error_msg(), 0);
        return json_encode($Form);
    }
    private function Login(): bool
    {
        $this->SendDebug(__FUNCTION__, '', 0);
        $this->ssh = new \phpseclib\Net\SSH2($this->ReadPropertyString('Address'));
        $KeyData = $this->ReadPropertyString('KeyFile');
        if ($KeyData) {
            $key = new \phpseclib\Crypt\RSA();
            $pwd = $this->ReadPropertyString('Password');
            if ($pwd) {
                $key->setPassword($pwd);
            }
            $key->loadKey(base64_decode($KeyData));
        } else {
            $key = $this->ReadPropertyString('Password');
        }
        if (!@$this->ssh->login($this->ReadPropertyString('Username'), $key)) {
            return false;
        }
        if ($this->ReadPropertyBoolean('CheckHost')) {
            $HostKey = $this->ReadAttributeString('HostKey');
            if ($HostKey == '') {
                return false;
            }
            if ($HostKey != $this->ssh->getServerPublicHostKey()) {
                return false;
            }
        }
        return true;
    }

    private function Close(): void
    {
        $this->SendDebug(__FUNCTION__, '', 0);
        if ($this->ssh->isConnected()) {
            $this->ssh->disconnect();
        }
    }

    /*private function SetLastError($stdErr)
    {
        $LastError = $this->LastError;
        if ((strlen($LastError) + ($stdErr)) < 64000) {
            $LastError .= $stdErr;
            $this->LastError = $LastError;
        } else {
            $this->LastError = $stdErr;
        }
    }*/
}
