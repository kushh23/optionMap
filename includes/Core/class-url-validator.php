<?php
/**
 * URL Validator class
 *
 * Validates and fixes invalid admin URLs
 *
 * @package OptionMap
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * URL Validator class
 */
class OptionMap_URL_Validator {

    /**
     * Validate and fix invalid admin URLs
     * Fixes URLs like themes.php?page=themes.php that return 404
     *
     * @param string $url The URL to validate
     * @return string Valid URL (redirects to wp-admin if invalid)
     */
    public function validate($url) {
        // Parse the URL
        $parsed = parse_url($url);
        
        // If URL parsing fails, return wp-admin as fallback
        if ($parsed === false || !isset($parsed['path'])) {
            return admin_url();
        }
        
        // Extract the base file name (e.g., 'themes.php' from '/wp-admin/themes.php')
        $path = $parsed['path'];
        $base_file = basename($path);
        
        // Check if there's a query string with 'page' parameter
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $query_params);
            
            // If page parameter equals the base file name, it's invalid (e.g., themes.php?page=themes.php)
            if (isset($query_params['page']) && $query_params['page'] === $base_file) {
                // Redirect to wp-admin page
                return admin_url();
            }
        }
        
        // URL is valid, return as-is
        return $url;
    }

    /**
     * Validate URLs in a settings array
     *
     * @param array $settings Array of settings with 'url' keys
     * @return array Settings array with validated URLs
     */
    public function validate_settings_urls($settings) {
        foreach ($settings as &$setting) {
            if (isset($setting['url']) && !empty($setting['url'])) {
                $setting['url'] = $this->validate($setting['url']);
            }
        }
        return $settings;
    }
}

