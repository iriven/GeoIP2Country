<?php

namespace iriven\bin;

class GeoipNetwork
{
    /**
     * @var string $ipAddress
    **/
    private $ipAddress=null;
    /**
     * Class constructor.
     *
     * @param string $ipAddress
     */
    public function __construct(string $ipAddress=null)
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
        $ipAddress = '';
        $ipKeys =['HTTP_X_COMING_FROM', 'HTTP_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_X_CLUSTER_CLIENT_IP',
                'HTTP_X_FORWARDED', 'HTTP_VIA', 'HTTP_CLIENT_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'];
        foreach ($ipKeys as $key):
            if (array_key_exists($key, $_SERVER) && !empty($_SERVER[$key])):
                $ipAddress = $_SERVER[$key];
                if (($commaPos = strpos($ipAddress, ',')) > 0):
                    $ipAddress = substr($ipAddress, 0, ($commaPos - 1));
                endif;
                break;
            endif;
        endforeach;
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
        } catch (\UnexpectedValueException $th) {
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
                throw new \UnexpectedValueException('Invalid IP given: '.$ipAddress);
            }
        } catch (\UnexpectedValueException $th) {
            trigger_error($th->getMessage(), E_USER_ERROR);
        }
        return $ipAddress;
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
        } catch (\UnexpectedValueException $th) {
            trigger_error($th->getMessage(), E_USER_ERROR);
        }
    }
}
