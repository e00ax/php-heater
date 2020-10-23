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
require_once('inc.php');

use Heater\GoogleCalendar;
use Heater\Helper;

try {
    // Only cli is allowed
    Helper::checkCli($log);

    // Set process title
    Helper::setProcTitle(
        $log,
        $cfg['heater']['title']
    );

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
        Helper::validateTimestamp(
            $log,
            $timestamp,
            $dht22Last[0]['timestamp']
        );

        // [Debug]
        echo "Temperatur: " . $dht22Last[0]['temp'] . "\n";
        echo "Heater state: " . $heaterState . "\n";

        // Check for ini file
        $ini = Helper::checkIni(
            $log,
            $cfg['heater']['ini']
        );

        switch ($ini['control']['mode']) {

            /**
             * Automatic heater cycles
             *
             * from heater.ini
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

                                // Set heater
                                Helper::setHeater(
                                    $heaterState,
                                    $dht22Last[0]['temp'],
                                    $temp,
                                    $log
                                );
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

                // Set heater
                Helper::setHeater(
                    $heaterState,
                    $dht22Last[0]['temp'],
                    $temp,
                    $log
                );
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
    
                    // Set heater
                    Helper::setHeater(
                        $heaterState,
                        $dht22Last[0]['temp'],
                        $temp,
                        $log
                    );
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
