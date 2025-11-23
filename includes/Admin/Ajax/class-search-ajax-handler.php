<?php
/**
 * Search AJAX Handler class
 *
 * Handles search AJAX requests
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
 * Search AJAX Handler class
 */
class OptionMap_Search_Ajax_Handler extends OptionMap_Ajax_Handler_Base {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct('sf_search');
    }

    /**
     * Handle AJAX request
     */
    public function handle() {
        $this->verify_nonce();
        $this->verify_capability();
        
        $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        
        // For now, return success (search is handled client-side)
        wp_send_json_success(array());
    }
}

