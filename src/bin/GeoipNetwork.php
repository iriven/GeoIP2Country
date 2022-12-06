<?php

namespace geolocation\bin;

use \Throwable;

class GeoipNetwork
{

    /**
     * @var string $ipAddress
    **/
    private $ipAddress=null;

    public function __construct($ipAddress=null)
    {
        $ipAddress || $ipAddress = $this->getIPAddress();
        $this->validateAddress($ipAddress);
        $this->ipAddress = $ipAddress;
    }

    /**
     * Auto Get the current visitor IP Address
     * @return string
     */
    public function getIPAddress()
    {
        $ipAddress = null;
        $serverIPKeys =['HTTP_X_COMING_FROM', 'HTTP_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_X_CLUSTER_CLIENT_IP',
                        'HTTP_X_FORWARDED', 'HTTP_VIA', 'HTTP_CLIENT_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'];
        foreach ($serverIPKeys as $IPKey):
            if (array_key_exists($IPKey, $_SERVER)) {
                if (!strlen($_SERVER[$IPKey])) { continue; }
                $ipAddress = $_SERVER[$IPKey];
                break;
            }
        endforeach;
        if (!is_null($ipAddress) && ($commaPos = strpos($ipAddress, ',')) > 0)
        {
            $ipAddress = substr($ipAddress, 0, ($commaPos - 1));
        }
        return $ipAddress?:'0.0.0.0';
    }

    /**
     * If IPV6, Returns the IP in it's fullest format.
     * @example
     *          ::1              => 0000:0000:0000:0000:0000:0000:0000:0001
     *          220F::127.0.0.1  => 220F:0000:0000:0000:0000:0000:7F00:0001
     *          2F:A1::1         => 002F:00A1:0000:0000:0000:0000:0000:0001
     * @param $ipAddress
     * @return mixed|string
     */
    public function expandAddress($ipAddress)
    {
        $ipAddress || $ipAddress = $this->ipAddress;
        try
        {
            $this->validateAddress($ipAddress);
            if (strpos($ipAddress, ':') !== false) // IPv6 address
            {
                $hex = unpack('H*hex', inet_pton($ipAddress));
                $ipAddress = substr(preg_replace('/([A-f0-9]{4})/', "$1:", $hex['hex']), 0, -1);
                $ipAddress = strtoupper($ipAddress);
            }
        } catch (\Throwable $th) {
            trigger_error($th->getMessage(), E_USER_ERROR);
        }
        return $ipAddress;
    }

    /**
     * Convert both IPV4 and IPv6 address to int|bigint
     *
     * @param string $ipAddress
     * @return int
     */
    public function ip2Integer(string $ipAddress)
    {
        $ipAddress = $this->expandAddress($ipAddress);
        if (strpos($ipAddress, ':') !== false):
            $bin = inet_pton($ipAddress) ;
            $ints = unpack('J2', $bin) ;
            return $ints[1] ;
        endif;
        return ip2long($ipAddress);
    }
    /**
     * Convert both IPV4 and IPv6 address to a long|binary number
     * @param $ipAddress
     * @return mixed|string
     */
    public function ip2long (string $ipAddress)
    {
        $ipAddress || $ipAddress = $this->getIPAddress();
        $decimal = null;
        try
        {
            $ipAddress = $this->expandAddress($ipAddress);
            switch ($ipAddress):
                case (strpos($ipAddress, '.') !== false):
                    $decimal .= ip2long($ipAddress);
                    break;
                case (strpos($ipAddress, ':') !== false):
                    $network = inet_pton($ipAddress);
                    $parts   = unpack('C*', $network);
                    foreach ($parts as &$byte):
                        $decimal.= str_pad(decbin($byte), 8, '0', STR_PAD_LEFT);
                    endforeach;
                    $decimal = ltrim($decimal, '0');
                    break;
                default:
                    throw new Throwable($ipAddress.' is not a valid IP address');
                    break;
            endswitch;
        } catch (Throwable $th) {
            trigger_error($th->getMessage(), E_USER_ERROR);
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
    public function long2ip($decimal, $compress = true)
    {
        $ipAddress = null;
        if (preg_match('/[.:]/', $decimal))
        {return strtoupper($decimal);}
        switch ($decimal):
            case (strlen($decimal) <= 32):
                $ipAddress .= long2ip($decimal);
                break;
            default:
                $pad = 128 - strlen($decimal);
                for ($i = 1; $i <= $pad; $i++)
                { $decimal = '0'.$decimal; }
                for ($bits = 0; $bits <= 7; $bits++)
                {
                    $binPart = substr($decimal,($bits*16),16);
                    $ipAddress .= dechex(bindec($binPart)).':';
                }
                $ipAddress = inet_ntop(inet_pton(substr($ipAddress,0,-1)));
                break;
        endswitch;
            $ipAddress = strtoupper($ipAddress);
        return $compress? $ipAddress : $this->expandAddress($ipAddress);
    }

    /**
     * Check IP address validity
     *
     * @param string $ipAddress
     * @return string
     */
    public function validateAddress(string $ipAddress)
    {
        try
        {
            if (!filter_var($ipAddress, FILTER_VALIDATE_IP, [FILTER_FLAG_IPV4|FILTER_FLAG_IPV6])) {
                throw new Throwable('Invalid IP given');
            }
        } catch (Throwable $th) {
            trigger_error($th->getMessage(), E_USER_ERROR);
        }
        return $ipAddress;
    }

    /**
     * @param $ipAddress
     * @return null|string
     */
    public function getPrefix($ipAddress)
    {
        try
        {
            if(!preg_match('/[.:]/', $ipAddress)) {$ipAddress = $this->long2ip($ipAddress, false);}
            $this->validateAddress($ipAddress);
            $delimiter = (strpos($ipAddress,':')===false)? '.' : ':';
            return current(explode($delimiter, $ipAddress));
        } catch (\Throwable $th) {
            trigger_error($th->getMessage(), E_USER_ERROR);
        }
        return null;
    }

    /**
     * Get IP version of given address
     *
     * @param string $ipAddress
     * @return integer
     */
    public function ipVersion(string $ipAddress)
    {
        $ipAddress || $ipAddress = $this->getIPAddress();
        try
        {
            $ipAddress = $this->expandAddress($ipAddress);
            if (strpos($ipAddress, ':') !== false) {return 6;}
            return 4;
        } catch (Throwable $th) {
            trigger_error($th->getMessage(), E_USER_ERROR);
        }
    }


}
