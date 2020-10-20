<?php
/**
 * ============================================================================
 * Name        : dht22.class.php
 * Author      : Christian Rickert
 * Version     : 0.1
 * Description : DHT22 class
 * ============================================================================
*/
namespace Heater;

class DHT22 {
    protected $host;
    protected $user;
    protected $passwd;
    protected $db;
    protected $port;
    protected $mysqli;


    /**
     * query_fetch_assoc
     *
     * @access private
     */
    private function queryFetchAssoc($query)
    {
        if (!$res = $this->mysqli->query($query)) {
            $msg = sprintf("MySQL Error ( %s ) %s\n", $this->mysqli->connect_errno, $this->mysqli->connect_error);

            // Log to file using Monolog
            $this->log->error($msg);
            
            // Throw an exception in case we are testing on cli
            throw new \Exception($msg);
        }

        // [Debug]
        //echo "query:: ".$query."\n";

        $tbl = $res->fetch_all(MYSQLI_ASSOC);

        $res->free_result();

        return $tbl;
    }
    

    /**
     * Constructor
     *
     * @return ...
     */
    public function __construct(
        $host,
        $user,
        $passwd,
        $db,
        $log
    )
    {
        // Get Monolog stream
        $this->log = $log;

        // Open connection
        $this->mysqli = new \mysqli(
            $host,
            $user,
            $passwd,
            $db
        );

        // Check connection
        if ($this->mysqli->connect_error) {
            $msg = sprintf("Connect Error ( %s ) %s\n", $this->mysqli->connect_errno, $this->mysqli->connect_error);

            // Log to file using Monolog
            $this->log->error($msg);
            
            // Throw an exception in case we are testing on cli
            throw new \Exception($msg);
        }

        // Set names to UTF-8 to prevent encoding problems
        $this->mysqli->query("SET NAMES 'utf8'");
    }


    /**
     * Get dht22 as assoc array
     */
    public function getDHT22()
    {
        return $this->queryFetchAssoc("SELECT * from `dht22`");
    }


    /**
     * Get last entry dht22 as assoc array
     */
    public function getLastDHT22()
    {
        return $this->queryFetchAssoc("SELECT * FROM `dht22` ORDER BY id DESC LIMIT 1");
    }
}
