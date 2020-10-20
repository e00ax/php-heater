<?php
/**
 * ============================================================================
 * Name        : GoogleCalendar.class.php
 * Author      : Christian Rickert
 * Version     : 0.1
 * Description : Google Calendar class
 * ============================================================================
*/
namespace Heater;

use Google_Service_Calendar;

class GoogleCalendar extends GoogleClient
{
    protected $service;
    protected $id;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct(
        $token,
        $credentials,
        $id,
        $log
    )
    {
        // Call GoogleClient
        parent::__construct($token, $credentials, $log);

        // Set calendar options
        $this->client->setApplicationName('Smarthome heater google calendar control');
        $this->client->setScopes(Google_Service_Calendar::CALENDAR_READONLY);

        // Start new calendar service
        $this->service = new Google_Service_Calendar($this->client);

        // Set primary calendar
        // TODO: choose between multiple calendars
        $this->id = $id;
    }


    /**
     * Get calendar output
     *
     * @return string
     */
    public function getEvents($maxResults)
    {
        // Params
        $optParams = array(
            'maxResults' => $maxResults,
            'orderBy' => 'startTime',
            'singleEvents' => true,
            'timeMin' => date('c'),
            //'timeMax' => date('c'),
        );
        
        // Get listed events
        $results = $this->service->events->listEvents($this->id, $optParams);
        
        // Get event items
        $events = $results->getItems();
        
        // [Debug]
        //print_r($events);

        return $events;
    }


    /**
     * Get current temperature from events
     *
     * @return string
     */
    public function getCurrentTemp($events, $colorId)
    {
        $temp = array();
        if (empty($events)) {
            $msg = sprintf("No upcoming events found in Google_Service_Calendar with id: %s\n", $this->id);

            // Log to file using Monolog
            $this->log->error($msg);
            
            // Throw an exception in case we are testing on cli
            throw new \Exception($msg);
        } else {
            // Loop over events and collect the temperature values
            foreach ($events as $event) {
                // [Debug]
                //echo "Start: " . $event->start->dateTime . "\n";
                //echo "End: " . $event->end->dateTime . "\n";
                //echo "Temp: " . $event->end->getSummary() . "\n";

                // Only look for our specified colorId
                if ($event->colorId == $colorId) {
                    $temp[] = $event->getSummary();
                }
            }

            // Return an error if we have no upcoming events in our calendar
            if (empty($temp)) {
                $msg = sprintf("No upcoming events found in Google_Service_Calendar with colorId: %s\n", $colorId);

                // Log to file using Monolog
                $this->log->error($msg);

                // Throw an exception in case we are testing on cli
                throw new \Exception($msg);
            } else {
                // Get the first temp value (which should be the most current one)
                // Valid value are only between 10 and 30 °C
                if ($temp[0] >= 10 && $temp[0] <= 30) {
                    return $temp[0];
                } else {
                    $msg = sprintf("Temperature must be between 10°C and 40°C. Temperature in event: %s°C\n", $temp[0]);

                    // Log to file using Monolog
                    $this->log->error($msg);

                    // Throw an exception in case we are testing on cli
                    throw new \Exception($msg);
                }
            }
        }
    }
}
