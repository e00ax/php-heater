<?php
/**
 * ============================================================================
 * Name        : heater.class.php
 * Author      : Christian Rickert
 * Version     : 0.1
 * Description : Control a relais card 
 *             : (and an atmotec gas heater wich is connected to it)
 * ============================================================================
*/
namespace Heater;

use Volantus\Pigpio\Client;
use Volantus\Pigpio\Protocol\Commands;
use Volantus\Pigpio\Protocol\DefaultRequest;
use Volantus\Pigpio\Protocol\DefaultResponseStructure;

class Heater
{
    protected $pin;
    protected $log;
    protected $gpio;
    
    /**
     * Constructor
     *
     * @return void
     */
    public function __construct($pin, $log, $gpio = '')
    {
        $this->pin = $pin;
        $this->log = $log;
        $this->gpio = $gpio;
    }


    /**
     * Get gpio state
     *
     * @return string
     */
    public function getStateExec()
    {
        // Gpio command
        $cmd = sprintf(
            "%s -g read %s",
            $this->gpio,
            $this->pin
        );

        if (!file_exists($this->gpio)) {
            $msg = sprintf("Unable to find gpio in: %s\n", $this->gpio);

            // Log to file using Monolog
            $this->log->error($msg);
            
            // Throw an exception in case we are testing on cli
            throw new \Exception($msg);
        }

        // GPIO cmd
        exec($cmd, $out, $retval);

        // Error handling
        if ($retval == 0) {
            return $out[0];
        } else {
            $msg = sprintf("Gpio command execution failed!");

            // Log to file using Monolog
            $this->log->error($msg);
            
            // Throw an exception in case we are testing on cli
            throw new \Exception($msg);
        }
    }


    /**
     * Set gpio state using gpio system call
     *
     * @param $state
     * @return string
     */
    public function setStateExec($state)
    {
        // Gpio mode
        $cmd_mode = sprintf(
            "%s -g mode %s out",
            $this->gpio,
            $this->pin
        );

        // Gpio command
        $cmd = sprintf(
            "%s -g write %s %s",
            $this->gpio,
            $this->pin,
            $state
        );

        if (!file_exists($this->gpio)) {
            $msg = sprintf("Unable to find gpio in: %s\n", $this->gpio);

            // Log to file using Monolog
            $this->log->error($msg);
            
            // Throw an exception in case we are testing on cli
            throw new \Exception($msg);
        }

        // GPIO cmd
        exec($cmd_mode);
        exec($cmd, $out, $retval);

        // Error handling
        if ($retval == 0) {
            return $retval;
        } else {
            $msg = sprintf("Gpio command execution failed!");

            // Log to file using Monolog
            $this->log->error($msg);
            
            // Throw an exception in case we are testing on cli
            throw new \Exception($msg);
        }
    }

    /**
     * Get gpio state with pigpiod
     *
     * @param $state
     * @return int
     */
    public function getStatePigpio()
    {
        // Change pin state using pigpiod
        $client = new Client();
        $responseStructure = new DefaultResponseStructure();
        $response = $client->sendRaw(new DefaultRequest(
            Commands::READ,
            $this->pin,
            0,
            $responseStructure
        ));

        // was successful
        if ($response->isSuccessful() == 0) {
            $msg = sprintf("Unable to get heater state.Pigpio response error: %s", $response->getResponse());

            // Log to file using Monolog
            $this->log->error($msg);
            
            // Throw an exception in case we are testing on cli
            throw new \Exception($msg);
        } else {
            // some responses return data (P3)
            return $response->getResponse();
        }
    }


    /**
     * Set gpio state with pigpiod
     *
     * @param $state
     * @return int
     */
    public function setStatePigpio($state)
    {
        // Change pin state using pigpiod
        $client = new Client();
        $responseStructure = new DefaultResponseStructure();
        $response = $client->sendRaw(new DefaultRequest(
            Commands::WRITE,
            $this->pin,
            $state,
            $responseStructure
        ));

        // was successful
        if ($response->isSuccessful() == 0) {
            $msg = sprintf("Unable to set heater state.Pigpio response error: %s", $response->getResponse());

            // Log to file using Monolog
            $this->log->error($msg);
            
            // Throw an exception in case we are testing on cli
            throw new \Exception($msg);
        } else {
            // some responses return data (P3)
            return $response->getResponse();
        }
    }
}
