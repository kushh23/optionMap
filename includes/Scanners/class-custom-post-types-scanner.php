<?php
/**
 * Custom Post Types Scanner class
 *
 * Scans registered custom post types
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
 * Custom Post Types Scanner class
 */
class OptionMap_Custom_Post_Types_Scanner extends OptionMap_Scanner_Base {

    /**
     * Scan for settings
     *
     * @return array Array of settings
     */
    public function scan() {
        $settings = array();
        $theme = wp_get_theme();
        
        $post_types = get_post_types(array('public' => true, '_builtin' => false), 'objects');
        
        if (empty($post_types)) {
            return $settings;
        }
        
        foreach ($post_types as $post_type => $post_type_obj) {
            if (in_array($post_type, array('post', 'page', 'attachment', 'revision', 'nav_menu_item'))) {
                continue;
            }
            
            $settings[] = $this->normalize_setting(array(
                'name' => isset($post_type_obj->labels->name) ? $post_type_obj->labels->name : $this->formatter->format_setting_name($post_type),
                'description' => isset($post_type_obj->description) ? $post_type_obj->description : 'Custom post type: ' . $post_type,
                'path' => 'Content > ' . (isset($post_type_obj->labels->name) ? $post_type_obj->labels->name : $post_type),
                'url' => admin_url('edit.php?post_type=' . $post_type),
                'type' => 'custom_post_type',
                'category' => 'content',
                'keywords' => 'post type cpt ' . strtolower($post_type) . ' ' . (isset($post_type_obj->labels->name) ? strtolower($post_type_obj->labels->name) : ''),
                'source' => isset($post_type_obj->_theme) ? $post_type_obj->_theme : $theme->get('Name'),
                'post_type' => $post_type
            ));
        }
        
        return $settings;
    }
}

