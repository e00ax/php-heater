<?php
/**
 * ============================================================================
 * Name        : heater.php
 * Author      : Christian Rickert
 * Version     : 0.1
 * Description : Deamon script to handle automatic heating control
 * Options	   : [manual|auto|google]
 * ============================================================================
 */
// App loader
require_once('loader.php');

use Heater\GoogleCalendar;

try {
    // Only cli is allowed
    if (php_sapi_name() != 'cli') {
        $msg = "This application must be run on the command line.\n";

        // Log to file using Monolog
        $log->error($msg);

        // Throw an exception in case we are testing on cli
        throw new Exception($msg);
    }

    // Set process title
    if (!cli_set_process_title($cfg['heater']['text'])) {
        $msg = "Unable to set process title!\n";

        // Log to file using Monolog
        $log->error($msg);

        // Throw an exception in case we are testing on cli
        throw new Exception($msg);
    }

    // Loop forever with a sleeptime
    while (true) {
        // Get fresh date & time
        $currDay = date("D");
        $currTime = date("H:i");
        $timestamp = date("Y-m-d H:i:s");

        // Get current heater state and temp hum
        $heaterState = $heater->getStatePigpio($cfg['heater']['channel'][1]);
        $dht22Last = $dht22->getLastDHT22();

        // Check if last dht22 entry is too old ( older than 10min)
        $validTimestamp = new DateTime($timestamp);
        $validTimestamp->modify('-10 minute');

        if (isset($dht22Last[0]['timestamp']) && ($dht22Last[0]['timestamp'] < $validTimestamp->format("Y-m-d H:i:s"))) {
            $msg = sprintf("Timestamp from last MySQL entry is older than 10 min: %s", $dht22Last[0]['timestamp']);

            // Log to file using Monolog
            $log->error($msg);

            // Throw an exception in case we are testing on cli
            throw new Exception($msg);
        }

        // [Debug]
        echo "Temperatur: " . $dht22Last[0]['temp'] . "\n";
        echo "Heater state: " . $heaterState . "\n";

        // Check for ini file
        if (!file_exists($cfg['heater']['ini'])) {
            $msg = sprintf("Unable to read heater.ini in path: %s\n", $cfg['heater']['ini']);

            // Log to file using Monolog
            $log->error($msg);

            // Throw an exception in case we are testing on cli
            throw new Exception($msg);
        }
        
        // Parse ini file
        $ini = parse_ini_file($cfg['heater']['ini'], true);

        switch ($ini['control']['mode']) {

            /**
             * Automatic heater cycles
             *
             * Heater.ini
             */
            case 'auto':
                // Check if heater array at least exists
                if (!$ini['auto']) {
                    $msg = "Unable to read automatic heater cycles in heater.ini!\n";

                    // Log to file using Monolog
                    $log->error($msg);
                    
                    // Throw an exception in case we are testing on cli
                    throw new Exception($msg);
                }

                // Loop over heater cycles
                foreach ($ini['auto'] as $day => $val) {
                    // Get cycles as array
                    $cycles = explode("#", $val);

                    // Get times
                    foreach ($cycles as $times) {
                        // Split cycles
                        $data = explode("-", $times);
                        $startTime = $data[0];
                        $endTime = $data[1];
                        $temp = $data[2];

                        // Check for current day
                        if ($currDay == $day) {

                            // Check for current time
                            if (($currTime >= $startTime) && ($currTime <= $endTime)) {

                                // Start heater or keep running when temp is too low
                                if ($dht22Last[0]['temp'] <= $temp) {

                                    // Start heater only when state is off(1)
                                    if ($heaterState == 1) {
                                        $setHeaterState = $heater->setStatePigpio(0);

                                        $log->info($msg = sprintf("Starting heater at room temp of %s and set temp of %s\n", $dht22Last[0]['temp'], $temp));

                                        //[debug]
                                        echo "Starting heater...\n";
                                        //print_r($setHeaterState);
                                    }
                                } else {
                                    // Stop heater only if necessary
                                    if ($heaterState == 0) {
                                        $setHeaterState = $heater->setStatePigpio(1);

                                        $log->info($msg = sprintf("Stopping heater at room temp of %s and set temp of %s\n", $dht22Last[0]['temp'], $temp));

                                        //[debug]
                                        echo "Stopping heater...\n";
                                        //print_r($setHeaterState);
                                    }
                                }
                            }
                        }
                    }
                }

                // [Debug]
                //print_r($cycles);
                break;
            
            /**
             * Manual heater control
             *
             */
            case 'manual':
                if (!$ini['manual']['temp']) {
                    $msg = "Unable to read manual heater value in heater.ini!\n";

                    // Log to file using Monolog
                    $log->error($msg);
                    
                    // Throw an exception in case we are testing on cli
                    throw new Exception($msg);
                }
                
                // Get temp from ini file
                $temp = $ini['manual']['temp'];

                // [debug]
                //echo Temp: " . $temp . "\n";

                // Start heater or keep running when temp is too low
                if ($dht22Last[0]['temp'] <= $temp) {

                    // Start heater only when state is off(1)
                    if ($heaterState == 1) {
                        $setHeaterState = $heater->setStatePigpio(0);

                        $log->info($msg = sprintf("Starting heater at room temp of %s and set temp of %s\n", $dht22Last[0]['temp'], $temp));

                        //[debug]
                        echo "Starting heater...\n";
                        //print_r($setHeaterState);
                    }
                } else {
                    // Stop heater only if necessary
                    if ($heaterState == 0) {
                        $setHeaterState = $heater->setStatePigpio(1);

                        $log->info($msg = sprintf("Stopping heater at room temp of %s and set temp of %s\n", $dht22Last[0]['temp'], $temp));

                        //[debug]
                        echo "Stopping heater...\n";
                        //print_r($setHeaterState);
                    }
                }
                break;
 
            /**
             * Google calendar control
             *
             */
            case 'google':
                $fp = fsockopen("www.google.de", 80, $errno, $errstr, 30);
                if ($fp == false) {
                    $msg = sprintf("Unable to reach service google.de\nErrno: %s\nErrstr: %s\n", $errno, $errstr);

                    // Log to file using Monolog
                    $log->error($msg);
                    
                    // Throw an exception in case we are testing on cli
                    throw new Exception($msg);

                // Only go further when we are online
                } else {
                    $googleCalendar = new GoogleCalendar(
                        $cfg['heater']['google']['token'],
                        $cfg['heater']['google']['credentials'],
                        $cfg['heater']['google']['id'],
                        $log
                    );
    
                    // Get events from calendar
                    $events = $googleCalendar->getEvents($cfg['heater']['google']['maxResults']);

                    // [Debug]
                    //print_r($events);
    
                    // Get temp from item summary
                    $temp = $googleCalendar->getCurrentTemp(
                        $events,
                        $cfg['heater']['google']['colorId']
                    );

                    // [Debug]
                    //print_r($temp);
    
                    // Start heater or keep running when temp is too low
                    if ($dht22Last[0]['temp'] <= $temp) {

                        // Start heater only when state is off(1)
                        if ($heaterState == 1) {
                            $setHeaterState = $heater->setStatePigpio(0);

                            $log->info($msg = sprintf("Starting heater at room temp of %s and set temp of %s\n", $dht22Last[0]['temp'], $temp));

                            //[debug]
                            echo "Starting heater...\n";
                            //print_r($setHeaterState);
                        }
                    } else {
                        // Stop heater only when state is on(0)
                        if ($heaterState == 0) {
                            $setHeaterState = $heater->setStatePigpio(1);

                            $log->info($msg = sprintf("Stopping heater at room temp of %s and set temp of %s\n", $dht22Last[0]['temp'], $temp));

                            //[debug]
                            echo "Stopping heater...\n";
                            //print_r($setHeaterState);
                        }
                    }
                }

                fclose($fp);
                break;

            // Default error
            default:
                $msg = sprintf("Unable to read mode [auto|manual|google] in: %s\n", $cfg['heater']['ini']);

                // Log to file using Monolog
                $log->error($msg);
                
                // Throw an exception in case we are testing on cli
                throw new Exception($msg);
        }

        // [debug]
        //print_r($ini);

        // sleeptime
        sleep($cfg['heater']['sleeptime']);
    }

    // Close MySQL (in case we reach this point)
    return $mysql->close();
} catch (Exception $e) {
    echo 'Message: ' . $e->getMessage();
    exit(0);
}
