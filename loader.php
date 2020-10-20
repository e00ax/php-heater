<?php
/**
 * ============================================================================
 * Name        : loader.php
 * Author      : Christian Rickert
 * Version     : 0.1
 * Description : Make app wide class objects available
 * ============================================================================
*/
$cfg = include('/config/sh.php');

// Load vendors
require __DIR__ . '/vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Heater\Heater;
use Heater\DHT22;

// Get Monolog stream (even if we do not need it)
$log = new Logger('sdHeater');
$log->pushHandler(new StreamHandler($cfg['heater']['log']));

// Access Heater
$heater = new Heater(
    $cfg['heater']['channel']['1'],
    $log
);

// Access DHT22
$dht22 = new DHT22(
    $cfg['mysql']['server'],
    $cfg['mysql']['username'],
    $cfg['mysql']['passwd'],
    $cfg['mysql']['db'],
    $log
);
