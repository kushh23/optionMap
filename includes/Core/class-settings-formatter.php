<?php
/**
 * Settings Formatter class
 *
 * Formats setting names and data for display
 *
 * @package OptionMap
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings Formatter class
 */
class OptionMap_Settings_Formatter {

    /**
     * Format setting name to be user-friendly
     *
     * @param string $name Raw setting name/ID
     * @return string Formatted name
     */
    public function format_setting_name($name) {
        // Remove common prefixes
        $name = preg_replace('/^(theme_|neve_|hestia_|astra_|ocean_|generate_|storefront_|twentytwenty_|twentytwentyone_|twentytwentytwo_|twentytwentythree_|twentytwentyfour_)/i', '', $name);
        
        // Replace underscores and hyphens with spaces
        $formatted = str_replace(array('_', '-'), ' ', $name);
        
        // Capitalize first letter of each word
        $formatted = ucwords($formatted);
        
        // Handle common abbreviations
        $formatted = str_replace('Id', 'ID', $formatted);
        $formatted = str_replace('Url', 'URL', $formatted);
        $formatted = str_replace('Css', 'CSS', $formatted);
        $formatted = str_replace('Js', 'JS', $formatted);
        $formatted = str_replace('Api', 'API', $formatted);
        
        return $formatted;
    }

    /**
     * Format settings for AI context
     *
     * @param array $settings All available settings
     * @param string $theme_name Active theme name
     * @param int $total_count Total number of settings
     * @return string Formatted context string
     */
    public function format_for_ai($settings, $theme_name = '', $total_count = 0) {
        // Count by category for summary
        $category_counts = array();
        $setting_types = array();
        foreach ($settings as $setting) {
            $cat = isset($setting['category']) ? $setting['category'] : 'general';
            $category_counts[$cat] = isset($category_counts[$cat]) ? $category_counts[$cat] + 1 : 1;
            
            $type = isset($setting['type']) ? $setting['type'] : 'unknown';
            $setting_types[$type] = isset($setting_types[$type]) ? $setting_types[$type] + 1 : 1;
        }
        
        $context = "=== WORDPRESS SETTINGS DATABASE ===\n\n";
        $context .= "Active Theme: " . ($theme_name ? $theme_name : 'Unknown') . "\n";
        $context .= "Total Available Settings: " . $total_count . "\n\n";
        
        $context .= "SETTINGS SUMMARY BY CATEGORY:\n";
        foreach ($category_counts as $cat => $count) {
            $context .= "- " . ucfirst($cat) . ": " . $count . " settings\n";
        }
        $context .= "\n";
        
        $context .= "SETTINGS SUMMARY BY TYPE:\n";
        foreach ($setting_types as $type => $count) {
            $context .= "- " . str_replace('_', ' ', $type) . ": " . $count . " settings\n";
        }
        $context .= "\n";
        
        $context .= "IMPORTANT: Below is a complete list of ALL available WordPress settings on this site.\n";
        $context .= "You MUST only answer questions about settings that appear in this list.\n";
        $context .= "When matching user questions, check:\n";
        $context .= "1. Setting name (exact or partial match)\n";
        $context .= "2. Description text\n";
        $context .= "3. Keywords/search terms\n";
        $context .= "4. Location path\n";
        $context .= "5. Setting type\n";
        $context .= "If a user asks about something NOT listed here, politely say it's not available.\n\n";
        $context .= "=== COMPLETE SETTINGS LIST ===\n\n";
        
        // Group by category for better organization
        $categories = array();
        foreach ($settings as $setting) {
            $cat = isset($setting['category']) ? $setting['category'] : 'general';
            if (!isset($categories[$cat])) {
                $categories[$cat] = array();
            }
            $categories[$cat][] = $setting;
        }
        
        // Sort categories for consistency
        ksort($categories);
        
        foreach ($categories as $cat_name => $cat_settings) {
            $context .= "━━━ " . strtoupper($cat_name) . " SETTINGS (" . count($cat_settings) . " settings) ━━━\n\n";
            
            foreach ($cat_settings as $index => $setting) {
                $setting_num = $index + 1;
                $context .= "SETTING #" . $setting_num . ": " . $setting['name'] . "\n";
                
                // Description (very important for matching)
                if (isset($setting['description']) && !empty($setting['description'])) {
                    $context .= "  • Description: " . $setting['description'] . "\n";
                }
                
                // Location/Path (critical for navigation)
                if (isset($setting['path']) && !empty($setting['path'])) {
                    $context .= "  • Location: " . $setting['path'] . "\n";
                }
                
                // Direct URL (CRITICAL - always include if available)
                if (isset($setting['url']) && !empty($setting['url'])) {
                    // Ensure URL is complete and properly formatted
                    $url = $setting['url'];
                    // If URL is relative (starts with wp-admin), make it absolute for AI context
                    if (strpos($url, 'wp-admin') === 0) {
                        $url = admin_url($url);
                    } elseif (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
                        $url = home_url($url);
                    }
                    $context .= "  • Direct URL: " . $url . " (USE THIS COMPLETE URL IN YOUR RESPONSE)\n";
                }
                
                // Keywords (important for matching user questions)
                if (isset($setting['keywords']) && !empty($setting['keywords'])) {
                    $context .= "  • Search Keywords: " . $setting['keywords'] . "\n";
                }
                
                // Setting ID/Type (for technical reference and matching)
                if (isset($setting['id']) && !empty($setting['id'])) {
                    $context .= "  • Setting ID: " . $setting['id'] . "\n";
                } elseif (isset($setting['control_id']) && !empty($setting['control_id'])) {
                    $context .= "  • Control ID: " . $setting['control_id'] . "\n";
                } elseif (isset($setting['option_key']) && !empty($setting['option_key'])) {
                    $context .= "  • Option Key: " . $setting['option_key'] . "\n";
                } elseif (isset($setting['panel_id']) && !empty($setting['panel_id'])) {
                    $context .= "  • Panel ID: " . $setting['panel_id'] . "\n";
                } elseif (isset($setting['section_id']) && !empty($setting['section_id'])) {
                    $context .= "  • Section ID: " . $setting['section_id'] . "\n";
                }
                
                // Type (for categorization)
                if (isset($setting['type']) && !empty($setting['type'])) {
                    $context .= "  • Type: " . $setting['type'] . "\n";
                }
                
                // Source (theme/core)
                if (isset($setting['source']) && !empty($setting['source'])) {
                    $context .= "  • Source: " . $setting['source'] . "\n";
                }
                
                $context .= "\n";
            }
            
            $context .= "\n";
        }
        
        $context .= "=== END OF SETTINGS LIST ===\n\n";
        $context .= "REMEMBER: Only reference settings that appear in the list above. ";
        $context .= "When a user asks about a setting, search through the list by name, description, keywords, or location. ";
        $context .= "CRITICAL URL FORMAT: Always include the COMPLETE Direct URL in your response using the format [URL:complete_url_here]. ";
        $context .= "The URL must be COMPLETE from http:// or https:// to the end, including ALL query parameters. ";
        $context .= "Example: [URL:http://yoursite.com/wp-admin/customize.php?autofocus[control]=neve_global_colors] ";
        $context .= "Do NOT split the URL - include the ENTIRE URL including brackets in query parameters.";
        
        return $context;
    }
}

