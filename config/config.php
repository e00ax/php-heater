<?php
/**
 * ============================================================================
 * Name        : config.php
 * Author      : Christian Rickert
 * Version     : 0.1
 * Description : Application wide config file
 * ============================================================================
*/
return [
    /**
     * MySQL
     */
    'mysql' => [
        'server' => 'localhost',
        'username' => 'sh',
        'passwd' => 'sh2019',
        'db' => 'smarthome',
    ],

    /**
     * Heater
     */
    'heater' => [
        'title' => '[smarthome => php-heater]',
        'ini' => '/webtools/php-heater/config/heater.ini',
        'log' => '/webtools/php-heater/log/heater.log',
        'gpio' => '/usr/bin/gpio',
        'temp' => '21',
        'sleeptime' => '600',

        'mode' => [
            '1' => 'manual',
            '2' => 'auto',
            '3' => 'google',
        ],

        'channel' => [
            '1' => '26',
            //'2' => '20', - unused
            //'3' => '21', - unused
        ],

        'daemon' => [
            'path' => '/webtools/php-heater/',
            'name' => 'php-heater',
        ],

        'google' => [
            'token' => '/webtools/php-heater/auth/token.json',
            'credentials' => '/webtools/php-heater/auth/credentials.json',
            'id' => 'primary',
            'colorId' => '11',
            'maxResults' => '10',
        ],
    ],
];
