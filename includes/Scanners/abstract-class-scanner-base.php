<?php
/**
 * Abstract Scanner Base class
 *
 * Base class for all scanners
 *
 * @package OptionMap
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Ensure interface is loaded first
if (!interface_exists('OptionMap_Scanner_Interface')) {
    require_once SF_PLUGIN_DIR . 'includes/Scanners/interface-scanner.php';
}

/**
 * Abstract Scanner Base class
 */
abstract class OptionMap_Scanner_Base implements OptionMap_Scanner_Interface {

    /**
     * URL Validator instance
     *
     * @var OptionMap_URL_Validator
     */
    protected $url_validator;

    /**
     * Settings Formatter instance
     *
     * @var OptionMap_Settings_Formatter
     */
    protected $formatter;

    /**
     * Constructor
     *
     * @param OptionMap_URL_Validator $url_validator URL validator instance
     * @param OptionMap_Settings_Formatter $formatter Settings formatter instance
     */
    public function __construct(OptionMap_URL_Validator $url_validator, OptionMap_Settings_Formatter $formatter) {
        $this->url_validator = $url_validator;
        $this->formatter = $formatter;
    }

    /**
     * Normalize setting data
     *
     * @param array $setting Setting data
     * @return array Normalized setting
     */
    protected function normalize_setting($setting) {
        // Ensure required fields
        if (!isset($setting['name'])) {
            $setting['name'] = '';
        }
        if (!isset($setting['category'])) {
            $setting['category'] = 'general';
        }
        if (!isset($setting['type'])) {
            $setting['type'] = 'unknown';
        }
        
        // Validate URL if present
        if (isset($setting['url'])) {
            $setting['url'] = $this->url_validator->validate($setting['url']);
        }
        
        return $setting;
    }

    /**
     * Build customizer URL with proper autofocus parameters
     *
     * @param string $type 'panel', 'section', or 'control'
     * @param string $id The ID of the panel/section/control
     * @param string $panel_id Optional panel ID if section/control is in a panel
     * @param string $section_id Optional section ID if control is in a section
     * @return string Customizer URL with autofocus
     */
    protected function build_customizer_url($type, $id, $panel_id = '', $section_id = '') {
        // WordPress customizer URL format: wp-admin/customize.php?autofocus[control]=control_id
        // Use admin_url to get the full path, then append query parameters
        $url = admin_url('customize.php');
        
        if (empty($id)) {
            return $url;
        }
        
        // Build autofocus parameter based on type
        $autofocus_key = 'autofocus[' . $type . ']';
        $query_params = array($autofocus_key => $id);
        
        // Add panel if provided and type is section or control
        if (!empty($panel_id) && in_array($type, array('section', 'control'))) {
            $query_params['autofocus[panel]'] = $panel_id;
        }
        
        // Add section if provided and type is control
        if (!empty($section_id) && $type === 'control') {
            $query_params['autofocus[section]'] = $section_id;
        }
        
        // Build query string
        $query_string = http_build_query($query_params);
        
        if (!empty($query_string)) {
            $url .= '?' . $query_string;
        }
        
        return $url;
    }

    /**
     * Find theme option page URL
     *
     * @param string $option_name Option name to search for
     * @return string URL to theme options page
     */
    protected function find_theme_option_page_url($option_name) {
        global $submenu, $menu;
        
        // Check submenus under Appearance
        if (isset($submenu['themes.php'])) {
            foreach ($submenu['themes.php'] as $item) {
                if (isset($item[2]) && (
                    strpos($item[2], 'theme') !== false ||
                    strpos($item[2], 'option') !== false ||
                    strpos($item[2], 'customize') !== false
                )) {
                    $url = admin_url('themes.php?page=' . $item[2]);
                    // Validate and fix invalid URLs
                    return $this->url_validator->validate($url);
                }
            }
        }
        
        // Check top-level menus
        if (isset($menu)) {
            foreach ($menu as $item) {
                if (isset($item[0]) && isset($item[2]) && (
                    stripos($item[0], 'Theme') !== false ||
                    stripos($item[0], 'Option') !== false
                )) {
                    return admin_url($item[2]);
                }
            }
        }
        
        // Default to customizer
        return admin_url('customize.php');
    }
}

