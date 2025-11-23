<?php
/**
 * Customizer Scanner class
 *
 * Scans WordPress Customizer for panels, sections, and controls
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
 * Customizer Scanner class
 */
class OptionMap_Customizer_Scanner extends OptionMap_Scanner_Base {

    /**
     * Scan for settings
     *
     * @return array Array of settings
     */
    public function scan() {
        $settings = array();
        
        ob_start();
        
        try {
            if (!class_exists('WP_Customize_Manager')) {
                ob_end_clean();
                return $settings;
            }
            
            global $wp_customize;
            
            if (!is_admin() && !($wp_customize instanceof WP_Customize_Manager)) {
                ob_end_clean();
                return $settings;
            }
            
            if ($wp_customize instanceof WP_Customize_Manager) {
                $theme = wp_get_theme();
                
                // Scan panels
                try {
                    $panels = $wp_customize->panels();
                    foreach ($panels as $panel_id => $panel) {
                        try {
                            if (in_array($panel_id, array('nav_menus', 'widgets'))) {
                                continue;
                            }
                            
                            if (isset($panel->title)) {
                                $settings[] = $this->normalize_setting(array(
                                    'name' => $panel->title . ' (Panel)',
                                    'description' => isset($panel->description) ? $panel->description : 'Customizer panel',
                                    'path' => 'Appearance > Customize > ' . $panel->title,
                                    'url' => $this->build_customizer_url('panel', $panel_id),
                                    'type' => 'customizer_panel',
                                    'category' => 'appearance',
                                    'keywords' => strtolower($panel->title . ' panel ' . $panel_id),
                                    'source' => $theme->get('Name'),
                                    'panel_id' => $panel_id
                                ));
                            }
                        } catch (Exception $e) {
                            continue;
                        } catch (Error $e) {
                            continue;
                        }
                    }
                } catch (Exception $e) {
                    // Continue
                } catch (Error $e) {
                    // Continue
                }
                
                // Scan sections
                try {
                    $sections = $wp_customize->sections();
                    foreach ($sections as $section_id => $section) {
                        try {
                            if (in_array($section_id, array('title_tagline', 'colors', 'header_image', 'background_image', 'nav', 'static_front_page'))) {
                                continue;
                            }
                            
                            $path = 'Appearance > Customize';
                            $panel_id_for_url = '';
                            
                            if (isset($section->panel) && $section->panel) {
                                $panel = $wp_customize->get_panel($section->panel);
                                if ($panel && isset($panel->title)) {
                                    $path .= ' > ' . $panel->title;
                                    $panel_id_for_url = $section->panel;
                                }
                            }
                            
                            if (isset($section->title)) {
                                $path .= ' > ' . $section->title;
                            }
                            
                            $settings[] = $this->normalize_setting(array(
                                'name' => (isset($section->title) ? $section->title : $this->formatter->format_setting_name($section_id)) . ' (Section)',
                                'description' => isset($section->description) ? $section->description : 'Customizer section',
                                'path' => $path,
                                'url' => $this->build_customizer_url('section', $section_id, $panel_id_for_url),
                                'type' => 'customizer_section',
                                'category' => 'appearance',
                                'keywords' => strtolower((isset($section->title) ? $section->title : $section_id) . ' section ' . $section_id),
                                'source' => $theme->get('Name'),
                                'section_id' => $section_id
                            ));
                        } catch (Exception $e) {
                            continue;
                        } catch (Error $e) {
                            continue;
                        }
                    }
                } catch (Exception $e) {
                    // Continue
                } catch (Error $e) {
                    // Continue
                }
                
                // Scan controls
                try {
                    $controls = $wp_customize->controls();
                    foreach ($controls as $control_id => $control) {
                        if (in_array($control_id, array('blogname', 'blogdescription', 'site_icon', 'show_on_front', 'page_on_front', 'page_for_posts'))) {
                            continue;
                        }
                        
                        if (strpos($control_id, 'nav_') === 0 || 
                            strpos($control_id, 'widget_') === 0 || 
                            strpos($control_id, 'sidebars_') === 0) {
                            continue;
                        }
                        
                        try {
                            $section = null;
                            if (isset($control->section) && $control->section) {
                                $section = $wp_customize->get_section($control->section);
                            }
                            
                            $path = 'Appearance > Customize';
                            $panel_id_for_url = '';
                            $section_id_for_url = '';
                            
                            if ($section && is_object($section)) {
                                if (isset($section->panel) && $section->panel) {
                                    $panel = $wp_customize->get_panel($section->panel);
                                    if ($panel && is_object($panel) && isset($panel->title)) {
                                        $path .= ' > ' . $panel->title;
                                        $panel_id_for_url = $section->panel;
                                    }
                                }
                                if (isset($section->title)) {
                                    $path .= ' > ' . $section->title;
                                }
                                if (isset($section->id)) {
                                    $section_id_for_url = $section->id;
                                }
                            }
                            
                            $control_name = (isset($control->label) && $control->label) ? $control->label : $this->formatter->format_setting_name($control_id);
                            
                            $settings[] = $this->normalize_setting(array(
                                'name' => $control_name,
                                'description' => (isset($control->description) && $control->description) ? $control->description : 'Theme customizer setting',
                                'path' => $path,
                                'url' => $this->build_customizer_url('control', $control_id, $panel_id_for_url, $section_id_for_url),
                                'type' => 'customizer_control',
                                'category' => 'appearance',
                                'keywords' => strtolower($control_name . ' ' . $control_id),
                                'source' => $theme->get('Name'),
                                'control_id' => $control_id
                            ));
                        } catch (Exception $e) {
                            continue;
                        } catch (Error $e) {
                            continue;
                        }
                    }
                } catch (Exception $e) {
                    // Continue
                } catch (Error $e) {
                    // Continue
                }
            }
        } catch (Exception $e) {
            // Silently fail
        } catch (Error $e) {
            // Silently fail
        } catch (Throwable $e) {
            // Catch any other throwable
        }
        
        ob_end_clean();
        
        return $settings;
    }
}

