<?php
/**
 * Dashboard Pages Scanner class
 *
 * Scans theme-related admin menu items
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
 * Dashboard Pages Scanner class
 */
class OptionMap_Dashboard_Pages_Scanner extends OptionMap_Scanner_Base {

    /**
     * Scan for settings
     *
     * @return array Array of settings
     */
    public function scan() {
        $settings = array();
        global $menu, $submenu;
        
        $theme = wp_get_theme();
        $theme_slug = get_option('stylesheet');
        $theme_template = get_option('template');
        $theme_text_domain = $theme->get('TextDomain');
        $theme_name = $theme->get('Name');
        
        $theme_identifiers = array();
        if (!empty($theme_slug)) {
            $theme_identifiers[] = strtolower($theme_slug);
        }
        if (!empty($theme_template) && $theme_template !== $theme_slug) {
            $theme_identifiers[] = strtolower($theme_template);
        }
        if (!empty($theme_text_domain) && $theme_text_domain !== $theme_slug && $theme_text_domain !== $theme_template) {
            $theme_identifiers[] = strtolower($theme_text_domain);
        }
        
        if (empty($theme_identifiers)) {
            return $settings;
        }
        
        // Scan top-level menu items
        if (is_array($menu)) {
            foreach ($menu as $menu_item) {
                if (!isset($menu_item[0]) || !isset($menu_item[2])) {
                    continue;
                }
                
                $menu_title = strip_tags($menu_item[0]);
                $menu_slug = $menu_item[2];
                $menu_slug_lower = strtolower($menu_slug);
                $menu_title_lower = strtolower($menu_title);
                
                $matches_theme = false;
                foreach ($theme_identifiers as $identifier) {
                    if (strpos($menu_slug_lower, $identifier) !== false || 
                        strpos($menu_title_lower, $identifier) !== false ||
                        strpos($menu_title_lower, strtolower($theme_name)) !== false) {
                        $matches_theme = true;
                        break;
                    }
                }
                
                if ($matches_theme) {
                    $settings[] = $this->normalize_setting(array(
                        'name' => $menu_title,
                        'description' => 'Theme dashboard page: ' . $menu_slug,
                        'path' => $menu_title,
                        'url' => admin_url($menu_slug),
                        'type' => 'dashboard_page',
                        'category' => 'dashboard_pages',
                        'keywords' => strtolower($menu_title . ' ' . $menu_slug . ' ' . $theme_name . ' dashboard page'),
                        'source' => $theme_name,
                        'menu_slug' => $menu_slug
                    ));
                }
            }
        }
        
        // Scan submenu items
        if (is_array($submenu)) {
            foreach ($submenu as $parent_slug => $submenu_items) {
                if (!is_array($submenu_items)) {
                    continue;
                }
                
                foreach ($submenu_items as $submenu_item) {
                    if (!isset($submenu_item[0]) || !isset($submenu_item[2])) {
                        continue;
                    }
                    
                    $submenu_title = strip_tags($submenu_item[0]);
                    $submenu_slug = $submenu_item[2];
                    $submenu_slug_lower = strtolower($submenu_slug);
                    $submenu_title_lower = strtolower($submenu_title);
                    $parent_slug_lower = strtolower($parent_slug);
                    
                    $matches_theme = false;
                    foreach ($theme_identifiers as $identifier) {
                        if (strpos($submenu_slug_lower, $identifier) !== false || 
                            strpos($submenu_title_lower, $identifier) !== false ||
                            strpos($parent_slug_lower, $identifier) !== false ||
                            strpos($submenu_title_lower, strtolower($theme_name)) !== false) {
                            $matches_theme = true;
                            break;
                        }
                    }
                    
                    if ($matches_theme) {
                        $parent_title = '';
                        if (is_array($menu)) {
                            foreach ($menu as $menu_item) {
                                if (isset($menu_item[2]) && $menu_item[2] === $parent_slug) {
                                    $parent_title = strip_tags($menu_item[0]);
                                    break;
                                }
                            }
                        }
                        
                        $menu_path = !empty($parent_title) ? $parent_title . ' > ' . $submenu_title : $submenu_title;
                        
                        if (strpos($submenu_slug, '.php') !== false) {
                            $submenu_url = admin_url($submenu_slug);
                        } else {
                            $submenu_url = admin_url('admin.php?page=' . $submenu_slug);
                        }
                        
                        $submenu_url = $this->url_validator->validate($submenu_url);
                        
                        $settings[] = $this->normalize_setting(array(
                            'name' => $submenu_title,
                            'description' => 'Theme dashboard submenu page: ' . $submenu_slug,
                            'path' => $menu_path,
                            'url' => $submenu_url,
                            'type' => 'dashboard_page',
                            'category' => 'dashboard_pages',
                            'keywords' => strtolower($submenu_title . ' ' . $submenu_slug . ' ' . $parent_title . ' ' . $theme_name . ' dashboard page'),
                            'source' => $theme_name,
                            'menu_slug' => $submenu_slug,
                            'parent_slug' => $parent_slug
                        ));
                    }
                }
            }
        }
        
        return $settings;
    }
}

