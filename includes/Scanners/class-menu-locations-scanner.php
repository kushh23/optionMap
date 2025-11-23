<?php
/**
 * Menu Locations Scanner class
 *
 * Scans registered navigation menu locations
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
 * Menu Locations Scanner class
 */
class OptionMap_Menu_Locations_Scanner extends OptionMap_Scanner_Base {

    /**
     * Scan for settings
     *
     * @return array Array of settings
     */
    public function scan() {
        $settings = array();
        $theme = wp_get_theme();
        $menu_locations = get_registered_nav_menus();
        
        if (empty($menu_locations)) {
            return $settings;
        }
        
        foreach ($menu_locations as $location => $description) {
            $settings[] = $this->normalize_setting(array(
                'name' => $description,
                'description' => 'Theme menu location: ' . $location,
                'path' => 'Appearance > Menus > Menu Locations',
                'url' => admin_url('nav-menus.php?action=locations'),
                'type' => 'menu_location',
                'category' => 'appearance',
                'keywords' => 'menu navigation location ' . $description . ' ' . $location,
                'source' => $theme->get('Name'),
                'location_id' => $location
            ));
        }
        
        return $settings;
    }
}

