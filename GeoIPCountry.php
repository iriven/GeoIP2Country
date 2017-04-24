<?php
/**
 * Created by PhpStorm.
 * User: Iriven France
 * Date: 20/04/2017
 * Time: 08:55
 */

namespace Iriven;
use ZipArchive;
use SplFileObject;


class GeoIPCountry
{
    const DOWNLOAD_LINK = 'http://software77.net/geo-ip/?DL=2';
    const DOWNLOADED_FILE = 'GeoIP.zip';
    const DS = DIRECTORY_SEPARATOR;
    private $DataLocation = null;
    private $EditModeEnabled = false;
    private $IsoCode = null;
    private $PackageLocation = null;
    private $PackageName = self::DOWNLOADED_FILE;
    private $UpdateUrl = self::DOWNLOAD_LINK;

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
            $Archive = $this->PackageLocation.self::DS.$this->PackageName;
            try
            {
                $curl=curl_init();
                $Handler = fopen($Archive,'w+');
                curl_setopt($curl, CURLOPT_URL, str_replace(' ','%20',$this->UpdateUrl));
                curl_setopt($curl, CURLOPT_FILE, $Handler); //auto write to file
                curl_setopt($curl, CURLOPT_TIMEOUT, 5040);
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
                if(curl_exec($curl) === false)
                    throw new \Exception(curl_error($curl));
                curl_close($curl);
                fclose($Handler);
            }
            catch (\Exception $e)
            {
                trigger_error($e->getMessage());
            }
        }
        return $this;
    }

    /**
     * @return string
     */
    private function getExtractedFile()
    {
        $filename = pathinfo($this->PackageName,PATHINFO_FILENAME)?:'GeoIP';
        $filename .= '.csv';
        return $this->PackageLocation.self::DS.$filename;
    }

    /**
     * @param $ip
     * @return null|string
     */
    private function getTable($ip)
    {
        try
        {
            if(strpos($ip,'.')===false) $ip = long2ip($ip);
            if(!filter_var($ip,FILTER_VALIDATE_IP,[FILTER_FLAG_IPV4]))
                throw new \Exception('Invalid IP given');
            $DBfile = current(explode('.',$ip)).'.php';
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
        $isArchive OR $tmp = rtrim(__DIR__, self::DS).self::DS.'GeoIPDatas';
        try{
            if (!is_writeable($tmp))
                throw new \Exception(sprintf('Download directory is not writable: %s', $tmp));
        }
        catch(\Exception $e)
        {
            trigger_error($e->getMessage(),E_USER_ERROR);
        }
        $isArchive AND $tmp .= self::DS.'GeoIPCountry';
        if(!is_dir($tmp)) mkdir($tmp,'0755', true);
           return $tmp;
    }

    /**
     * @param null $ip
     * @return bool
     */
    public function isReservedIP($ip=null)
    {
        if($ip) $this->resolve($ip);
        return is_null($this->IsoCode) OR strcasecmp($this->IsoCode,'ZZ') == 0 ;
    }
    /**
     * @return $this
     */
    private function prepareLookup()
    {
        if(count(glob($this->DataLocation.'/[0-9]*.php'))!==256)
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
            if(strpos($ip,'.')===false) $ip = long2ip($ip);
            if(!filter_var($ip,FILTER_VALIDATE_IP,[FILTER_FLAG_IPV4]))
                throw new \Exception('Invalid IP given');
            $ipFilename = $this->getTable($ip);
            $ipLong = ip2long($ip);
            $DBFilePath = realpath($this->DataLocation.self::DS.$ipFilename);
            if(!file_exists($DBFilePath))
                throw new \Exception('IP Database file not found');
            $IpRanges = include $DBFilePath;
            foreach($IpRanges as $Range):
                if(!is_array($Range) OR sizeof($Range) !== 3) continue;
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

    private function getRemoteIP(){
        $ip = null;
        if (!empty($_SERVER['HTTP_CLIENT_IP']))
             $ip = $_SERVER['HTTP_CLIENT_IP'];
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else
            $ip = $_SERVER['REMOTE_ADDR'];
        return $ip;
    }
    
    public function updateDatabase()
    {
        if($this->EditModeEnabled)
        {
            $this->DownloadPackage()->unzip();
            $ExtractedFile = $this->getExtractedFile();
            if(file_exists($ExtractedFile))
            {
                set_time_limit(0); //prevent timeout
                $files = [];
                foreach (new SplFileObject($ExtractedFile) as $line)
                {
                    if (substr($line, 0, 1) === '#') continue;
                    $line = str_replace('"','',$line);
                    $temp = explode(',', $line);
                    if (count($temp)<7) continue;
                    $ipMin = (int) $temp[0];
                    $ipMax = (int) $temp[1];
                    $Alpha2 = $temp[4];
                    $filename = current(explode('.',long2ip($ipMin))).'.php';
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
                if($files)
                {
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
                }
            }
            if(file_exists($ExtractedFile)) @unlink($ExtractedFile);
        }

        return $this;
    }

    /**
     * @param null $file
     * @return $this
     */
    private function unzip($file = null)
    {
        if($this->EditModeEnabled)
        {
            !$file  OR $this->PackageName = pathinfo(realpath($file), PATHINFO_BASENAME);
            try{
                $Archive = realpath($this->PackageLocation.self::DS.$this->PackageName);
                if(file_exists($Archive))
                {
                    $ArchiveExt = pathinfo($Archive, PATHINFO_EXTENSION);
                    if(strcasecmp($ArchiveExt,'zip') == 0)
                        throw new \Exception('The Downloaded package must be a zip file: "'.$ArchiveExt.'" file given');
                    set_time_limit(0);
                    $zip = new ZipArchive;
                    if ($zip->open($Archive) !== false)
                    {
                        for($i = 0; $i < $zip->numFiles; $i++)
                        {
                            $filename = $zip->getNameIndex($i);
                            $fileExt = pathinfo($filename,PATHINFO_EXTENSION);
                            if(strcasecmp($fileExt,'csv') == 0)
                                copy('zip://'.$Archive.'#'.$filename, $this->getExtractedFile());
                        }
                        $zip->close();
                    }
                    if(file_exists($Archive)) @unlink($Archive);
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
