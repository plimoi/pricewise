<?php
/**
 * Test History Triggers Renderer Class
 * Handles rendering of the PW Triggers section
 *
 * @package PriceWise
 * @subpackage TestHistory
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Include the trigger actions class
require_once dirname(__FILE__) . '/class-triggers-renderer/main-class-trigger-actions.php';

class Pricewise_Test_History_Triggers_Renderer extends Pricewise_Test_History_Base_Renderer {
    /**
     * Trigger actions instance
     *
     * @var Pricewise_Triggers_Actions
     */
    private $trigger_actions;
    
    /**
     * Constructor
     * 
     * @param Pricewise_Test_History_DB $db The database handler
     */
    public function __construct($db) {
        parent::__construct($db);
        $this->trigger_actions = new Pricewise_Triggers_Actions($db);
    }
    
    /**
     * Render the PW Triggers section
     */
    public function render_triggers_section() {
        ?>
        <div class="card" style="max-width: 100%; margin-top: 20px;">
            <h2><?php _e('PW Triggers', 'pricewise'); ?></h2>
            <p><?php _e('Configure automatic actions based on API test results. Triggers can monitor specific fields like response time, status codes, or custom header values.', 'pricewise'); ?></p>
            
            <?php $this->trigger_actions->render_trigger_form(); ?>
        </div>
        <?php
    }
}