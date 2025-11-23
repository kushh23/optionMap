<?php
/**
 * Page Templates Scanner class
 *
 * Scans available page templates
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
 * Page Templates Scanner class
 */
class OptionMap_Page_Templates_Scanner extends OptionMap_Scanner_Base {

    /**
     * Scan for settings
     *
     * @return array Array of settings
     */
    public function scan() {
        $settings = array();
        $theme = wp_get_theme();
        $templates = wp_get_theme()->get_page_templates();
        
        if (empty($templates) || !is_array($templates)) {
            return $settings;
        }
        
        foreach ($templates as $template_file => $template_name) {
            $settings[] = $this->normalize_setting(array(
                'name' => $template_name,
                'description' => 'Page template: ' . $template_file,
                'path' => 'Page > Page Attributes > Template',
                'url' => admin_url('edit.php?post_type=page'),
                'type' => 'page_template',
                'category' => 'appearance',
                'keywords' => 'template page layout ' . $template_name . ' ' . $template_file,
                'source' => $theme->get('Name'),
                'template_file' => $template_file
            ));
        }
        
        return $settings;
    }
}

