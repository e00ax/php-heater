<?php
/**
 * ============================================================================
 * Name        : GoogleClient.class.php
 * Author      : Christian Rickert
 * Version     : 0.1
 * Description : Google client class
 * ============================================================================
*/
namespace Heater;

use Google_Client;

class GoogleClient {
    protected $token;
    protected $credentials;
    protected $client;
    protected $log;

    /**
     * Constructor
     *
     * @return ...
     */
    public function __construct($token, $credentials, $log)
    {
        $this->token = $token;
        $this->credentials = $credentials;

        $this->client = new Google_Client();
        $this->client->setAuthConfig($this->credentials);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('select_account consent');

        // Get Monolog stream
        $this->log = $log;

        // Use existing token. If it doesn't exist, run quickstart.php again to get a new one
        if (file_exists($this->token)) {
            $accessToken = json_decode(file_get_contents($this->token), true);
            $this->client->setAccessToken($accessToken);
        } else {
            $msg = sprintf("Unable to find access token.\nPlease run quickstart.php again to retrieve a new one!\n");

            // Log to file using Monolog
            $this->log->error($msg);

            // Throw an exception in case we are testing on cli
            throw new \Exception($msg);
        }
    }


    /**
     * Get token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }


    /**
     * Get credentials
     *
     * @return string
     */
    public function getCredentials()
    {
        return $this->credentials;
    }
}
