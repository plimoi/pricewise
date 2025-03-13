<?php
/**
 * Example Trigger Action
 * This is a sample trigger that doesn't do anything but demonstrates the structure
 *
 * @package PriceWise
 * @subpackage Triggers
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Example trigger action class
 */
class PW_Trigger_Exa {
    /**
     * Trigger name
     *
     * @var string
     */
    public $name = 'Example Trigger Here';
    
    /**
     * Trigger description
     *
     * @var string
     */
    public $description = 'This is just an example trigger that doesn\'t do anything.';
    
    /**
     * Execute the trigger action
     *
     * @param array $data Trigger data
     * @return bool Success status
     */
    public function execute($data) {
        // This is just a sample trigger that doesn't actually do anything
        // In a real trigger, you would process the data and perform actions
        
        // Log that the trigger was executed
        error_log('Example trigger executed with data: ' . print_r($data, true));
        
        return true;
    }
}