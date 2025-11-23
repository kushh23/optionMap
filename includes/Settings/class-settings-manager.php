<?php
/**
 * Settings Manager class
 *
 * Manages plugin settings and options
 *
 * @package OptionMap
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings Manager class
 */
class OptionMap_Settings_Manager {

    /**
     * Get OpenAI API key
     *
     * @return string API key
     */
    public function get_openai_api_key() {
        return get_option('sf_openai_api_key', '');
    }

    /**
     * Save OpenAI API key
     *
     * @param string $api_key API key to save
     * @return bool Success status
     */
    public function save_openai_api_key($api_key) {
        return update_option('sf_openai_api_key', sanitize_text_field($api_key));
    }

    /**
     * Get plugin version
     *
     * @return string Version
     */
    public function get_version() {
        return get_option('sf_version', SF_VERSION);
    }

    /**
     * Get last scan timestamp
     *
     * @return int Timestamp
     */
    public function get_last_scan() {
        return get_option('sf_last_scan', 0);
    }

    /**
     * Update last scan timestamp
     *
     * @return bool Success status
     */
    public function update_last_scan() {
        return update_option('sf_last_scan', current_time('timestamp'));
    }
}

