<?php
/**
 * Database Options Scanner class
 *
 * Scans wp_options table for theme-specific options
 *
 * @package OptionMap
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Ensure base class is loaded
if (!class_exists('OptionMap_Scanner_Base')) {
    require_once SF_PLUGIN_DIR . 'includes/Scanners/abstract-class-scanner-base.php';
}

/**
 * Database Options Scanner class
 */
class OptionMap_Database_Options_Scanner extends OptionMap_Scanner_Base {

    /**
     * Scan for settings
     *
     * @return array Array of settings
     */
    public function scan() {
        $settings = array();
        global $wpdb;
        $theme = wp_get_theme();
        $theme_slug = get_option('stylesheet');
        $theme_template = get_option('template');
        $theme_name_lower = strtolower($theme->get('Name'));
        $theme_text_domain = $theme->get('TextDomain');
        
        $option_patterns = array();
        
        if (!empty($theme_slug)) {
            $option_patterns[] = $theme_slug . '_%';
            $option_patterns[] = '%_' . $wpdb->esc_like($theme_slug) . '_%';
        }
        
        if (!empty($theme_template) && $theme_template !== $theme_slug) {
            $option_patterns[] = $theme_template . '_%';
            $option_patterns[] = '%_' . $wpdb->esc_like($theme_template) . '_%';
        }
        
        if (!empty($theme_name_lower) && strlen($theme_name_lower) > 3) {
            $generic_names = array('theme', 'wordpress', 'wp', 'default', 'custom');
            if (!in_array($theme_name_lower, $generic_names)) {
                $option_patterns[] = '%' . $wpdb->esc_like($theme_name_lower) . '%';
            }
        }
        
        if (!empty($theme_text_domain)) {
            $option_patterns[] = $theme_text_domain . '_%';
            $option_patterns[] = '%_' . $wpdb->esc_like($theme_text_domain) . '_%';
        }
        
        if (empty($option_patterns)) {
            return $settings;
        }
        
        $where_clauses = array();
        $where_values = array();
        
        foreach ($option_patterns as $pattern) {
            $where_clauses[] = 'option_name LIKE %s';
            $where_values[] = $pattern;
        }
        
        $query = "SELECT option_name, option_value 
                  FROM {$wpdb->options} 
                  WHERE (" . implode(' OR ', $where_clauses) . ")
                  AND option_name NOT LIKE %s
                  AND option_name NOT LIKE %s
                  AND option_name NOT LIKE %s
                  LIMIT 200";
        
        $where_values[] = '%_transient%';
        $where_values[] = '%_cache%';
        $where_values[] = '%_site_transient%';
        
        $results = $wpdb->get_results($wpdb->prepare($query, $where_values));
        
        foreach ($results as $row) {
            $option_name_lower = strtolower($row->option_name);
            $is_active_theme_option = false;
            
            if (!empty($theme_slug) && strpos($option_name_lower, strtolower($theme_slug)) !== false) {
                $is_active_theme_option = true;
            } elseif (!empty($theme_template) && strpos($option_name_lower, strtolower($theme_template)) !== false) {
                $is_active_theme_option = true;
            } elseif (!empty($theme_text_domain) && strpos($option_name_lower, strtolower($theme_text_domain)) !== false) {
                $is_active_theme_option = true;
            }
            
            if (!$is_active_theme_option) {
                continue;
            }
            
            if (strpos($row->option_name, '_transient') !== false || 
                strpos($row->option_name, '_cache') !== false) {
                continue;
            }
            
            $value = maybe_unserialize($row->option_value);
            $option_url = $this->find_theme_option_page_url($row->option_name);
            
            if (is_array($value)) {
                foreach ($value as $key => $val) {
                    $settings[] = $this->normalize_setting(array(
                        'name' => $this->formatter->format_setting_name($key),
                        'description' => 'Theme option: ' . $row->option_name . '[' . $key . ']',
                        'path' => 'Theme Options > ' . $this->formatter->format_setting_name($row->option_name) . ' > ' . $this->formatter->format_setting_name($key),
                        'url' => $option_url,
                        'type' => 'theme_option',
                        'category' => 'appearance',
                        'keywords' => str_replace('_', ' ', $key . ' ' . $row->option_name) . ' theme option',
                        'source' => $theme->get('Name'),
                        'option_key' => $row->option_name . '[' . $key . ']'
                    ));
                }
            } else {
                $settings[] = $this->normalize_setting(array(
                    'name' => $this->formatter->format_setting_name($row->option_name),
                    'description' => 'Theme database option',
                    'path' => 'Theme Options > ' . $this->formatter->format_setting_name($row->option_name),
                    'url' => $option_url,
                    'type' => 'theme_option',
                    'category' => 'appearance',
                    'keywords' => str_replace('_', ' ', $row->option_name) . ' theme option database',
                    'source' => $theme->get('Name'),
                    'option_key' => $row->option_name
                ));
            }
        }
        
        return $settings;
    }
}

