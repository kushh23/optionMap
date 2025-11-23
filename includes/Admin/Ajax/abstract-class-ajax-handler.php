<?php
/**
 * Abstract AJAX Handler class
 *
 * Base class for all AJAX handlers
 *
 * @package OptionMap
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Abstract AJAX Handler class
 */
abstract class OptionMap_Ajax_Handler_Base {

    /**
     * Action name for this handler
     *
     * @var string
     */
    protected $action;

    /**
     * Constructor
     *
     * @param string $action AJAX action name
     */
    public function __construct($action) {
        $this->action = $action;
        $this->register();
    }

    /**
     * Register AJAX handler
     */
    protected function register() {
        add_action('wp_ajax_' . $this->action, array($this, 'handle'));
    }

    /**
     * Verify nonce
     *
     * @param string $nonce_field Nonce field name
     */
    protected function verify_nonce($nonce_field = 'nonce') {
        check_ajax_referer('sf_ajax_nonce', $nonce_field);
    }

    /**
     * Verify user capability
     *
     * @param string $capability Capability to check
     */
    protected function verify_capability($capability = 'manage_options') {
        if (!current_user_can($capability)) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
    }

    /**
     * Handle AJAX request
     * Must be implemented by child classes
     */
    abstract public function handle();
}

