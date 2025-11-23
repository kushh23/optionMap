<?php
/**
 * Post Metaboxes Scanner class
 *
 * Scans post meta for theme-specific metabox options
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
 * Post Metaboxes Scanner class
 */
class OptionMap_Post_Metaboxes_Scanner extends OptionMap_Scanner_Base {

    /**
     * Scan for settings
     *
     * @return array Array of settings
     */
    public function scan() {
        $settings = array();
        $theme = wp_get_theme();
        $theme_slug = get_option('stylesheet');
        $theme_template = get_option('template');
        $theme_text_domain = $theme->get('TextDomain');
        
        global $wpdb;
        
        $where_patterns = array();
        $where_values = array();
        
        if (!empty($theme_slug)) {
            $where_patterns[] = 'meta_key LIKE %s';
            $where_values[] = $theme_slug . '_%';
        }
        
        if (!empty($theme_template) && $theme_template !== $theme_slug) {
            $where_patterns[] = 'meta_key LIKE %s';
            $where_values[] = $theme_template . '_%';
        }
        
        if (!empty($theme_text_domain)) {
            $where_patterns[] = 'meta_key LIKE %s';
            $where_values[] = $theme_text_domain . '_%';
        }
        
        if (empty($where_patterns)) {
            return $settings;
        }
        
        $query = "SELECT DISTINCT meta_key 
                 FROM {$wpdb->postmeta} 
                 WHERE (" . implode(' OR ', $where_patterns) . ")
                 AND meta_key NOT LIKE %s
                 AND meta_key NOT LIKE %s
                 AND meta_key NOT LIKE %s
                 AND meta_key NOT LIKE %s
                 LIMIT 50";
        
        $where_values[] = '_edit_%';
        $where_values[] = '_wp_%';
        $where_values[] = 'elementor_%';
        $where_values[] = 'beaver_%';
        
        $meta_keys_found = $wpdb->get_col($wpdb->prepare($query, $where_values));
        
        foreach ($meta_keys_found as $meta_key) {
            $meta_key_lower = strtolower($meta_key);
            $belongs_to_active_theme = false;
            
            if (!empty($theme_slug) && strpos($meta_key_lower, strtolower($theme_slug)) === 0) {
                $belongs_to_active_theme = true;
            } elseif (!empty($theme_template) && strpos($meta_key_lower, strtolower($theme_template)) === 0) {
                $belongs_to_active_theme = true;
            } elseif (!empty($theme_text_domain) && strpos($meta_key_lower, strtolower($theme_text_domain)) === 0) {
                $belongs_to_active_theme = true;
            }
            
            if (!$belongs_to_active_theme) {
                continue;
            }
            
            if (in_array($meta_key, array('_edit_lock', '_edit_last', '_wp_page_template', '_thumbnail_id', '_wp_attachment_metadata'))) {
                continue;
            }
            
            $settings[] = $this->normalize_setting(array(
                'name' => $this->formatter->format_setting_name($meta_key),
                'description' => 'Post/Page metabox option: ' . $meta_key,
                'path' => 'Post/Page Editor > Theme Options > ' . $this->formatter->format_setting_name($meta_key),
                'url' => admin_url('edit.php?post_type=post'),
                'type' => 'post_metabox',
                'category' => 'content',
                'keywords' => 'metabox meta ' . str_replace('_', ' ', $meta_key) . ' post page',
                'source' => $theme->get('Name'),
                'meta_key' => $meta_key
            ));
        }
        
        return $settings;
    }
}

