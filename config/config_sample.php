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
        'server' => '',
        'username' => '',
        'passwd' => '',
        'db' => '',
    ],

    /**
     * Heater
     */
    'heater' => [
        'title' => '[smarthome => php-heater]',
        'ini' => 'path-to-heater.ini',
        'log' => 'path-to-heater.log',
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

        'google' => [
            'token' => 'path-to-token.json',
            'credentials' => 'path-to-credentials.json',
            'id' => 'primary',
            'colorId' => '11',
            'maxResults' => '10',
        ],
    ],
];
