<?php

namespace App\Service;

/**
 * Read the csv file into the Database
 *
 * Class CsvReader
 * @package App\Service
 */
class CsvReader
{
    /** @var string */
    private $dbHost;

    /** @var string */
    private $dbName;

    /** @var string */
    private $dbUser;

    /** @var string */
    private $dbPwd;

    /**
     * CsvReader constructor.
     * @param string $dbHost
     * @param string $dbName
     * @param string $dbUser
     * @param string $dbPwd
     */
    public function __construct(string $dbHost, string $dbName, string $dbUser, string $dbPwd)
    {
        $this->dbHost = $dbHost;
        $this->dbName = $dbName;
        $this->dbUser = $dbUser;
        $this->dbPwd = $dbPwd;
    }

    /**
     * @param string $filePath
     * @param string $delimiter
     */
    public function readContent(string $filePath, string $delimiter = ';'): void
    {
        $pdoConn  = $this->createConnection();

        /*$sql = "SET FOREIGN_KEY_CHECKS=0";
        $stmt = $pdoConn->prepare($sql);
        $stmt->execute();*/

        $sql = "LOAD DATA 
                    LOCAL INFILE '{$filePath}'
                    INTO TABLE csv_product 
                FIELDS TERMINATED BY \"{$delimiter}\"
                LINES TERMINATED BY \"\\n\"                                
                (sku, description, normal_price, special_price)       
                ";

        $stmt = $pdoConn->prepare($sql);
        $stmt->execute();
    }

    /**
     * @return \PDO
     */
    private function createConnection(): \PDO
    {
        return new \PDO(
            "mysql:dbname={$this->dbName};host={$this->dbHost}",
            $this->dbUser,
            $this->dbPwd,
            [
                \PDO::MYSQL_ATTR_LOCAL_INFILE => true,
            ]
        );
    }
}