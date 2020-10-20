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
    'heater' => [
        'text' => '[smarthome => heater]',
        'ini' => '/webtools/heater/config/heater.ini',
        'log' => '/webtools/heater/log/heater.log',
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
            '2' => '20',
            '3' => '21',
        ],

        'daemon' => [
            'path' => '/webtools/heater/',
            'name' => 'heater',
        ],

        'google' => [
            'token' => '/webtools/heater/auth/token.json',
            'credentials' => '/webtools/heater/auth/credentials.json',
            'id' => 'primary',
            'colorId' => '11',
            'maxResults' => '10',
        ],
    ],
];
