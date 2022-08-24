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
        $file = $LibPath . str_replace(['\\','phpseclib3'], [DIRECTORY_SEPARATOR,'phpseclib'], $className) . '.php';
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

    protected \phpseclib\Net\SSH2 $ssh;

    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->RegisterPropertyBoolean('Active',false);
        $this->RegisterPropertyBoolean('CheckHost',false);
        $this->RegisterPropertyString('Address', '');
        $this->RegisterPropertyString('Username', '');
        $this->RegisterPropertyString('Password', '');
        $this->RegisterPropertyString('KeyFile', '');
        $this->RegisterAttributeString('HostKey','');
        $this->LastError='';
    }

    private function Login(): bool
    {
        $this->SendDebug(__FUNCTION__,'',0);
        $this->ssh = new \phpseclib\Net\SSH2($this->ReadPropertyString('Address'));
        $KeyData = $this->ReadPropertyString('KeyFile');
        if ($KeyData)
        {
            $key = new \phpseclib\Crypt\RSA();
            $pwd = $this->ReadPropertyString('Password');
            if ($pwd)
            {
                $key->setPassword($pwd);
            }
            $key->loadKey(base64_decode($KeyData));
        } else {
            $key = $this->ReadPropertyString('Password');
        }
        if (!$this->ssh->login($this->ReadPropertyString('Username'), $key))
        {
            return false;
        }
        if ($this->ReadPropertyString('CheckHost')){
            $HostKey = $this->ReadAttributeString('HostKey');
            if ($HostKey==''){
                return false;
            }
            if ($HostKey != $this->ssh->getServerPublicHostKey()){
                return false;
            }
        }
        return true;
    }

    private function Close(): void
    {
        $this->SendDebug(__FUNCTION__,'',0);
        if ($this->ssh->isConnected()) {
            $this->ssh->disconnect();
        }
    }

    public function GetHostKey(): bool
        {
        $this->SendDebug(__FUNCTION__,'',0);
        $this->ssh = new \phpseclib\Net\SSH2($this->ReadPropertyString('Address'));
        if (!$this->ssh->login($this->ReadPropertyString('Username'), $this->ReadPropertyString('Password'))){
            return false;
        }
        $HostKey = $this->ssh->getServerPublicHostKey();
        if ($HostKey === false){
            return false;
        }
        $this->TempHostKey= $HostKey;
        $this->UpdateFormField();
        $this->Close();
        return true;
    }
    public function SaveHostKey(): bool
    {
if ($this->TempHostKey != ''){
    $this->WriteAttributeString('HostKey',$this->TempHostKey);
    return true;
}
return false;
    }
    public function CheckLogin(): bool
    {
        $this->SendDebug(__FUNCTION__,'',0);
        $ret =  $this->Login();
        $this->Close();
        return $ret;
    }

    public function Execute(string $Data): string
    {
        $this->SendDebug(__FUNCTION__,'',0);
        if (!$this->Login()) {
                return false;
            }
        $this->ssh->enableQuietMode();
        $ret = $this->ssh->exec($Data);
        $this->LastError = $this->ssh->getStdError();
        $this->Close();
        return $ret;
    }

    public function ExecuteEx(array $DataArray): string
    {
        $this->SendDebug(__FUNCTION__,'',0);
        $ret= $this->Execute(implode("\n",$DataArray));
        $this->Close();
        return $ret;
    }
public function GetLastError():string{
    $LastError = $this->LastError;
    $this->LastError = '';;
    return $LastError;
}

private function SetLastError($stdErr):void
{
    $LastError = $this->LastError;
    if ((strlen($LastError) + ($stdErr)) < 64000){
        $LastError.=$stdErr;
        $this->LastError = $LastError;
    } else {
        $this->LastError = $stdErr;
    }
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
}
