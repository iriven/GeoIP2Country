<?php
/**
 * Created by PhpStorm.
 * User: sjhc1170
 * Date: 20/04/2017
 * Time: 08:55
 */

namespace Iriven;
use ZipArchive;
use SplFileObject;

/**
 * Class GeoIPCountry
 * @package Iriven\GeoIPCountry
 */
class GeoIPCountry
{
    const DOWNLOAD_LINK = 'http://software77.net/geo-ip/?DL=%s';
    const DOWNLOADED_FILE = 'GeoIP';
    const DS = DIRECTORY_SEPARATOR;
    private $DataLocation = null;
    private $EditModeEnabled = false;
    private $IsoCode = null;
    private $IpPackageID = ['ipv4'=>'1','ipv6'=>'7'];
    private $PackageLocation = null;
    private $PackageName = self::DOWNLOADED_FILE;
    private $UpdateUrl = self::DOWNLOAD_LINK;

    /**
     * GeoIPCountry constructor.
     */
    public function __construct()
    {
        $this->PackageLocation = realpath($this->getStoragePath());
        $this->DataLocation  = realpath($this->getStoragePath(false));
        $this->prepareLookup();
        return $this;
    }

    /**
     * @return $this
     */
    public function Admin()
    {
        $this->EditModeEnabled = true;
        return $this;
    }

    /**
     * @return $this
     */
    private function DownloadPackage()
    {
        if($this->EditModeEnabled)
        {
            set_time_limit(0); //prevent timeout
            try
            {
                foreach ($this->IpPackageID AS $ipVersion=>$packageId)
                {
                    $Archive = $this->PackageLocation.self::DS.$this->PackageName;
                    $Archive .=($ipVersion==='ipv6')?'6.gz':'.gz';
                    if(!file_exists($Archive))
                    {
                        $url = sprintf($this->UpdateUrl,$packageId);
                        $curl=curl_init();
                        $Handler = fopen($Archive,'w+');
                        curl_setopt($curl, CURLOPT_URL, str_replace(' ','%20',$url));
                        curl_setopt($curl, CURLOPT_FILE, $Handler); //auto write to file
                        curl_setopt($curl, CURLOPT_TIMEOUT, 5040);
                        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
                        if(curl_exec($curl) === false)
                            throw new \Exception(curl_error($curl));
                        curl_close($curl);
                        fclose($Handler);
                    }
                }
            }
            catch (\Exception $e)
            {
                trigger_error($e->getMessage());
            }
        }
        return $this;
    }

