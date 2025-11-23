<?php
/**
 * Widget Areas Scanner class
 *
 * Scans registered widget areas (sidebars)
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
 * Widget Areas Scanner class
 */
class OptionMap_Widget_Areas_Scanner extends OptionMap_Scanner_Base {

    /**
     * Scan for settings
     *
     * @return array Array of settings
     */
    public function scan() {
        $settings = array();
        global $wp_registered_sidebars;
        $theme = wp_get_theme();
        
        if (empty($wp_registered_sidebars) || !is_array($wp_registered_sidebars)) {
            return $settings;
        }
        
        foreach ($wp_registered_sidebars as $sidebar_id => $sidebar) {
            if (in_array($sidebar_id, array('wp_inactive_widgets', 'array_version'))) {
                continue;
            }
            
            $settings[] = $this->normalize_setting(array(
                'name' => isset($sidebar['name']) ? $sidebar['name'] : $this->formatter->format_setting_name($sidebar_id),
                'description' => isset($sidebar['description']) ? $sidebar['description'] : 'Widget area',
                'path' => 'Appearance > Widgets > ' . (isset($sidebar['name']) ? $sidebar['name'] : $sidebar_id),
                'url' => admin_url('widgets.php'),
                'type' => 'widget_area',
                'category' => 'appearance',
                'keywords' => 'widget sidebar area ' . (isset($sidebar['name']) ? $sidebar['name'] : $sidebar_id) . ' ' . $sidebar_id,
                'source' => $theme->get('Name'),
                'sidebar_id' => $sidebar_id
            ));
        }
        
        return $settings;
    }
}

