<?php
/**
 * Theme Modifications Scanner class
 *
 * Scans theme_mods for theme modification settings
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
 * Theme Modifications Scanner class
 */
class OptionMap_Theme_Mods_Scanner extends OptionMap_Scanner_Base {

    /**
     * Scan for settings
     *
     * @return array Array of settings
     */
    public function scan() {
        $settings = array();
        $theme = wp_get_theme();
        $theme_mods = get_theme_mods();
        
        if (empty($theme_mods) || !is_array($theme_mods)) {
            return $settings;
        }
        
        foreach ($theme_mods as $mod_key => $mod_value) {
            if (in_array($mod_key, array('nav_menu_locations', 'custom_css_post_id', 'background_image', 'header_image'))) {
                continue;
            }
            
            $settings[] = $this->normalize_setting(array(
                'name' => $this->formatter->format_setting_name($mod_key),
                'description' => 'Theme modification setting: ' . $mod_key,
                'path' => 'Appearance > Customize > Theme Modifications',
                'url' => admin_url('customize.php'),
                'type' => 'theme_mod',
                'category' => 'appearance',
                'keywords' => str_replace('_', ' ', $mod_key) . ' theme mod modification',
                'source' => $theme->get('Name'),
                'mod_key' => $mod_key
            ));
        }
        
        return $settings;
    }
}

