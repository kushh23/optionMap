<?php
/**
 * Block Patterns Scanner class
 *
 * Scans registered block patterns from active theme
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
 * Block Patterns Scanner class
 */
class OptionMap_Block_Patterns_Scanner extends OptionMap_Scanner_Base {

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
        
        if (!function_exists('register_block_pattern') && !class_exists('WP_Block_Patterns_Registry')) {
            return $settings;
        }
        
        $patterns = array();
        
        if (class_exists('WP_Block_Patterns_Registry')) {
            $registry = WP_Block_Patterns_Registry::get_instance();
            if (method_exists($registry, 'get_all_registered')) {
                $all_patterns = $registry->get_all_registered();
                
                foreach ($all_patterns as $pattern) {
                    if (!isset($pattern['name'])) {
                        continue;
                    }
                    
                    $pattern_slug = $pattern['name'];
                    $belongs_to_active_theme = false;
                    $theme_namespace = '';
                    
                    if (strpos($pattern_slug, '/') !== false) {
                        $parts = explode('/', $pattern_slug, 2);
                        $theme_namespace = $parts[0];
                        $pattern_name = $parts[1];
                        
                        if (strtolower($theme_namespace) === strtolower($theme_slug) ||
                            strtolower($theme_namespace) === strtolower($theme_template) ||
                            (!empty($theme_text_domain) && strtolower($theme_namespace) === strtolower($theme_text_domain))) {
                            $belongs_to_active_theme = true;
                        }
                    } else {
                        continue;
                    }
                    
                    if ($belongs_to_active_theme) {
                        $patterns[] = array(
                            'name' => isset($pattern['title']) ? $pattern['title'] : $pattern['name'],
                            'description' => isset($pattern['description']) ? $pattern['description'] : 'Block pattern',
                            'slug' => $pattern['name'],
                            'categories' => isset($pattern['categories']) ? $pattern['categories'] : array(),
                            'namespace' => $theme_namespace
                        );
                    }
                }
            }
        }
        
        foreach ($patterns as $pattern) {
            $pattern_slug = $pattern['slug'];
            $theme_namespace = $pattern['namespace'];
            
            $settings[] = $this->normalize_setting(array(
                'name' => 'Block Pattern: ' . $pattern['name'],
                'description' => $pattern['description'],
                'path' => 'Block Editor > Patterns > ' . ucfirst($theme_namespace),
                'url' => admin_url('post-new.php?post_type=page'),
                'type' => 'block_pattern',
                'category' => 'patterns',
                'keywords' => 'pattern block ' . strtolower($pattern['name']) . ' ' . $pattern_slug,
                'source' => $theme->get('Name'),
                'pattern_slug' => $pattern_slug
            ));
        }
        
        return $settings;
    }
}

