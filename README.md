# Control Atmotec gas boiler with a raspberry pi
!!!Development status:<br>
> Package is currently in Development status. Bugs may occur, library may be unstable, API may be changed anytime.<br>
## Prequisites:
- A google developer token and credentials.
- The setup assumes you have a raspberry pi with a relais card or something similar running.
- Lamp server enviroment must be running.
- Pigpiod must be running.
- Finally the pi must be connected to the Atmotec boiler so it can power on the heater by pin state.<br>

> The Atmotec gas boiler runs on 230V so be sure you know what you are doing!<br>

## Installation:
Package may be installed using Composer:
> `composer require e00ax/php-heater`<br>

Create directory `<auth>` in the root directory.<br>
Place your google token and credentials in `<auth>`.<br>
File names must be token.json and crendtials.json.

## Dht22 Configuration:
Rename `<dht22/dht22_example.ini>` to `<dht22/dht22.ini>`.<br>
Fill in your pin and mysql data.<br>

## Startup:
Go to `<dht22>`.<br>
Start the service with `./DHTXXD_SYS_V start`.<br>

## Heater Configuration:
Rename `<config/config_sample.php>` to `<config/config.php>`.<br>
Fill in your pin, paths and mysql data.<br>
Rename `<config/heater_sample.ini>` to `<config/heater.ini>`.<br>

## Startup:
Start the service with `./HEATER_SYS_V start`.<br>

## To Do:
- Implement proper hysteresis (not necessary in my case).
- Change from dht22 to pt1000 for more stability and accuracy.<br>
- Implement script to create the necessery SQL table.


> Sorry, no further documentation available yet!
