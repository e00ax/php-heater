<?php

namespace E00ax\Heater;

use E00ax\Heater\Heater;

class Helper
{
    /**
     * Check for cli
     *
     * @param object $log monolog
     * @return void
     */
    public static function checkCli($log)
    {
        // Only cli is allowed
        if (php_sapi_name() != 'cli') {
            $msg = "This application must be run on the command line.\n";

            // Log to file using Monolog
            $log->error($msg);

            // Throw an exception in case we are testing on cli
            throw new \Exception($msg);
        }
    }


    /**
     * Set proc title for better view in process manager
     *
     * @param object $log monolog
     * @param string $title proc name
     * @return void
     */
    public static function setProcTitle($log, $title)
    {
        // Set process title
        if (!cli_set_process_title($title)) {
            $msg = "Unable to set process title!\n";

            // Log to file using Monolog
            $log->error($msg);

            // Throw an exception in case we are testing on cli
            throw new \Exception($msg);
        }
    }


    /**
     * Check for valid timestamp
     *
     * @param object $log monolog
     * @param string $timestamp
     * @param array $dht22Last last dht22 entry
     * @return void
     */
    public static function validateTimestamp(
        $log,
        $timestamp,
        $dht22Last
    ) {
        // Check if last dht22 entry is too old ( older than 10min)
        $validTimestamp = new \DateTime($timestamp);
        $validTimestamp->modify('-10 minute');

        if (isset($dht22Last) && ($dht22Last < $validTimestamp->format("Y-m-d H:i:s"))) {
            $msg = sprintf("Timestamp from last MySQL entry is older than 10 min: %s", $dht22Last);

            // Log to file using Monolog
            $log->error($msg);

            // Throw an exception in case we are testing on cli
            throw new \Exception($msg);
        }
    }


    /**
     * Check for ini file
     *
     * @param object $log monolog
     * @param string $iniFile
     * @return array parsed ini file
     */
    public static function checkIni($log, $iniFile)
    {
        // Check for ini file
        if (!file_exists($iniFile)) {
            $msg = sprintf("Unable to read heater.ini in path: %s\n", $iniFile);

            // Log to file using Monolog
            $log->error($msg);

            // Throw an exception in case we are testing on cli
            throw new \Exception($msg);
        }
        
        return parse_ini_file($iniFile, true);
    }


    /**
     * Set Heater state
     *
     * @param int $heaterState
     * @param array $dht22Last last dht22 entry
     * @param string $temp
     * @param object $log monolog
     * @param int $channel pin channel
     * @return void
     */
    public static function setHeater(
        $heaterState,
        $dht22Last,
        $temp,
        $log,
        $channel
    ) {
        $heater = new Heater(
            $channel,
            $log
        );

        // Start heater or keep running when temp is too low
        if ($dht22Last <= $temp) {

            // Start heater only when state is off(1)
            if ($heaterState == 1) {
                $heater->setStatePigpio(0);

                $log->info(sprintf("Starting heater at room temp of %s and set temp of %s\n", $dht22Last, $temp));

                //[debug]
                //echo "Starting heater...\n";
                //print_r($setHeaterState);
            }
        } else {
            // Stop heater only if necessary
            if ($heaterState == 0) {
                $heater->setStatePigpio(1);

                $log->info(sprintf("Stopping heater at room temp of %s and set temp of %s\n", $dht22Last, $temp));

                //[debug]
                //echo "Stopping heater...\n";
                //print_r($setHeaterState);
            }
        }

        // Free memory
        $heater = null;
    }
}