    /**
     * If IPV6, Returns the IP in it's fullest format.
     * @example
     *          ::1              => 0000:0000:0000:0000:0000:0000:0000:0001
     *          220F::127.0.0.1  => 220F:0000:0000:0000:0000:0000:7F00:0001
     *          2F:A1::1         => 002F:00A1:0000:0000:0000:0000:0000:0001
     * @param $Ip
     * @return mixed|string
     */
    private function ExpandIPAddress($Ip)
    {
        if (strpos($Ip, ':') !== false) // IPv6 address
        {
            $hex = unpack('H*hex', inet_pton($Ip));
            $Ip = substr(preg_replace('/([A-f0-9]{4})/', "$1:", $hex['hex']), 0, -1);
            $Ip = strtoupper($Ip);
        }
        return $Ip;
    }
    /**
     * @param $ip
     * @return null|string
     */
    private function getIPRangeProviderFile($ip)
    {
        try
        {
            if(!preg_match('/[.:]/', $ip)) $ip = $this->long2ip($ip, false);
            if(!filter_var($ip,FILTER_VALIDATE_IP,[FILTER_FLAG_IPV4|FILTER_FLAG_IPV6]))
                throw new \Exception('Invalid IP given');
            $delimiter = (strpos($ip,':')===false)? '.' : ':';
            $DBfile = current(explode($delimiter,$ip)).'.php';
            return $DBfile;
        }
        catch (\Exception $e)
        {
            trigger_error($e->getMessage());
        }
        return null;
    }
    /**
     * @param bool $isArchive
     * @return string
     */
    private function getStoragePath($isArchive=true)
    {
        $tmp = ini_get('upload_tmp_dir')?:sys_get_temp_dir ();
        $isArchive OR $tmp = rtrim(__DIR__, self::DS);
        try{
            if (!is_writeable($tmp))
                throw new \Exception(sprintf('The required destination path is not writable: %s', $tmp));
        }
        catch(\Exception $e)
        {
            trigger_error($e->getMessage(),E_USER_ERROR);
        }
        $tmp .= self::DS.($isArchive? 'GeoIPCountry' : 'GeoIPDatas');
        if(!is_dir($tmp)) mkdir($tmp,'0755', true);
           return $tmp;
    }
    /**
     * Convert both IPV4 and IPv6 address to an integer
     * @param $Ip
     * @return mixed|string
     */
    private function ip2long($Ip)
    {
        $decimal = null;
       $Ip = $this->ExpandIPAddress($Ip);
        try
        {
            switch ($Ip):
                case (strpos($Ip, '.') !== false):
                    if(!filter_var($Ip,FILTER_VALIDATE_IP,[FILTER_FLAG_IPV4]))
                        throw new \Exception('Invalid IPV4 given');
                    $decimal .= ip2long($Ip);
                    break;
                case (strpos($Ip, ':') !== false):
                    if(!filter_var($Ip,FILTER_VALIDATE_IP,[FILTER_FLAG_IPV6]))
                        throw new \Exception('Invalid IPV6 given');
                    $network = inet_pton($Ip);
                    $parts   = unpack('C*', $network);
                    foreach ($parts as &$byte)
                        $decimal.= str_pad(decbin($byte), 8, '0', STR_PAD_LEFT);
                    break;
                default:
                    throw new \Exception($Ip.' is not a valid IP address');
                    break;
            endswitch;
        }
        catch (\Exception $e)
        {
            trigger_error($e->getMessage(),E_USER_ERROR);
        }
        return $decimal;
    }
    /**
     * Convert an IP address from decimal format to presentation format
     *
     * @param $decimal
     * @param bool $compress
     * @return mixed|string
     */
    private function long2ip($decimal,$compress = true)
    {
        $Ip = null;
        if(preg_match('/[.:]/', $decimal))
            return strtoupper($decimal);
        switch ($decimal):
            case (strlen($decimal) <= 32):
                $Ip .= long2ip($decimal);
                break;
            default:
                $pad = 128 - strlen($decimal);
                for ($i = 1; $i <= $pad; $i++)
                    $decimal = '0'.$decimal;
                for ($bits = 0; $bits <= 7; $bits++)
                {
                    $binPart = substr($decimal,($bits*16),16);
                    $Ip .= dechex(bindec($binPart)).':';
                }
                $Ip = inet_ntop(inet_pton(substr($Ip,0,-1)));
                break;
        endswitch;
            $Ip = strtoupper($Ip);
        return $compress? $Ip : $this->ExpandIPAddress($Ip);
    }
    /**
     * @param null $ip
     * @return bool
     */
    public function isReservedIP($ip=null)
    {
        if($ip) $this->resolve($ip);
        return !$this->IsoCode OR strcasecmp($this->IsoCode,'ZZ') == 0 ;
    }
    /**
     * @return $this
     */
    private function prepareLookup()
    {
        $totalRangeFiles = count(glob($this->DataLocation.'/*[0-9]*.php'));
        if($totalRangeFiles < 332)
        {
            $this->Admin()->updateDatabase();
            $this->EditModeEnabled = false;
        }
        return $this;
    }
    /**
     * @param null $ip
     * @return null|string
     */
    public function resolve($ip = null)
    {
        try
        {
            $ip OR $ip = $this->getRemoteIP();
            if(!preg_match('/[.:]/', $ip)) $ip = $this->long2ip($ip);
            $ip = $this->ExpandIPAddress($ip);
            if(!filter_var($ip,FILTER_VALIDATE_IP,[FILTER_FLAG_IPV4|FILTER_FLAG_IPV6]))
                throw new \Exception('Invalid IP given');
            $ipFilename = $this->getIPRangeProviderFile($ip);
            $ipLong = $this->ip2long($ip);
            $ipFilePath = realpath($this->DataLocation.self::DS.$ipFilename);
            if(!file_exists($ipFilePath))
                throw new \Exception('IP Ranges provider file not found');
            $IpRanges = include $ipFilePath;
            foreach($IpRanges as $Range):
                if(!is_array($Range) OR sizeof($Range) !== 3) continue;
                if(preg_match('/^[01]+$/', $ipLong))
                {
                    $Range[0] = $this->ip2long($Range[0]);
                    $Range[1] = $this->ip2long($Range[1]);
                }
                if($Range[1] < $ipLong) continue;
                if(($Range[0]<=$ipLong))
                {
                    $this->IsoCode = $Range[2]?:'ZZ';
                    break;
                }
            endforeach;
        }
        catch (\Exception $e)
        {
            trigger_error($e->getMessage());
        }
        return $this->IsoCode;
    }

