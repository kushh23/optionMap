<?php
/**
 * Theme Support Scanner class
 *
 * Scans theme support features
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
 * Theme Support Scanner class
 */
class OptionMap_Theme_Support_Scanner extends OptionMap_Scanner_Base {

    /**
     * Scan for settings
     *
     * @return array Array of settings
     */
    public function scan() {
        $settings = array();
        $theme = wp_get_theme();
        
        $theme_supports = array(
            'custom-logo' => array(
                'name' => 'Custom Logo',
                'url' => $this->build_customizer_url('control', 'custom_logo')
            ),
            'custom-header' => array(
                'name' => 'Custom Header',
                'url' => $this->build_customizer_url('control', 'header_image')
            ),
            'custom-background' => array(
                'name' => 'Custom Background',
                'url' => $this->build_customizer_url('control', 'background_image')
            ),
            'post-thumbnails' => array(
                'name' => 'Featured Images',
                'url' => admin_url('options-media.php')
            ),
            'title-tag' => array(
                'name' => 'Title Tag Support',
                'url' => admin_url('options-general.php')
            ),
            'automatic-feed-links' => array(
                'name' => 'Automatic Feed Links',
                'url' => admin_url('options-reading.php')
            ),
            'html5' => array(
                'name' => 'HTML5 Support',
                'url' => admin_url('themes.php')
            ),
            'post-formats' => array(
                'name' => 'Post Formats',
                'url' => admin_url('options-writing.php')
            ),
        );
        
        foreach ($theme_supports as $feature => $info) {
            if (current_theme_supports($feature)) {
                $settings[] = $this->normalize_setting(array(
                    'name' => $info['name'] . ' (Enabled)',
                    'description' => 'Theme support feature: ' . $feature,
                    'path' => 'Theme Features > ' . $info['name'],
                    'url' => $info['url'],
                    'type' => 'theme_support',
                    'category' => 'appearance',
                    'keywords' => 'theme support feature ' . str_replace('-', ' ', $feature),
                    'source' => $theme->get('Name'),
                    'feature' => $feature
                ));
            }
        }
        
        return $settings;
    }
}

