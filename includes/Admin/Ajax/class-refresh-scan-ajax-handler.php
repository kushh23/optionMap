<?php
/**
 * Refresh Scan AJAX Handler class
 *
 * Handles refresh scan AJAX requests
 *
 * @package OptionMap
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Ensure base class is loaded
if (!class_exists('OptionMap_Ajax_Handler_Base')) {
    require_once SF_PLUGIN_DIR . 'includes/Admin/Ajax/abstract-class-ajax-handler.php';
}

/**
 * Refresh Scan AJAX Handler class
 */
class OptionMap_Refresh_Scan_Ajax_Handler extends OptionMap_Ajax_Handler_Base {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct('sf_refresh_scan');
    }

    /**
     * Handle AJAX request
     */
    public function handle() {
        $this->verify_nonce();
        $this->verify_capability();
        
        // For now, return success (scanning happens on page load)
        wp_send_json_success(array('message' => 'Scan completed'));
    }
}