    /**
     * Auto Get the current visitor IP Address
     * @return string
     */
    private function getRemoteIP()
    {
        $ip = null;
        $serverIPKeys =['HTTP_X_COMING_FROM', 'HTTP_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_X_CLUSTER_CLIENT_IP',
                        'HTTP_X_FORWARDED', 'HTTP_VIA', 'HTTP_CLIENT_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'];
        foreach ($serverIPKeys AS $IPKey):
            if(array_key_exists($IPKey,$_SERVER))
            {
                if (!strlen($_SERVER[$IPKey])) continue;
                $ip = $_SERVER[$IPKey];
                break;
            }
            endforeach;
        if (($CommaPos = strpos($ip, ',')) > 0)
            $ip = substr($ip, 0, ($CommaPos - 1));
        return $ip?:'0.0.0.0';
    }
    /**
     * @return $this
     */
    public function updateDatabase()
    {
        if($this->EditModeEnabled)
        {
            $this->DownloadPackage()->ExtractArchive();
            $ExtractedFileName = pathinfo($this->PackageName,PATHINFO_FILENAME);
            $ExtractedFiles = glob($this->PackageLocation.self::DS.$ExtractedFileName.'*.csv');
            if($ExtractedFiles)
            {
                set_time_limit(0); //prevent timeout
                foreach ($ExtractedFiles AS $ExtractedFile):
                    $files = [];
                    foreach (new SplFileObject($ExtractedFile) as $line)
                    {
                        if (substr($line, 0, 1) === '#') continue;
                        $line = str_replace('"','',$line);
                        $temp = explode(',', $line);
                        if (count($temp)<4) continue;
                        $filename = null;
                        $ipMin = null;
                        $ipMax = null;
                        $Alpha2 = null;
                        switch ($temp[0]):
                            case (strpos($temp[0], '-') !== false):
                                list($ipMin,$ipMax) = explode('-',$temp[0]);
                                $Alpha2 = $temp[1];
                                $filename = current(explode(':',$this->ExpandIPAddress($ipMin))).'.php';
                                break;
                            default:
                                if (count($temp)<7) continue;
                                $ipMin = (int) $temp[0];
                                $ipMax = (int) $temp[1];
                                $Alpha2 = $temp[4];
                                $filename = current(explode('.',$this->long2ip($ipMin))).'.php';
                                break;
                        endswitch;
                        $dataFile = $this->PackageLocation.self::DS.$filename;
                        $files[] = $filename;
                        $fileContent = null;
                        if(!file_exists($dataFile))
                        {
                            $fileContent .= '<?php'.PHP_EOL;
                            $fileContent .= 'return ['.PHP_EOL;
                        }
                        $fileContent .= '[\''.$ipMin.'\', \''.$ipMax.'\', \''.$Alpha2.'\'],'.PHP_EOL;
                        file_put_contents($dataFile, $fileContent,FILE_APPEND | LOCK_EX);
                    }
                    if($files):
                        foreach ($files as $file)
                        {
                            $source = $this->PackageLocation.self::DS.$file;
                            $destination = $this->DataLocation.self::DS.$file;
                            if(!file_exists($source)) continue;
                            $sourceContent = '];';
                            file_put_contents($source, $sourceContent,FILE_APPEND | LOCK_EX);
                            rename($source,$destination);
                            @chmod($destination,0644);
                        }
                    endif;
                    if(file_exists($ExtractedFile)) @unlink($ExtractedFile);
                endforeach;
            }
            $this->EditModeEnabled = false;
        }
        return $this;
    }

    /**
     * @param null $file
     * @return $this
     */
    private function ExtractArchive($file = null)
    {
        if($this->EditModeEnabled)
        {
            !$file  OR $this->PackageName = pathinfo(realpath($file), PATHINFO_FILENAME);
            try{
                $Packages = array_filter(glob($this->PackageLocation.self::DS.$this->PackageName.'*.{gz,zip}',GLOB_BRACE), 'is_file');
                if($Packages)
                {
                    $bufferSize = 4096;
                    $Package = null;
                    foreach($Packages AS $PackageFile):
                        $PackageExt = pathinfo($PackageFile, PATHINFO_EXTENSION);
                    if(!in_array(strtolower($PackageExt),['zip','gz'],true)) continue;
                        $ExtractedFilename = pathinfo($PackageFile,PATHINFO_FILENAME).'.csv';
                        $ExtractedFile = realpath($this->PackageLocation).self::DS.$ExtractedFilename;
                    switch ($PackageExt):
                        case (strcasecmp($PackageExt,'gz') == 0):
                            $file = gzopen($PackageFile, 'rb');
                            $Handler = fopen($ExtractedFile, 'wb');
                            while (!gzeof($file))
                            {
                                fwrite($Handler, gzread($file, $bufferSize));
                            }
                            fclose($Handler);
                            gzclose($file);
                            break;
                        case (strcasecmp($PackageExt,'zip') == 0):
                            $zip = new ZipArchive;
                            if ($zip->open($PackageFile) !== false)
                            {
                                for($i = 0; $i < $zip->numFiles; $i++)
                                {
                                    $filename = $zip->getNameIndex($i);
                                    if(strcasecmp(pathinfo($filename,PATHINFO_EXTENSION),'csv') == 0)
                                        copy('zip://'.$PackageFile.'#'.$filename, $ExtractedFile);
                                }
                                $zip->close();
                            }
                            break;
                        default:
                            throw new \Exception('The Downloaded package must be a zip or gz file: "'.$PackageExt.'" file given');
                            break;
                        endswitch;
                        if(file_exists($PackageFile)) @unlink($PackageFile);
                    endforeach;
                }
            }
            catch (\Exception $e)
            {
                trigger_error($e->getMessage(),E_USER_ERROR);
            }
        }
        return $this;
    }
}
