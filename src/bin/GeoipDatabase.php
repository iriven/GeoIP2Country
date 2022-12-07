<?php

namespace geolocation\bin;

class GeoipDatabase
{
    const DS = DIRECTORY_SEPARATOR;
    /**
     * Database instance
     *
     * @var \PDO
     */
    private $oPDOInstance;
    /**
     * PDO transaction Counter
    *
    * @var integer
    */
    private $transactionCounter = 0;
    /**
     * Class Constructor
     *
     * @param string $database
     */
    public function __construct(string $database = null)
    {
        try
        {
            if (!extension_loaded('pdo_sqlite')) {
                throw new \Throwable(
                    'The PHP PDO_SQLite extension is required. Please enable it before running this program !'
                );
            }
            $aOptions = [
                    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES   => false,
                ];
            $this->oPDOInstance = new \PDO($this->genDsn($database), null, null, $aOptions);
            $this->initialize();
        } catch (\Throwable $th) {
            trigger_error($th->getMessage(), E_USER_ERROR);
        }
        return $this->oPDOInstance;
    }
    /**
     * Create Database tables structure.
     *
     * @return GeoipDatabase
     */
    private function initialize()
    {
        $aCommands = [
            'CREATE TABLE IF NOT EXISTS `ipv4Range`(
                    `start` INT UNSIGNED ,
                    `end` INT UNSIGNED ,
                    `country` VARCHAR(2)
                )',
            'CREATE TABLE IF NOT EXISTS `ipv6Range`(
                    `start` BIGINT UNSIGNED ,
                    `end` BIGINT UNSIGNED ,
                    `country` VARCHAR(2)
                )',
            'CREATE UNIQUE INDEX IF NOT EXISTS idx_ipv4Range ON ipv4Range(start, end)',
            'CREATE UNIQUE INDEX IF NOT EXISTS idx_ipv6Range ON ipv6Range(start, end)'
            ];
        foreach ($aCommands as $command)
        {
            $this->oPDOInstance->query($command);
        }
        return $this;
    }
    /**
     * Generate PDO SQLite3 DSN
     *
     * @param string|null $database
     * @return string
     */
    private function genDsn(string $database = null)
    {
        $database || $database='Geoip.db.sqlite';
        try {
            $destination = rtrim(dirname(__DIR__), self::DS);
            if (!is_writeable($destination))
            {
                throw new \Throwable('The required destination path is not writable: '.$destination);
            }
            $info = new \SplFileInfo($database);
            $dbName= $info->getFilename();
            $dbSuffix='.sqlite';
            if (substr_compare(strtolower($dbName), $dbSuffix, -strlen($dbSuffix)) !== 0) { $dbName .= $dbSuffix ; }
        } catch (\Throwable $th) {
            trigger_error($th->getMessage(), E_USER_ERROR);
        }
        $destination .= self::DS.'data';
        if (!is_dir($destination)) { mkdir($destination, '0755', true); }
        return 'sqlite:'.realpath($destination).self::DS.$dbName ;
    }
    /**
     * Get the table list in the database
     *
     * @return array
     */
    public function showTables()
    {
        try
        {
            $command = 'SELECT `name` FROM `sqlite_master` WHERE `type` = \'table\' ORDER BY name';
            $statement = $this->oPDOInstance->query($command);
            $tables = [];
            while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
                $tables[] = $row['name'];
            }
            return $tables;
        } catch (\PDOException $th) {
            trigger_error($th->getMessage(), E_USER_ERROR);
        }
    }
    /**
     * Retrieve Column(s) value from given table
     *
     * @param string $sTable
     * @param array $columns
     * @return array
     */
    public function fetchAll(string $sTable, array $columns = [])
    {
        !empty($columns) || $columns = '*';
        if (is_array($columns)) { $columns = implode('`, `', $columns); }
        try
        {
            $sCommand = 'SELECT `%s` from  `%s`';
            $statement = $this->oPDOInstance->prepare(sprintf($sCommand, $columns, $sTable));
            $statement->execute();
            return $statement->fetchAll();
        } catch (\PDOException $th) {
            trigger_error($th->getMessage(), E_USER_ERROR);
        }
    }
    /**
     * Return Country code from given IP address (converted to integer)
     *
     * @param integer $start
     * @param integer $ipVersion  (ip version)
     * @return string
     */
    public function fetch(int $start, int $ipVersion)
    {
        try
        {
            $sCommand  = 'SELECT `start`, `end`, `country` ';
            $sCommand .= 'FROM `ipv%dRange` ';
            $sCommand .= 'WHERE `start` <= :start ';
            $sCommand .= 'ORDER BY start DESC LIMIT 1';
            $statement = $this->oPDOInstance->prepare(sprintf($sCommand, $ipVersion)) ;
            $statement->execute([':start' => $start ]) ;
            $row = $statement->fetch(\PDO::FETCH_OBJ) ;
            if (is_bool($row) && $row === false)
            {
                $row = new \stdClass();
                $row->end = 0 ;
            }
            if ($row->end < $start || !$row->country) { $row->country = 'ZZ' ; }
            return $row->country ;
        } catch (\PDOException $th) {
            trigger_error($th->getMessage(), E_USER_ERROR);
        }
    }
    /**
     * Empty a given list of database tables
     *
     * @param array $tablesList
     * @return void
     */
    public function flush(array $tablesList=[])
    {
        !empty($tablesList) || $tablesList = $this->showTables();
        is_array($tablesList) || $tablesList = [$tablesList];
        try
        {
            if (!empty($tablesList)):
                $sCommand = 'DELETE FROM `%s`';
                foreach ($tablesList as $sTable) {
                    $this->oPDOInstance->query(sprintf($sCommand, $sTable));
                }
                $this->oPDOInstance->query('VACUUM');
            endif;
        } catch (\PDOException $th) {
            trigger_error('Statement failed: ' . $th->getMessage(), E_USER_ERROR);
        }
    }
    /**
     * Insert data into database
     *
     * @param integer $start
     * @param integer $end
     * @param integer $ipVersion
     * @param string $country
     * @return void
     */
    public function insert(int $start, int $end, int $ipVersion, string $country)
    {
        try
        {
            $sQuery ='INSERT INTO `ipv%dRange` (`start`, `end`, `country`) values (:start, :end, :country)';
            $command = sprintf($sQuery, $ipVersion);
            $statement = $this->oPDOInstance->prepare($command);
            $statement->execute([
                ':start'   => $start,
                ':end'     => $end,
                ':country' => $country
            ]) ;
        } catch (\PDOException $th) {
            trigger_error('Statement failed: ' . $th->getMessage(), E_USER_ERROR);
        }
    }
    /**
     * Begin PDO transaction, turning off autocommit
     *
     * @return bool
     */
    public function beginTransaction()
    {
        if (!$this->transactionCounter++) {return $this->oPDOInstance->beginTransaction();}
        return $this->transactionCounter >= 0;
    }
    /**
     * Commit PDO transaction changes
     *
     * @return bool
     */
    public function commit()
    {
        if (!--$this->transactionCounter) {return $this->oPDOInstance->commit();}
        return $this->transactionCounter >= 0;
    }
    /**
     * Rollback PDO transaction, Recognize mistake and roll back changes
     *
     * @return bool
     */
    public function rollback()
    {
        if ($this->transactionCounter >= 0) {
            $this->transactionCounter = 0;
            return $this->oPDOInstance->rollback();
        }
        $this->transactionCounter = 0;
        return false;
    }
}
