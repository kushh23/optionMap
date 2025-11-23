<?php
/**
 * Settings Aggregator class
 *
 * Aggregates and categorizes settings from multiple sources
 *
 * @package OptionMap
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings Aggregator class
 */
class OptionMap_Settings_Aggregator {

    /**
     * URL Validator instance
     *
     * @var OptionMap_URL_Validator
     */
    private $url_validator;

    /**
     * Constructor
     *
     * @param OptionMap_URL_Validator $url_validator URL validator instance
     */
    public function __construct(OptionMap_URL_Validator $url_validator) {
        $this->url_validator = $url_validator;
    }

    /**
     * Aggregate settings from multiple scanners
     *
     * @param array $scanner_results Array of arrays from different scanners
     * @return array Aggregated and validated settings
     */
    public function aggregate($scanner_results) {
        $all_settings = array();
        
        // Merge all scanner results
        foreach ($scanner_results as $results) {
            if (is_array($results)) {
                $all_settings = array_merge($all_settings, $results);
            }
        }
        
        // Validate all URLs
        $all_settings = $this->url_validator->validate_settings_urls($all_settings);
        
        return $all_settings;
    }

    /**
     * Get categories with counts
     *
     * @param array $settings All settings array
     * @return array Categories with counts
     */
    public function get_categories($settings) {
        $categories = array(
            'all' => array('label' => 'Everything', 'emoji' => '✨', 'count' => count($settings)),
            'general' => array('label' => 'General Settings', 'emoji' => '⚙️', 'count' => 0),
            'appearance' => array('label' => 'Look & Feel', 'emoji' => '🎨', 'count' => 0),
            'content' => array('label' => 'Content & Writing', 'emoji' => '📝', 'count' => 0),
            'media' => array('label' => 'Media', 'emoji' => '🖼️', 'count' => 0),
            'seo' => array('label' => 'SEO & URLs', 'emoji' => '🔍', 'count' => 0),
            'patterns' => array('label' => 'Patterns', 'emoji' => '🧩', 'count' => 0),
            'dashboard_pages' => array('label' => 'Dashboard Pages', 'emoji' => '📊', 'count' => 0)
        );
        
        foreach ($settings as $setting) {
            $cat = $setting['category'] ?? 'general';
            if (isset($categories[$cat])) {
                $categories[$cat]['count']++;
            }
        }
        
        return $categories;
    }
}

