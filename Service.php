<?php

/**
 * NixLabs Discord Notifications Module.
 *
 * @copyright NixLabs (https://nixlabs.dev)
 * @license   Apache-2.0
 *
 * Copyright NixLabs 2022
 * 
 * 
 * This module allows for discord notifications to be sent for different system events.
 * 
 */

namespace Box\Mod\Discord;

use FOSSBilling\InformationException;

class Service
{
    protected $di;

    // Global Configuration Variables
    protected static $url = 'https://discord.com/api/webhooks/1288169311499849841/Ha3WHG6BVlQqwp8nYwGWs8dcDTl1xGNrjvAg7ENOnh8FYCmLMK6-n3TJGfuW4vb7Q-Jp';
    protected static $staffRole = "1204151705101533204";


    public function setDi(\Pimple\Container|null $di): void
    {
        $this->di = $di;
    }

    /**
     * Method to install the module. In most cases you will use this
     * to create database tables for your module.
     *
     * If your module isn't very complicated then the extension_meta
     * database table might be enough.
     *
     * @return bool
     *
     * @throws InformationException
     */
    public function install(): bool
    {

        // throw new InformationException("Throw exception to terminate module installation process with a message", array(), 123);
        return true;
    }

    /**
     * Method to uninstall module. In most cases you will use this
     * to remove database tables for your module.
     *
     * You also can opt to keep the data in the database if you want
     * to keep the data for future use.
     *
     * @return bool
     *
     * @throws InformationException
     */
    public function uninstall(): bool
    {
        // throw new InformationException("Throw exception to terminate module uninstallation process with a message", array(), 124);
        return true;
    }

    /**
     * Method to update module. When you release new version to
     * extensions.fossbilling.org then this method will be called
     * after the new files are placed.
     *
     * @param array $manifest - information about the new module version
     *
     * @return bool
     *
     * @throws InformationException
     */
    public function update(array $manifest): bool
    {
        // throw new InformationException("Throw exception to terminate module update process with a message", array(), 125);
        return true;
    }




    public static function sendDiscordNotification(String $message, Bool $mentionStaff = false){
        $formattedMessage = "";

        if($mentionStaff == true){
            $formattedMessage = "<@&".self::$staffRole."> ".$message;
        }else{
            $formattedMessage = $message;
        }

        $data = ['content' => $formattedMessage];
        
        // Convert the data to JSON format
        $jsonData = json_encode($data);
        
        // Initialize cURL
        $ch = curl_init(self::$url);
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response as a string
        curl_setopt($ch, CURLOPT_POST, true); // Specify that this is a POST request
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json', // Set the content type to JSON
            'Content-Length: ' . strlen($jsonData) // Set the content length
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData); // Attach the JSON data to the request
        
        // Execute the request
        $response = curl_exec($ch);
        
        // Get the response status code
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Close cURL
        curl_close($ch);

        
        return ["response" => $response, "status" => $statusCode];
    }



    /**
     * onAfterClientOpenTicket
     * ---
     * 
     * When a client opens a ticket, it notifies the discord server
     * in the #notifications channel so that they know that a user
     * has opened a ticket. This way we dont have the issue of customers
     * waiting 15 days for a response. *cough* mineman *cough*
     * 
     *
     * @return void
     *
     * @throws InformationException
     */
    public static function onAfterClientOpenTicket(\Box_Event $event): void
    {
        $di = $event->getDi();
        $logger = $di['logger'];
        $params = $event->getParameters();
        $supportService = $di['mod_service']('support');

        $ticketObj = $supportService->getTicketById($params['id']);
        $ticketArr = $supportService->toApiArray($ticketObj, true, null);

        //Send the notification
        $response = self::sendDiscordNotification('New ticket created by ' . $ticketArr["first"]["author"]["name"] . ' with title "'. $ticketArr["subject"].'"', true);

        if($response["status"] == 204){
            $logger->info('Sent webhook for ticket "%s"', $params["id"]);
        }else{
            $logger->info('Webhook POST failed with code: "%s"', $response["status"]);
        }        

    }

    /**
     * onAfterClientReplyTicket
     * ---
     * 
     * When a client replies to a ticket, it notifies the discord 
     * server in the #notifications channel so that they know that a user
     * has replied to a ticket. This way we dont have the issue of customers
     * waiting 15 days for a response from staff. *cough* mineman *cough*
     * 
     *
     * @return void
     *
     * @throws InformationException
     */
    public static function onAfterClientReplyTicket(\Box_Event $event)
    {
        $di = $event->getDi();
        $logger = $di['logger'];
        $params = $event->getParameters();
        $supportService = $di['mod_service']('support');

        $ticketObj = $supportService->getTicketById($params['id']);
        $ticketArr = $supportService->toApiArray($ticketObj, true);

        //Send the notification
        $response = self::sendDiscordNotification('Ticket "' . $ticketArr["subject"] . '" with ID '.$params["id"].' was updated by '. $ticketArr["first"]["author"]["name"], true);


        if($response["status"] == 204){
            $logger->info('Sent webhook for ticket %s', $params["id"]);

        }else{
            $logger->info('Webhook POST failed with code: "%s"', strval($response["status"]));
        }        
    }
}
