<?php

namespace iriven;

use iriven\bin\GeoipDatabase;
use iriven\bin\GeoipNetwork;
class GeoIP2Country
{
    /**
     * PDO SQLite3 database instance
     *
     * @var GeoipDatabase
    **/
    private $oDBInstance=null;
    /**
     * Network tools class instance
     *
     * @var GeoipNetwork
    **/
    private $oNetwork=null;
    /**
     * Class Constructor
     *
     * @param string $database
     */
    public function __construct(string $database = null)
    {
        $this->oDBInstance = new GeoipDatabase($database);
        $this->oNetwork = new GeoipNetwork();
        return $this;
    }
    /**
     * Retrieve country code from given IP address
     *
     * @param string|null $ipAddress
     * @return string
     */
    public function resolve(string $ipAddress= null): string
    {
        $ipAddress || $ipAddress = $this->oNetwork->getIPAddress();
        if ($this->oNetwork->isIpAddress($ipAddress)):
            $ipVersion = $this->oNetwork->ipVersion($ipAddress);
            $start = $this->oNetwork->ip2Integer($ipAddress);
            return $this->oDBInstance->fetch($start, $ipVersion);
        endif;
        return 'ZZ';
    }
    /**
     * @param mixed|null $ipAddress
     * @return bool
     */
    public function isReservedAddress($ipAddress=null): bool
    {
        $ipAddress || $ipAddress = $this->oNetwork->getIPAddress();
        $countryCode = $this->resolve($ipAddress);
        return !$countryCode || strcasecmp($countryCode, 'ZZ') == 0 ;
    }
}
