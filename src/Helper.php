<?php
/**
 * ============================================================================
 * Name        : functions.php
 * Author      : Christian Rickert
 * Version     : 0.1
 * Description : Helper functions
 * ============================================================================
 */
namespace Heater;

// Monolog
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Heater\Heater;

class Helper
{
    /**
     * Automatic heater cycles
     *
     * @return ...
     */
    public static function setheater(
        $timestamp,
        $heaterState,
        $dht22Last,
        $temp,
        $log
    ) {
        // Log to file using Monolog
        $log = new Logger('sdHeater');
        $log->pushHandler(new StreamHandler($log, Logger::INFO));

        // Start heater or keep running when temp is too low
        if ($dht22Last[0]['temp'] <= $temp) {

            // Start heater only when state is off(1)
            if ($heaterState == 1) {
                //$setHeaterState = $heater->setStatePigpio(0);

                $log->info($msg = sprintf("Starting heater at room temp of %s and set temp of %s\n", $dht22Last[0]['temp'], $temp));

                //[debug]
                echo "Starting heater...\n";
                //print_r($setHeaterState);
            }
        } else {
            // Stop heater only if necessary
            if ($heaterState == 0) {
                //$setHeaterState = $heater->setStatePigpio(1);

                $log->info($msg = sprintf("Stopping heater at room temp of %s and set temp of %s\n", $dht22Last[0]['temp'], $temp));

                //[debug]
                echo "Stopping heater...\n";
                //print_r($setHeaterState);
            }
        }
    }
}
