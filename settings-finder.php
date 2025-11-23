<?php
/**
 * Plugin Name: Option Map
 * Plugin URI: https://themeisle.com/option-map
 * Description: Find any WordPress setting in seconds. Stop hunting through menus - search and discover all settings in one place.
 * Version: 1.0.0
 * Author: Support Ninjas
 * License: GPL v2 or later
 * Text Domain: option-map
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SF_VERSION', '1.0.0');
define('SF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SF_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
class SettingsFinder {
    
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     * 
     * Note: Most hooks are now handled by OptionMap_Plugin to prevent duplicates.
     * This class is kept for backward compatibility and method access.
     */
    private function init_hooks() {
        // Activation/deactivation hooks are handled by OptionMap_Plugin
        // Admin menu registration moved to OptionMap_Plugin to prevent duplicates
        // Asset enqueuing is handled by OptionMap_Plugin
        // AJAX handlers are handled by OptionMap_Plugin
        
        // Only keep init hook if needed for legacy compatibility
        add_action('init', array($this, 'init'));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        $this->create_database_table();
        update_option('sf_version', SF_VERSION);
        update_option('sf_last_scan', current_time('timestamp'));
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Cleanup if needed
    }
    
    /**
     * Create database table
     */
    private function create_database_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'settings_finder';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text NOT NULL,
            path varchar(255) NOT NULL,
            url varchar(255) NOT NULL,
            type varchar(50) NOT NULL,
            category varchar(50) NOT NULL,
            keywords text,
            option_name varchar(255),
            source varchar(255),
            last_updated datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY category (category),
            KEY type (type)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize components
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Option Map', 'option-map'),
            __('Option Map', 'option-map'),
            'manage_options',
            'settings-finder',
            array($this, 'render_admin_page'),
            'dashicons-search',
            75
        );
        
        // Add test submenu
        add_submenu_page(
            'settings-finder',
            'Test Scanner',
            'Test Scanner',
            'manage_options',
            'sf-test-scanner',
            array($this, 'render_test_page')
        );
        
        // Add theme settings submenu
        add_submenu_page(
            'settings-finder',
            'Theme Settings',
            'Theme Settings',
            'manage_options',
            'sf-theme-settings',
            array($this, 'render_theme_settings_page')
        );
        
        // Add AI Assisted Search submenu
        add_submenu_page(
            'settings-finder',
            'AI Assisted Search',
            'AI Assisted Search',
            'manage_options',
            'sf-ai-search',
            array($this, 'render_ai_search_page')
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'settings-finder') === false && strpos($hook, 'sf-') === false) {
            return;
        }
        
        wp_enqueue_style(
            'sf-admin-style',
            SF_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            SF_VERSION
        );
        
        wp_enqueue_script(
            'sf-admin-script',
            SF_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            SF_VERSION,
            true
        );
        
        // Media uploader no longer needed - logo is now embedded
        
        // Enqueue AI search assets only on AI search page
        if (strpos($hook, 'sf-ai-search') !== false) {
            wp_enqueue_style(
                'sf-ai-style',
                SF_PLUGIN_URL . 'assets/css/ai-search.css',
                array('sf-admin-style'),
                SF_VERSION
            );
            
            wp_enqueue_script(
                'sf-ai-script',
                SF_PLUGIN_URL . 'assets/js/ai-search.js',
                array('jquery', 'sf-admin-script'),
                SF_VERSION,
                true
            );
        }
        
        wp_localize_script('sf-admin-script', 'sf_ajax', array(
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sf_ajax_nonce'),
            'adminUrl' => admin_url()
        ));
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        // Get all settings including theme settings
        // Note: scan_all_theme_options() already includes core settings via scan_core_wordpress_settings()
        // So we don't need to call get_core_settings() separately
        $theme_settings = $this->scan_all_theme_options();
        $all_settings = $theme_settings;
        
        // Validate all URLs in settings to fix any invalid ones
        $all_settings = $this->validate_settings_urls($all_settings);
        
        // Count core vs theme settings for display
        $core_count = 0;
        $theme_count = 0;
        foreach ($all_settings as $setting) {
            if (isset($setting['type']) && $setting['type'] === 'core_setting') {
                $core_count++;
            } else {
                $theme_count++;
            }
        }
        
        $categories = $this->get_categories($all_settings);
        $logo_url = SF_PLUGIN_URL . 'assets/logo/logo.png';
        ?>
        <div class="wrap settings-finder-wrap">
            <!-- Welcome Header -->
            <header class="sf-welcome-header">
                <div class="sf-header-container">
                    <div class="sf-logo-section">
                        <div class="sf-logo-pill">
                            <div class="sf-logo-icon">
                                <img src="<?php echo esc_url($logo_url); ?>" alt="Option Map Logo" class="sf-logo-icon-img">
                            </div>
                            <span class="sf-logo-text">OptionMap</span>
                        </div>
                    </div>
                    <div class="sf-hero-content">
                        <h1 class="sf-hero-title">🔍 Find Any WordPress Setting in Seconds</h1>
                        <p class="sf-hero-subtitle">Stop hunting through menus! Search or browse all your WordPress settings in one friendly place.</p>
                    </div>
                </div>
            </header>

            <!-- Search Section -->
            <section class="sf-search-section">
                <div class="sf-search-card">
                    <span class="sf-search-stats">
                        Found <strong><?php echo count($all_settings); ?></strong> total settings 
                        (<?php echo $core_count; ?> core, <?php echo $theme_count; ?> theme)
                    </span>
                    
                    <div class="sf-search-label">
                        What setting are you looking for?
                        <span class="sf-search-hint">Try "logo", "comments", "homepage" or "permalinks"</span>
                    </div>
                    
                    <div class="sf-search-input-group">
                        <input type="text" class="sf-search-input" id="sf-search-input" 
                               placeholder="Type anything... header, colors, SEO, comments, footer...">
                        <button class="sf-search-button" onclick="SF_Admin.performSearch()" type="button">
                            <span class="sf-search-icon">🔍</span>
                            <span class="sf-search-text">Search</span>
                        </button>
                    </div>
                    
                    <!-- Quick Access -->
                    <div class="sf-quick-access">
                        <div class="sf-quick-card" onclick="SF_Admin.quickSearch('logo')">
                            <div class="sf-quick-icon">🎨</div>
                            <div class="sf-quick-title">Logo & Title</div>
                        </div>
                        <div class="sf-quick-card" onclick="SF_Admin.quickSearch('comments')">
                            <div class="sf-quick-icon">💬</div>
                            <div class="sf-quick-title">Comments</div>
                        </div>
                        <div class="sf-quick-card" onclick="SF_Admin.quickSearch('homepage')">
                            <div class="sf-quick-icon">🏠</div>
                            <div class="sf-quick-title">Homepage</div>
                        </div>
                        <div class="sf-quick-card" onclick="SF_Admin.quickSearch('seo')">
                            <div class="sf-quick-icon">🔍</div>
                            <div class="sf-quick-title">SEO</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Main Grid -->
            <div class="sf-main-grid">
                <!-- Categories Sidebar -->
                <aside class="sf-categories-card">
                    <h3 class="sf-card-title">📂 Browse by Category</h3>
                    <ul class="sf-category-list">
                        <?php foreach ($categories as $cat_key => $cat_data): ?>
                        <li class="sf-category-item <?php echo $cat_key === 'all' ? 'active' : ''; ?>" 
                            onclick="SF_Admin.selectCategory(this, '<?php echo $cat_key; ?>')">
                            <span class="sf-category-label">
                                <span class="sf-category-emoji"><?php echo $cat_data['emoji']; ?></span>
                                <span><?php echo $cat_data['label']; ?></span>
                            </span>
                            <span class="sf-category-count"><?php echo $cat_data['count']; ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </aside>

                <!-- Results Area -->
                <section class="sf-results-area">
                    <div class="sf-results-header">
                        <div>
                            <div class="sf-results-title">All Settings</div>
                            <div class="sf-results-subtitle">Click any setting to go directly to it</div>
                        </div>
                    </div>

                    <div class="sf-settings-grid" id="sf-settings-grid">
                        <?php foreach ($all_settings as $setting): ?>
                        <div class="sf-setting-card" data-category="<?php echo esc_attr($setting['category']); ?>"
                             data-keywords="<?php echo esc_attr($setting['keywords'] ?? ''); ?>">
                            <div class="sf-setting-header">
                                <div>
                                    <div class="sf-setting-title"><?php echo esc_html($setting['name']); ?></div>
                                    <div class="sf-setting-location">📍 <?php echo esc_html($setting['path']); ?></div>
                                </div>
                                <span class="sf-setting-badge sf-badge-<?php echo esc_attr($setting['category']); ?>">
                                    <?php echo ucfirst(esc_html($setting['category'])); ?>
                                </span>
                            </div>
                            <div class="sf-setting-description">
                                <?php echo esc_html($setting['description']); ?>
                            </div>
                            <div class="sf-setting-footer">
                                <span class="sf-setting-source"><?php echo esc_html($setting['source']); ?></span>
                                <a href="<?php echo esc_url($setting['url']); ?>" class="sf-go-to-setting">
                                    Go to Setting →
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="sf-empty-state" style="display: none;">
                        <div class="sf-empty-icon">🔍</div>
                        <div class="sf-empty-title">No settings found</div>
                        <div class="sf-empty-text">Try different keywords or browse categories</div>
                    </div>
                </section>
            </div>
        </div>
        
        <style>
        .sf-stats {
            margin-top: 15px;
            padding: 10px 15px;
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
            font-size: 14px;
        }
        .sf-stats strong {
            color: #fff;
            font-size: 18px;
        }
        </style>
        <?php
    }
    
    /**
     * Render test page
     */
    public function render_test_page() {
        ?>
        <div class="wrap">
            <h1>🧪 Theme Scanner Test Results</h1>
            <p>Testing theme: <strong><?php echo wp_get_theme()->get('Name'); ?></strong></p>
            
            <?php
            $start_time = microtime(true);
            $settings = $this->scan_all_theme_options();
            $end_time = microtime(true);
            $scan_time = round($end_time - $start_time, 2);
            
            // Validate all URLs in settings to fix any invalid ones
            $settings = $this->validate_settings_urls($settings);
            
            // Group by type
            $grouped = array();
            foreach ($settings as $setting) {
                $type = $setting['type'] ?? 'unknown';
                if (!isset($grouped[$type])) {
                    $grouped[$type] = array();
                }
                $grouped[$type][] = $setting;
            }
            ?>
            
            <div style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 5px;">
                <h2>📊 Results</h2>
                <p>
                    <strong>Total Settings:</strong> <?php echo count($settings); ?><br>
                    <strong>Types Found:</strong> <?php echo count($grouped); ?><br>
                    <strong>Scan Time:</strong> <?php echo $scan_time; ?> seconds
                </p>
                
                <h3>By Type:</h3>
                <ul>
                    <?php foreach ($grouped as $type => $items): ?>
                    <li><?php echo ucwords(str_replace('_', ' ', $type)); ?>: <?php echo count($items); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <h2>Sample Settings (First 20):</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Path</th>
                        <th>Type</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $samples = array_slice($settings, 0, 20);
                    foreach ($samples as $setting): 
                    ?>
                    <tr>
                        <td><?php echo esc_html($setting['name']); ?></td>
                        <td><?php echo esc_html($setting['path']); ?></td>
                        <td><?php echo esc_html($setting['type']); ?></td>
                        <td>
                            <a href="<?php echo esc_url($setting['url']); ?>" class="button button-small">View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Render theme settings page
     */
    public function render_theme_settings_page() {
        $theme = wp_get_theme();
        $all_settings = $this->scan_all_theme_options();
        // Validate all URLs in settings to fix any invalid ones
        $all_settings = $this->validate_settings_urls($all_settings);
        
        // Filter out core settings - only show theme-specific settings
        $settings = array();
        foreach ($all_settings as $setting) {
            if (!isset($setting['type']) || $setting['type'] !== 'core_setting') {
                $settings[] = $setting;
            }
        }
        ?>
        <div class="wrap">
            <h1>🎨 Theme Settings: <?php echo $theme->get('Name'); ?></h1>
            <p>Found <strong><?php echo count($settings); ?></strong> theme settings and options.</p>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Setting Name</th>
                        <th>Location</th>
                        <th>Type</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($settings as $setting): ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($setting['name']); ?></strong><br>
                            <small><?php echo esc_html($setting['description']); ?></small>
                        </td>
                        <td><?php echo esc_html($setting['path']); ?></td>
                        <td>
                            <span class="badge"><?php echo esc_html($setting['type']); ?></span>
                        </td>
                        <td>
                            <a href="<?php echo esc_url($setting['url']); ?>" class="button button-small">
                                Go to Setting →
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <style>
            .badge {
                display: inline-block;
                padding: 3px 8px;
                background: #f0f0f1;
                border-radius: 3px;
                font-size: 12px;
            }
        </style>
        <?php
    }
    
    /**
     * Get core WordPress settings
     * 
     * @return array Core WordPress settings
     */
    public function get_core_settings() {
        return array(
            array(
                'name' => 'Site Title',
                'description' => 'The name of your website displayed in browser tabs and search results',
                'path' => 'Settings > General',
                'url' => admin_url('options-general.php'),
                'category' => 'general',
                'keywords' => 'title, site name, website name',
                'source' => 'WordPress Core'
            ),
            array(
                'name' => 'Tagline',
                'description' => 'A short description of your website',
                'path' => 'Settings > General',
                'url' => admin_url('options-general.php'),
                'category' => 'general',
                'keywords' => 'tagline, description, slogan',
                'source' => 'WordPress Core'
            ),
            array(
                'name' => 'Homepage Display',
                'description' => 'Choose what shows on your homepage',
                'path' => 'Settings > Reading',
                'url' => admin_url('options-reading.php'),
                'category' => 'appearance',
                'keywords' => 'homepage, front page, static page',
                'source' => 'WordPress Core'
            ),
            array(
                'name' => 'Allow Comments',
                'description' => 'Allow people to submit comments on new posts',
                'path' => 'Settings > Discussion',
                'url' => admin_url('options-discussion.php'),
                'category' => 'content',
                'keywords' => 'comments, discussion, disable comments',
                'source' => 'WordPress Core'
            ),
            array(
                'name' => 'Permalink Structure',
                'description' => 'Choose the URL structure for your posts',
                'path' => 'Settings > Permalinks',
                'url' => admin_url('options-permalink.php'),
                'category' => 'seo',
                'keywords' => 'permalinks, urls, slug, seo',
                'source' => 'WordPress Core'
            ),
            array(
                'name' => 'Search Engine Visibility',
                'description' => 'Discourage search engines from indexing this site',
                'path' => 'Settings > Reading',
                'url' => admin_url('options-reading.php'),
                'category' => 'seo',
                'keywords' => 'seo, google, search engines, robots',
                'source' => 'WordPress Core'
            ),
            array(
                'name' => 'Media Sizes',
                'description' => 'Set default sizes for images',
                'path' => 'Settings > Media',
                'url' => admin_url('options-media.php'),
                'category' => 'media',
                'keywords' => 'images, thumbnails, media sizes',
                'source' => 'WordPress Core'
            )
        );
    }
    
    /**
     * Scan all theme options - Universal theme-agnostic scanner
     * 
     * This method dynamically detects all theme features without hardcoding
     * any theme-specific checks. Works with all WordPress themes.
     * 
     * @return array All discovered settings
     */
    public function scan_all_theme_options() {
        $all_settings = array();
        
        // 1. Scan Customizer (Panels, Sections, Controls) - Universal
        $all_settings = array_merge($all_settings, $this->scan_customizer_comprehensive());
        
        // 2. Scan Theme Modifications (theme_mods)
        $all_settings = array_merge($all_settings, $this->scan_theme_modifications());
        
        // 3. Scan Theme Options from Database
        $all_settings = array_merge($all_settings, $this->scan_theme_database_options());
        
        // 4. Scan Menu Locations
        $all_settings = array_merge($all_settings, $this->scan_menu_locations());
        
        // 5. Scan Widget Areas (Sidebars)
        $all_settings = array_merge($all_settings, $this->scan_widget_areas());
        
        // 6. Scan Page Templates
        $all_settings = array_merge($all_settings, $this->scan_page_templates());
        
        // 7. Scan Block Patterns (All themes)
        $all_settings = array_merge($all_settings, $this->scan_block_patterns());
        
        // 8. Scan Custom Post Types
        $all_settings = array_merge($all_settings, $this->scan_custom_post_types());
        
        // 9. Scan Post/Page Metaboxes (Generic detection)
        $all_settings = array_merge($all_settings, $this->scan_post_metaboxes());
        
        // 10. Scan Theme Support Features
        $all_settings = array_merge($all_settings, $this->scan_theme_support_features());
        
        // 11. Scan Core WordPress Settings
        $all_settings = array_merge($all_settings, $this->scan_core_wordpress_settings());
        
        // 12. Scan Dashboard Pages (Theme-related admin menu items)
        $all_settings = array_merge($all_settings, $this->scan_dashboard_pages());
        
        return $all_settings;
    }
    
    /**
     * Comprehensive Customizer Scanner
     * Scans all panels, sections, and controls dynamically using WordPress Customizer API
     * Works with ALL themes - no hardcoded theme checks
     * 
     * @return array Customizer settings
     */
    private function scan_customizer_comprehensive() {
        $settings = array();
        
        // Use output buffering and error suppression to prevent fatal errors
        ob_start();
        
        try {
            // Check if we can safely initialize customizer
            if (!class_exists('WP_Customize_Manager')) {
                ob_end_clean();
                return $settings;
            }
            
            global $wp_customize;
            
            // Don't try to initialize if we're not in admin or if it already exists
            if (!is_admin() && !($wp_customize instanceof WP_Customize_Manager)) {
                ob_end_clean();
                return $settings;
            }
            
            // If customizer already exists, use it
            if ($wp_customize instanceof WP_Customize_Manager) {
                $theme = wp_get_theme();
                
                // Scan panels
                try {
                    $panels = $wp_customize->panels();
                    foreach ($panels as $panel_id => $panel) {
                        try {
                            // Skip core WordPress panels if needed
                            if (in_array($panel_id, array('nav_menus', 'widgets'))) {
                                continue;
                            }
                            
                            if (isset($panel->title)) {
                                $settings[] = array(
                                    'name' => $panel->title . ' (Panel)',
                                    'description' => isset($panel->description) ? $panel->description : 'Customizer panel',
                                    'path' => 'Appearance > Customize > ' . $panel->title,
                                    'url' => $this->build_customizer_url('panel', $panel_id),
                                    'type' => 'customizer_panel',
                                    'category' => 'appearance',
                                    'keywords' => strtolower($panel->title . ' panel ' . $panel_id),
                                    'source' => $theme->get('Name'),
                                    'panel_id' => $panel_id
                                );
                            }
                        } catch (Exception $e) {
                            continue;
                        } catch (Error $e) {
                            continue;
                        }
                    }
                } catch (Exception $e) {
                    // Panels scanning failed, continue
                } catch (Error $e) {
                    // Panels scanning failed, continue
                }
                
                // Scan sections
                try {
                    $sections = $wp_customize->sections();
                    foreach ($sections as $section_id => $section) {
                        try {
                            // Skip core WordPress sections
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
                            
                            $settings[] = array(
                                'name' => (isset($section->title) ? $section->title : $this->format_setting_name($section_id)) . ' (Section)',
                                'description' => isset($section->description) ? $section->description : 'Customizer section',
                                'path' => $path,
                                'url' => $this->build_customizer_url('section', $section_id, $panel_id_for_url),
                                'type' => 'customizer_section',
                                'category' => 'appearance',
                                'keywords' => strtolower((isset($section->title) ? $section->title : $section_id) . ' section ' . $section_id),
                                'source' => $theme->get('Name'),
                                'section_id' => $section_id
                            );
                        } catch (Exception $e) {
                            continue;
                        } catch (Error $e) {
                            continue;
                        }
                    }
                } catch (Exception $e) {
                    // Sections scanning failed, continue
                } catch (Error $e) {
                    // Sections scanning failed, continue
                }
                
                // Scan controls
                try {
                    $controls = $wp_customize->controls();
                    foreach ($controls as $control_id => $control) {
                        // Skip core WordPress controls
                        if (in_array($control_id, array('blogname', 'blogdescription', 'site_icon', 'show_on_front', 'page_on_front', 'page_for_posts'))) {
                            continue;
                        }
                        
                        // Skip if it starts with nav_, widget_, or sidebars_ (core WordPress)
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
                            
                            $control_name = (isset($control->label) && $control->label) ? $control->label : $this->format_setting_name($control_id);
                            
                            $settings[] = array(
                                'name' => $control_name,
                                'description' => (isset($control->description) && $control->description) ? $control->description : 'Theme customizer setting',
                                'path' => $path,
                                'url' => $this->build_customizer_url('control', $control_id, $panel_id_for_url, $section_id_for_url),
                                'type' => 'customizer_control',
                                'category' => 'appearance',
                                'keywords' => strtolower($control_name . ' ' . $control_id),
                                'source' => $theme->get('Name'),
                                'control_id' => $control_id
                            );
                        } catch (Exception $e) {
                            continue;
                        } catch (Error $e) {
                            continue;
                        }
                    }
                } catch (Exception $e) {
                    // Controls scanning failed, continue
                } catch (Error $e) {
                    // Controls scanning failed, continue
                }
            }
        } catch (Exception $e) {
            // Silently fail - customizer scanning is optional
        } catch (Error $e) {
            // Silently fail - customizer scanning is optional
        } catch (Throwable $e) {
            // Catch any other throwable (PHP 7+)
        }
        
        ob_end_clean();
        
        return $settings;
    }
    
    /**
     * Scan Theme Modifications (theme_mods)
     * Universal scanner that works with all themes
     * 
     * @return array Theme modification settings
     */
    private function scan_theme_modifications() {
        $settings = array();
        $theme = wp_get_theme();
        $theme_mods = get_theme_mods();
        
        if (empty($theme_mods) || !is_array($theme_mods)) {
            return $settings;
        }
        
        foreach ($theme_mods as $mod_key => $mod_value) {
            // Skip special WordPress mods
            if (in_array($mod_key, array('nav_menu_locations', 'custom_css_post_id', 'background_image', 'header_image'))) {
                continue;
            }
            
            $settings[] = array(
                'name' => $this->format_setting_name($mod_key),
                'description' => 'Theme modification setting: ' . $mod_key,
                'path' => 'Appearance > Customize > Theme Modifications',
                'url' => admin_url('customize.php'),
                'type' => 'theme_mod',
                'category' => 'appearance',
                'keywords' => str_replace('_', ' ', $mod_key) . ' theme mod modification',
                'source' => $theme->get('Name'),
                'mod_key' => $mod_key
            );
        }
        
        return $settings;
    }
    
    /**
     * Scan Theme Options from Database
     * Detects theme-specific options stored in wp_options table
     * Only scans options for the ACTIVE theme
     * 
     * @return array Theme database options
     */
    private function scan_theme_database_options() {
        $settings = array();
        global $wpdb;
        $theme = wp_get_theme();
        $theme_slug = get_option('stylesheet'); // Active theme slug
        $theme_template = get_option('template'); // Parent theme if child theme
        $theme_name_lower = strtolower($theme->get('Name'));
        $theme_text_domain = $theme->get('TextDomain'); // Theme text domain
        
        // Only search for options that match the ACTIVE theme
        // Use specific patterns that include the theme slug/name to avoid matching other themes
        $option_patterns = array();
        
        // Add theme slug patterns (most specific)
        if (!empty($theme_slug)) {
            $option_patterns[] = $theme_slug . '_%';
            $option_patterns[] = '%_' . $wpdb->esc_like($theme_slug) . '_%';
        }
        
        // Add parent theme template if different (for child themes)
        if (!empty($theme_template) && $theme_template !== $theme_slug) {
            $option_patterns[] = $theme_template . '_%';
            $option_patterns[] = '%_' . $wpdb->esc_like($theme_template) . '_%';
        }
        
        // Add theme name patterns (if name is specific enough)
        if (!empty($theme_name_lower) && strlen($theme_name_lower) > 3) {
            // Only use if it's not a generic name
            $generic_names = array('theme', 'wordpress', 'wp', 'default', 'custom');
            if (!in_array($theme_name_lower, $generic_names)) {
                $option_patterns[] = '%' . $wpdb->esc_like($theme_name_lower) . '%';
            }
        }
        
        // Add text domain patterns
        if (!empty($theme_text_domain)) {
            $option_patterns[] = $theme_text_domain . '_%';
            $option_patterns[] = '%_' . $wpdb->esc_like($theme_text_domain) . '_%';
        }
        
        // If no specific patterns found, return empty (don't use generic patterns)
        if (empty($option_patterns)) {
            return $settings;
        }
        
        $where_clauses = array();
        $where_values = array();
        
        foreach ($option_patterns as $pattern) {
            $where_clauses[] = 'option_name LIKE %s';
            $where_values[] = $pattern;
        }
        
        $query = "SELECT option_name, option_value 
                  FROM {$wpdb->options} 
                  WHERE (" . implode(' OR ', $where_clauses) . ")
                  AND option_name NOT LIKE %s
                  AND option_name NOT LIKE %s
                  AND option_name NOT LIKE %s
                  LIMIT 200";
        
        $where_values[] = '%_transient%';
        $where_values[] = '%_cache%';
        $where_values[] = '%_site_transient%';
        
        $results = $wpdb->get_results($wpdb->prepare($query, $where_values));
        
        foreach ($results as $row) {
            // Additional filtering: ensure option name contains active theme identifier
            $option_name_lower = strtolower($row->option_name);
            $is_active_theme_option = false;
            
            // Check if option name contains active theme slug or template
            if (!empty($theme_slug) && strpos($option_name_lower, strtolower($theme_slug)) !== false) {
                $is_active_theme_option = true;
            } elseif (!empty($theme_template) && strpos($option_name_lower, strtolower($theme_template)) !== false) {
                $is_active_theme_option = true;
            } elseif (!empty($theme_text_domain) && strpos($option_name_lower, strtolower($theme_text_domain)) !== false) {
                $is_active_theme_option = true;
            }
            
            // Skip if it doesn't belong to active theme
            if (!$is_active_theme_option) {
                continue;
            }
            
            // Skip if it's a transient or cache
            if (strpos($row->option_name, '_transient') !== false || 
                strpos($row->option_name, '_cache') !== false) {
                continue;
            }
            
            $value = maybe_unserialize($row->option_value);
            $option_url = $this->find_theme_option_page_url($row->option_name);
            
            if (is_array($value)) {
                // If it's an array, create settings for each key
                foreach ($value as $key => $val) {
                    $settings[] = array(
                        'name' => $this->format_setting_name($key),
                        'description' => 'Theme option: ' . $row->option_name . '[' . $key . ']',
                        'path' => 'Theme Options > ' . $this->format_setting_name($row->option_name) . ' > ' . $this->format_setting_name($key),
                        'url' => $option_url,
                        'type' => 'theme_option',
                        'category' => 'appearance',
                        'keywords' => str_replace('_', ' ', $key . ' ' . $row->option_name) . ' theme option',
                        'source' => $theme->get('Name'),
                        'option_key' => $row->option_name . '[' . $key . ']'
                    );
                }
            } else {
                $settings[] = array(
                    'name' => $this->format_setting_name($row->option_name),
                    'description' => 'Theme database option',
                    'path' => 'Theme Options > ' . $this->format_setting_name($row->option_name),
                    'url' => $option_url,
                    'type' => 'theme_option',
                    'category' => 'appearance',
                    'keywords' => str_replace('_', ' ', $row->option_name) . ' theme option database',
                    'source' => $theme->get('Name'),
                    'option_key' => $row->option_name
                );
            }
        }
        
        return $settings;
    }
    
    /**
     * Scan Menu Locations
     * 
     * @return array Menu location settings
     */
    private function scan_menu_locations() {
        $settings = array();
        $theme = wp_get_theme();
        $menu_locations = get_registered_nav_menus();
        
        if (empty($menu_locations)) {
            return $settings;
        }
        
        foreach ($menu_locations as $location => $description) {
            $settings[] = array(
                'name' => $description,
                'description' => 'Theme menu location: ' . $location,
                'path' => 'Appearance > Menus > Menu Locations',
                'url' => admin_url('nav-menus.php?action=locations'),
                'type' => 'menu_location',
                'category' => 'appearance',
                'keywords' => 'menu navigation location ' . $description . ' ' . $location,
                'source' => $theme->get('Name'),
                'location_id' => $location
            );
        }
        
        return $settings;
    }
    
    /**
     * Scan Widget Areas (Sidebars)
     * 
     * @return array Widget area settings
     */
    private function scan_widget_areas() {
        $settings = array();
        global $wp_registered_sidebars;
        $theme = wp_get_theme();
        
        if (empty($wp_registered_sidebars) || !is_array($wp_registered_sidebars)) {
            return $settings;
        }
        
        foreach ($wp_registered_sidebars as $sidebar_id => $sidebar) {
            // Skip core WordPress sidebars
            if (in_array($sidebar_id, array('wp_inactive_widgets', 'array_version'))) {
                continue;
            }
            
            $settings[] = array(
                'name' => isset($sidebar['name']) ? $sidebar['name'] : $this->format_setting_name($sidebar_id),
                'description' => isset($sidebar['description']) ? $sidebar['description'] : 'Widget area',
                'path' => 'Appearance > Widgets > ' . (isset($sidebar['name']) ? $sidebar['name'] : $sidebar_id),
                'url' => admin_url('widgets.php'),
                'type' => 'widget_area',
                'category' => 'appearance',
                'keywords' => 'widget sidebar area ' . (isset($sidebar['name']) ? $sidebar['name'] : $sidebar_id) . ' ' . $sidebar_id,
                'source' => $theme->get('Name'),
                'sidebar_id' => $sidebar_id
            );
        }
        
        return $settings;
    }
    
    /**
     * Scan Page Templates
     * 
     * @return array Page template settings
     */
    private function scan_page_templates() {
        $settings = array();
        $theme = wp_get_theme();
        $templates = wp_get_theme()->get_page_templates();
        
        if (empty($templates) || !is_array($templates)) {
            return $settings;
        }
        
        foreach ($templates as $template_file => $template_name) {
            $settings[] = array(
                'name' => $template_name,
                'description' => 'Page template: ' . $template_file,
                'path' => 'Page > Page Attributes > Template',
                'url' => admin_url('edit.php?post_type=page'),
                'type' => 'page_template',
                'category' => 'appearance',
                'keywords' => 'template page layout ' . $template_name . ' ' . $template_file,
                'source' => $theme->get('Name'),
                'template_file' => $template_file
            );
        }
        
        return $settings;
    }
    
    /**
     * Scan Block Patterns
     * Detects block patterns registered by the ACTIVE theme only
     * 
     * @return array Block pattern settings
     */
    private function scan_block_patterns() {
        $settings = array();
        $theme = wp_get_theme();
        $theme_slug = get_option('stylesheet'); // Active theme slug
        $theme_template = get_option('template'); // Parent theme if child theme
        $theme_text_domain = $theme->get('TextDomain'); // Theme text domain
        
        if (!function_exists('register_block_pattern') && !class_exists('WP_Block_Patterns_Registry')) {
            return $settings;
        }
        
        $patterns = array();
        
        // Get all registered patterns from WordPress 5.5+
        if (class_exists('WP_Block_Patterns_Registry')) {
            $registry = WP_Block_Patterns_Registry::get_instance();
            if (method_exists($registry, 'get_all_registered')) {
                $all_patterns = $registry->get_all_registered();
                
                // Filter patterns to only include those from active theme
                foreach ($all_patterns as $pattern) {
                    if (!isset($pattern['name'])) {
                        continue;
                    }
                    
                    $pattern_slug = $pattern['name'];
                    $belongs_to_active_theme = false;
                    $theme_namespace = '';
                    
                    // Extract theme namespace if present (e.g., "neve/pattern-name" or "hestia/pattern-name")
                    if (strpos($pattern_slug, '/') !== false) {
                        $parts = explode('/', $pattern_slug, 2);
                        $theme_namespace = $parts[0];
                        $pattern_name = $parts[1];
                        
                        // Check if namespace matches active theme
                        if (strtolower($theme_namespace) === strtolower($theme_slug) ||
                            strtolower($theme_namespace) === strtolower($theme_template) ||
                            (!empty($theme_text_domain) && strtolower($theme_namespace) === strtolower($theme_text_domain))) {
                            $belongs_to_active_theme = true;
                        }
                    } else {
                        // Pattern without namespace - only include if it's registered by active theme
                        // We can't reliably detect this, so skip patterns without namespace
                        continue;
                    }
                    
                    // Only add patterns from active theme
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
            
            $settings[] = array(
                'name' => 'Block Pattern: ' . $pattern['name'],
                'description' => $pattern['description'],
                'path' => 'Block Editor > Patterns > ' . ucfirst($theme_namespace),
                'url' => admin_url('post-new.php?post_type=page'),
                'type' => 'block_pattern',
                'category' => 'patterns',
                'keywords' => 'pattern block ' . strtolower($pattern['name']) . ' ' . $pattern_slug,
                'source' => $theme->get('Name'),
                'pattern_slug' => $pattern_slug
            );
        }
        
        return $settings;
    }
    
    /**
     * Scan Custom Post Types
     * Detects all registered custom post types
     * 
     * @return array Custom post type settings
     */
    private function scan_custom_post_types() {
        $settings = array();
        $theme = wp_get_theme();
        
        // Get all registered post types
        $post_types = get_post_types(array('public' => true, '_builtin' => false), 'objects');
        
        if (empty($post_types)) {
            return $settings;
        }
        
        foreach ($post_types as $post_type => $post_type_obj) {
            // Skip if it's a built-in type or not public
            if (in_array($post_type, array('post', 'page', 'attachment', 'revision', 'nav_menu_item'))) {
                continue;
            }
            
            $settings[] = array(
                'name' => isset($post_type_obj->labels->name) ? $post_type_obj->labels->name : $this->format_setting_name($post_type),
                'description' => isset($post_type_obj->description) ? $post_type_obj->description : 'Custom post type: ' . $post_type,
                'path' => 'Content > ' . (isset($post_type_obj->labels->name) ? $post_type_obj->labels->name : $post_type),
                'url' => admin_url('edit.php?post_type=' . $post_type),
                'type' => 'custom_post_type',
                'category' => 'content',
                'keywords' => 'post type cpt ' . strtolower($post_type) . ' ' . (isset($post_type_obj->labels->name) ? strtolower($post_type_obj->labels->name) : ''),
                'source' => isset($post_type_obj->_theme) ? $post_type_obj->_theme : $theme->get('Name'),
                'post_type' => $post_type
            );
        }
        
        return $settings;
    }
    
    /**
     * Scan Post/Page Metaboxes (Generic detection)
     * Detects common metabox patterns used by themes
     * Only scans metaboxes for the ACTIVE theme
     * 
     * @return array Metabox settings
     */
    private function scan_post_metaboxes() {
        $settings = array();
        $theme = wp_get_theme();
        $theme_slug = get_option('stylesheet'); // Active theme slug
        $theme_template = get_option('template'); // Parent theme if child theme
        $theme_name_lower = strtolower($theme->get('Name'));
        $theme_text_domain = $theme->get('TextDomain'); // Theme text domain
        
        // Check if any posts/pages have theme-specific meta keys
        // Only search for meta keys that belong to the ACTIVE theme
        global $wpdb;
        
        $where_patterns = array();
        $where_values = array();
        
        // Build patterns for active theme only
        if (!empty($theme_slug)) {
            $where_patterns[] = 'meta_key LIKE %s';
            $where_values[] = $theme_slug . '_%';
        }
        
        // Include parent theme if it's a child theme
        if (!empty($theme_template) && $theme_template !== $theme_slug) {
            $where_patterns[] = 'meta_key LIKE %s';
            $where_values[] = $theme_template . '_%';
        }
        
        // Include text domain if available
        if (!empty($theme_text_domain)) {
            $where_patterns[] = 'meta_key LIKE %s';
            $where_values[] = $theme_text_domain . '_%';
        }
        
        // If no patterns, return empty
        if (empty($where_patterns)) {
            return $settings;
        }
        
        $query = "SELECT DISTINCT meta_key 
                 FROM {$wpdb->postmeta} 
                 WHERE (" . implode(' OR ', $where_patterns) . ")
                 AND meta_key NOT LIKE %s
                 AND meta_key NOT LIKE %s
                 AND meta_key NOT LIKE %s
                 AND meta_key NOT LIKE %s
                 LIMIT 50";
        
        $where_values[] = '_edit_%';
        $where_values[] = '_wp_%';
        $where_values[] = 'elementor_%';
        $where_values[] = 'beaver_%';
        
        $meta_keys_found = $wpdb->get_col($wpdb->prepare($query, $where_values));
        
        foreach ($meta_keys_found as $meta_key) {
            $meta_key_lower = strtolower($meta_key);
            
            // Verify it belongs to active theme
            $belongs_to_active_theme = false;
            
            if (!empty($theme_slug) && strpos($meta_key_lower, strtolower($theme_slug)) === 0) {
                $belongs_to_active_theme = true;
            } elseif (!empty($theme_template) && strpos($meta_key_lower, strtolower($theme_template)) === 0) {
                $belongs_to_active_theme = true;
            } elseif (!empty($theme_text_domain) && strpos($meta_key_lower, strtolower($theme_text_domain)) === 0) {
                $belongs_to_active_theme = true;
            }
            
            // Skip if it doesn't belong to active theme
            if (!$belongs_to_active_theme) {
                continue;
            }
            
            // Skip known WordPress core meta keys
            if (in_array($meta_key, array('_edit_lock', '_edit_last', '_wp_page_template', '_thumbnail_id', '_wp_attachment_metadata'))) {
                continue;
            }
            
            $settings[] = array(
                'name' => $this->format_setting_name($meta_key),
                'description' => 'Post/Page metabox option: ' . $meta_key,
                'path' => 'Post/Page Editor > Theme Options > ' . $this->format_setting_name($meta_key),
                'url' => admin_url('edit.php?post_type=post'),
                'type' => 'post_metabox',
                'category' => 'content',
                'keywords' => 'metabox meta ' . str_replace('_', ' ', $meta_key) . ' post page',
                'source' => $theme->get('Name'),
                'meta_key' => $meta_key
            );
        }
        
        return $settings;
    }
    
    /**
     * Scan Theme Support Features
     * 
     * @return array Theme support feature settings
     */
    private function scan_theme_support_features() {
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
                $settings[] = array(
                    'name' => $info['name'] . ' (Enabled)',
                    'description' => 'Theme support feature: ' . $feature,
                    'path' => 'Theme Features > ' . $info['name'],
                    'url' => $info['url'],
                    'type' => 'theme_support',
                    'category' => 'appearance',
                    'keywords' => 'theme support feature ' . str_replace('-', ' ', $feature),
                    'source' => $theme->get('Name'),
                    'feature' => $feature
                );
            }
        }
        
        return $settings;
    }
    
    /**
     * Scan Core WordPress Settings
     * Comprehensive list of all WordPress core settings
     * 
     * @return array Core WordPress settings
     */
    private function scan_core_wordpress_settings() {
        $settings = array();
        
        // General Settings
        $core_settings = array(
            array(
                'name' => 'Site Title',
                'description' => 'The name of your website',
                'path' => 'Settings > General',
                'url' => admin_url('options-general.php'),
                'category' => 'general',
                'keywords' => 'title site name website name blogname'
            ),
            array(
                'name' => 'Tagline',
                'description' => 'A short description of your website',
                'path' => 'Settings > General',
                'url' => admin_url('options-general.php'),
                'category' => 'general',
                'keywords' => 'tagline description slogan blogdescription'
            ),
            array(
                'name' => 'WordPress Address (URL)',
                'description' => 'The address where your WordPress files are located',
                'path' => 'Settings > General',
                'url' => admin_url('options-general.php'),
                'category' => 'general',
                'keywords' => 'url wordpress address siteurl'
            ),
            array(
                'name' => 'Site Address (URL)',
                'description' => 'The address visitors use to view your website',
                'path' => 'Settings > General',
                'url' => admin_url('options-general.php'),
                'category' => 'general',
                'keywords' => 'url site address home'
            ),
            array(
                'name' => 'Email Address',
                'description' => 'This address is used for admin purposes',
                'path' => 'Settings > General',
                'url' => admin_url('options-general.php'),
                'category' => 'general',
                'keywords' => 'email admin contact'
            ),
            array(
                'name' => 'Membership',
                'description' => 'Allow anyone to register',
                'path' => 'Settings > General',
                'url' => admin_url('options-general.php'),
                'category' => 'general',
                'keywords' => 'membership registration users'
            ),
            array(
                'name' => 'New User Default Role',
                'description' => 'Default role for new users',
                'path' => 'Settings > General',
                'url' => admin_url('options-general.php'),
                'category' => 'general',
                'keywords' => 'user role default subscriber'
            ),
            array(
                'name' => 'Timezone',
                'description' => 'Choose a city in the same timezone as you',
                'path' => 'Settings > General',
                'url' => admin_url('options-general.php'),
                'category' => 'general',
                'keywords' => 'timezone time date'
            ),
            array(
                'name' => 'Date Format',
                'description' => 'Choose how dates should be displayed',
                'path' => 'Settings > General',
                'url' => admin_url('options-general.php'),
                'category' => 'general',
                'keywords' => 'date format display'
            ),
            array(
                'name' => 'Time Format',
                'description' => 'Choose how times should be displayed',
                'path' => 'Settings > General',
                'url' => admin_url('options-general.php'),
                'category' => 'general',
                'keywords' => 'time format display'
            ),
            array(
                'name' => 'Week Starts On',
                'description' => 'Choose the day the week starts',
                'path' => 'Settings > General',
                'url' => admin_url('options-general.php'),
                'category' => 'general',
                'keywords' => 'week start day monday sunday'
            ),
            array(
                'name' => 'Site Language',
                'description' => 'Choose a language for the WordPress interface',
                'path' => 'Settings > General',
                'url' => admin_url('options-general.php'),
                'category' => 'general',
                'keywords' => 'language locale translation'
            ),
            // Writing Settings
            array(
                'name' => 'Default Post Category',
                'description' => 'Default category for new posts',
                'path' => 'Settings > Writing',
                'url' => admin_url('options-writing.php'),
                'category' => 'content',
                'keywords' => 'category default post'
            ),
            array(
                'name' => 'Default Post Format',
                'description' => 'Default format for new posts',
                'path' => 'Settings > Writing',
                'url' => admin_url('options-writing.php'),
                'category' => 'content',
                'keywords' => 'post format default'
            ),
            array(
                'name' => 'Post via Email',
                'description' => 'Publish posts via email',
                'path' => 'Settings > Writing',
                'url' => admin_url('options-writing.php'),
                'category' => 'content',
                'keywords' => 'email post publish'
            ),
            array(
                'name' => 'Update Services',
                'description' => 'Services to notify when you publish a new post',
                'path' => 'Settings > Writing',
                'url' => admin_url('options-writing.php'),
                'category' => 'content',
                'keywords' => 'ping update service'
            ),
            // Reading Settings
            array(
                'name' => 'Homepage Display',
                'description' => 'Choose what shows on your homepage',
                'path' => 'Settings > Reading',
                'url' => admin_url('options-reading.php'),
                'category' => 'appearance',
                'keywords' => 'homepage front page static page'
            ),
            array(
                'name' => 'Blog Pages Show At Most',
                'description' => 'Number of posts to show on blog pages',
                'path' => 'Settings > Reading',
                'url' => admin_url('options-reading.php'),
                'category' => 'content',
                'keywords' => 'posts per page blog'
            ),
            array(
                'name' => 'Syndication Feeds Show The Most Recent',
                'description' => 'Number of items to show in feeds',
                'path' => 'Settings > Reading',
                'url' => admin_url('options-reading.php'),
                'category' => 'content',
                'keywords' => 'feed rss items'
            ),
            array(
                'name' => 'For Each Post In A Feed, Include',
                'description' => 'Choose what to include in feeds',
                'path' => 'Settings > Reading',
                'url' => admin_url('options-reading.php'),
                'category' => 'content',
                'keywords' => 'feed full text summary'
            ),
            array(
                'name' => 'Search Engine Visibility',
                'description' => 'Discourage search engines from indexing this site',
                'path' => 'Settings > Reading',
                'url' => admin_url('options-reading.php'),
                'category' => 'seo',
                'keywords' => 'seo google search engines robots indexing'
            ),
            // Discussion Settings
            array(
                'name' => 'Default Article Settings',
                'description' => 'Default comment settings for new posts',
                'path' => 'Settings > Discussion',
                'url' => admin_url('options-discussion.php'),
                'category' => 'content',
                'keywords' => 'comments pingback trackback'
            ),
            array(
                'name' => 'Other Comment Settings',
                'description' => 'Additional comment moderation settings',
                'path' => 'Settings > Discussion',
                'url' => admin_url('options-discussion.php'),
                'category' => 'content',
                'keywords' => 'comments moderation approval'
            ),
            array(
                'name' => 'Email Me Whenever',
                'description' => 'Email notifications for comments',
                'path' => 'Settings > Discussion',
                'url' => admin_url('options-discussion.php'),
                'category' => 'content',
                'keywords' => 'email notification comments'
            ),
            array(
                'name' => 'Before A Comment Appears',
                'description' => 'Comment moderation settings',
                'path' => 'Settings > Discussion',
                'url' => admin_url('options-discussion.php'),
                'category' => 'content',
                'keywords' => 'comment moderation approval'
            ),
            array(
                'name' => 'Comment Moderation',
                'description' => 'Hold comments in queue if they contain links',
                'path' => 'Settings > Discussion',
                'url' => admin_url('options-discussion.php'),
                'category' => 'content',
                'keywords' => 'comment moderation links'
            ),
            array(
                'name' => 'Comment Blacklist',
                'description' => 'Comments containing these words will be marked as spam',
                'path' => 'Settings > Discussion',
                'url' => admin_url('options-discussion.php'),
                'category' => 'content',
                'keywords' => 'comment spam blacklist'
            ),
            array(
                'name' => 'Avatars',
                'description' => 'Avatar display settings',
                'path' => 'Settings > Discussion',
                'url' => admin_url('options-discussion.php'),
                'category' => 'content',
                'keywords' => 'avatar gravatar profile picture'
            ),
            // Media Settings
            array(
                'name' => 'Image Sizes',
                'description' => 'Default sizes for images',
                'path' => 'Settings > Media',
                'url' => admin_url('options-media.php'),
                'category' => 'media',
                'keywords' => 'images thumbnails media sizes'
            ),
            array(
                'name' => 'Uploading Files',
                'description' => 'Organize uploads into month- and year-based folders',
                'path' => 'Settings > Media',
                'url' => admin_url('options-media.php'),
                'category' => 'media',
                'keywords' => 'uploads files organization'
            ),
            // Permalink Settings
            array(
                'name' => 'Permalink Structure',
                'description' => 'Choose the URL structure for your posts',
                'path' => 'Settings > Permalinks',
                'url' => admin_url('options-permalink.php'),
                'category' => 'seo',
                'keywords' => 'permalinks urls slug seo'
            ),
            array(
                'name' => 'Category Base',
                'description' => 'URL prefix for category archives',
                'path' => 'Settings > Permalinks',
                'url' => admin_url('options-permalink.php'),
                'category' => 'seo',
                'keywords' => 'category base url permalink'
            ),
            array(
                'name' => 'Tag Base',
                'description' => 'URL prefix for tag archives',
                'path' => 'Settings > Permalinks',
                'url' => admin_url('options-permalink.php'),
                'category' => 'seo',
                'keywords' => 'tag base url permalink'
            ),
        );
        
        foreach ($core_settings as $setting) {
            $settings[] = array_merge($setting, array(
                'type' => 'core_setting',
                'source' => 'WordPress Core'
            ));
        }
        
        return $settings;
    }
    
    /**
     * Scan Dashboard Pages (Theme-related admin menu items)
     * Scans all WordPress admin menu items and submenu items
     * Filters items that match the active theme's slug
     * 
     * @return array Dashboard page settings
     */
    private function scan_dashboard_pages() {
        $settings = array();
        global $menu, $submenu;
        
        // Get active theme information
        $theme = wp_get_theme();
        $theme_slug = get_option('stylesheet'); // e.g., 'neve'
        $theme_template = get_option('template'); // e.g., 'neve' (if not child theme)
        $theme_text_domain = $theme->get('TextDomain'); // e.g., 'neve'
        $theme_name = $theme->get('Name'); // e.g., 'Neve'
        
        // Build array of theme identifiers to match
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
            return $settings; // No theme identifiers to match
        }
        
        // Scan top-level menu items
        if (is_array($menu)) {
            foreach ($menu as $menu_item) {
                if (!isset($menu_item[0]) || !isset($menu_item[2])) {
                    continue;
                }
                
                $menu_title = $menu_item[0];
                $menu_slug = $menu_item[2];
                
                // Clean menu title (remove HTML tags)
                $menu_title = strip_tags($menu_title);
                
                // Check if menu slug or title matches theme identifiers
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
                    // Build menu path
                    $menu_path = $menu_title;
                    
                    // Get menu URL
                    $menu_url = admin_url($menu_slug);
                    
                    $settings[] = array(
                        'name' => $menu_title,
                        'description' => 'Theme dashboard page: ' . $menu_slug,
                        'path' => $menu_path,
                        'url' => $menu_url,
                        'type' => 'dashboard_page',
                        'category' => 'dashboard_pages',
                        'keywords' => strtolower($menu_title . ' ' . $menu_slug . ' ' . $theme_name . ' dashboard page'),
                        'source' => $theme_name,
                        'menu_slug' => $menu_slug
                    );
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
                    
                    $submenu_title = $submenu_item[0];
                    $submenu_slug = $submenu_item[2];
                    
                    // Clean submenu title (remove HTML tags)
                    $submenu_title = strip_tags($submenu_title);
                    
                    // Check if submenu slug or title matches theme identifiers
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
                        // Get parent menu title for path
                        $parent_title = '';
                        if (is_array($menu)) {
                            foreach ($menu as $menu_item) {
                                if (isset($menu_item[2]) && $menu_item[2] === $parent_slug) {
                                    $parent_title = strip_tags($menu_item[0]);
                                    break;
                                }
                            }
                        }
                        
                        // Build menu path
                        $menu_path = !empty($parent_title) ? $parent_title . ' > ' . $submenu_title : $submenu_title;
                        
                        // Get submenu URL
                        // WordPress submenu URLs should use admin.php?page=submenu_slug format
                        // Some submenus use direct file paths (e.g., themes.php, options-general.php)
                        if (strpos($submenu_slug, '.php') !== false) {
                            // Direct file path - use as-is
                            $submenu_url = admin_url($submenu_slug);
                        } else {
                            // Standard submenu - use admin.php?page= format
                            $submenu_url = admin_url('admin.php?page=' . $submenu_slug);
                        }
                        
                        // Validate and fix invalid URLs (e.g., themes.php?page=themes.php)
                        $submenu_url = $this->validate_admin_url($submenu_url);
                        
                        $settings[] = array(
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
                        );
                    }
                }
            }
        }
        
        return $settings;
    }
    
    /**
     * Validate and fix invalid admin URLs
     * Fixes URLs like themes.php?page=themes.php that return 404
     * 
     * @param string $url The URL to validate
     * @return string Valid URL (redirects to wp-admin if invalid)
     */
    private function validate_admin_url($url) {
        // Parse the URL
        $parsed = parse_url($url);
        
        // If URL parsing fails, return wp-admin as fallback
        if ($parsed === false || !isset($parsed['path'])) {
            return admin_url();
        }
        
        // Extract the base file name (e.g., 'themes.php' from '/wp-admin/themes.php')
        $path = $parsed['path'];
        $base_file = basename($path);
        
        // Check if there's a query string with 'page' parameter
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $query_params);
            
            // If page parameter equals the base file name, it's invalid (e.g., themes.php?page=themes.php)
            if (isset($query_params['page']) && $query_params['page'] === $base_file) {
                // Redirect to wp-admin page
                return admin_url();
            }
        }
        
        // URL is valid, return as-is
        return $url;
    }
    
    /**
     * Validate URLs in a settings array
     * 
     * @param array $settings Array of settings with 'url' keys
     * @return array Settings array with validated URLs
     */
    private function validate_settings_urls($settings) {
        foreach ($settings as &$setting) {
            if (isset($setting['url']) && !empty($setting['url'])) {
                $setting['url'] = $this->validate_admin_url($setting['url']);
            }
        }
        return $settings;
    }
    
    /**
     * Format setting name to be user-friendly
     * 
     * @param string $name Raw setting name/ID
     * @return string Formatted name
     */
    private function format_setting_name($name) {
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
     * Find theme option page URL
     * Attempts to find the admin page where theme options are located
     * 
     * @param string $option_name Option name to search for
     * @return string URL to theme options page
     */
    private function find_theme_option_page_url($option_name) {
        global $submenu, $menu;
        
        // Check submenus under Appearance
        if (isset($submenu['themes.php'])) {
            foreach ($submenu['themes.php'] as $item) {
                if (isset($item[2]) && (
                    strpos($item[2], 'theme') !== false ||
                    strpos($item[2], 'option') !== false ||
                    strpos($item[2], 'customize') !== false
                )) {
                    $url = admin_url('themes.php?page=' . $item[2]);
                    // Validate and fix invalid URLs (e.g., themes.php?page=themes.php)
                    return $this->validate_admin_url($url);
                }
            }
        }
        
        // Check top-level menus
        if (isset($menu)) {
            foreach ($menu as $item) {
                if (isset($item[0]) && isset($item[2]) && (
                    stripos($item[0], 'Theme') !== false ||
                    stripos($item[0], 'Option') !== false
                )) {
                    return admin_url($item[2]);
                }
            }
        }
        
        // Default to customizer
        return admin_url('customize.php');
    }
    
    /**
     * Build customizer URL with proper autofocus parameters
     * 
     * @param string $type 'panel', 'section', or 'control'
     * @param string $id The ID of the panel/section/control
     * @param string $panel_id Optional panel ID if section/control is in a panel
     * @param string $section_id Optional section ID if control is in a section
     * @return string Customizer URL with autofocus
     */
    private function build_customizer_url($type, $id, $panel_id = '', $section_id = '') {
        // WordPress customizer URL format: wp-admin/customize.php?autofocus[control]=control_id
        // Use admin_url to get the full path, then append query parameters
        $url = admin_url('customize.php');
        
        if (empty($id)) {
            return $url;
        }
        
        // WordPress customizer expects autofocus[type]=id format
        // The brackets in the key should NOT be encoded, only the value should be encoded
        // Build query string manually to ensure correct format
        $query_params = array();
        
        if ($type === 'panel') {
            $query_params['autofocus[panel]'] = $id;
        } elseif ($type === 'section') {
            $query_params['autofocus[section]'] = $id;
            if (!empty($panel_id)) {
                $query_params['autofocus[panel]'] = $panel_id;
            }
        } elseif ($type === 'control') {
            // For controls, we only need autofocus[control]=control_id
            // WordPress will automatically expand to show the correct section/panel
            $query_params['autofocus[control]'] = $id;
        }
        
        // Build URL manually to preserve bracket format
        if (!empty($query_params)) {
            $query_string = '';
            foreach ($query_params as $key => $value) {
                if (!empty($query_string)) {
                    $query_string .= '&';
                }
                // Key should have brackets unencoded, value should be URL encoded
                $query_string .= $key . '=' . urlencode($value);
            }
            $url .= '?' . $query_string;
        }
        
        return $url;
    }
    
    /**
     * Get categories with counts
     * 
     * @param array $settings All settings array
     * @return array Categories with counts
     */
    private function get_categories($settings) {
        $categories = array(
            'all' => array('label' => 'Everything', 'emoji' => '✨', 'count' => count($settings)),
            'general' => array('label' => 'General Settings', 'emoji' => '⚙️', 'count' => 0),
            'appearance' => array('label' => 'Look & Feel', 'emoji' => '🎨', 'count' => 0),
            'content' => array('label' => 'Content & Writing', 'emoji' => '📝', 'count' => 0),
            'media' => array('label' => 'Media', 'emoji' => '🖼️', 'count' => 0),
            'seo' => array('label' => 'SEO & URLs', 'emoji' => '🔍', 'count' => 0),
            'patterns' => array('label' => 'Patterns', 'emoji' => '🧩', 'count' => 0),
            'dashboard_pages' => array('label' => 'Dashboard Pages', 'emoji' => '📊', 'count' => 0)
        );
        
        foreach ($settings as $setting) {
            $cat = $setting['category'] ?? 'general';
            if (isset($categories[$cat])) {
                $categories[$cat]['count']++;
            }
        }
        
        return $categories;
    }
    
    /**
     * AJAX handlers
     */
    public function ajax_search() {
        check_ajax_referer('sf_ajax_nonce', 'nonce');
        $query = sanitize_text_field($_POST['query'] ?? '');
        wp_send_json_success(array());
    }
    
    public function ajax_refresh_scan() {
        check_ajax_referer('sf_ajax_nonce', 'nonce');
        wp_send_json_success(array('message' => 'Scan completed'));
    }
    
    /**
     * Render AI Assisted Search page
     */
    public function render_ai_search_page() {
        // Handle OpenAI API key save
        if (isset($_POST['sf_save_openai_key']) && check_admin_referer('sf_save_openai_key')) {
            $api_key = isset($_POST['sf_openai_api_key']) ? sanitize_text_field($_POST['sf_openai_api_key']) : '';
            update_option('sf_openai_api_key', $api_key);
            echo '<div class="notice notice-success is-dismissible"><p>OpenAI API key saved successfully!</p></div>';
        }
        
        // Get all available settings for AI context
        // Note: scan_all_theme_options() already includes core settings via scan_core_wordpress_settings()
        $all_settings = $this->scan_all_theme_options();
        
        // Get OpenAI API key from settings
        $openai_api_key = get_option('sf_openai_api_key', '');
        ?>
        <div class="wrap sf-ai-search-wrap">
            <div class="sf-ai-header">
                <h1>🤖 AI Assisted Interactive Search</h1>
                <p>Ask me anything about your WordPress settings! I'll help you find and navigate to the exact setting you need.</p>
            </div>
            
            <!-- OpenAI API Key Settings -->
            <div class="sf-ai-settings-section">
                <h2>⚙️ Configuration</h2>
                <form method="post" action="" class="sf-ai-api-key-form">
                    <?php wp_nonce_field('sf_save_openai_key'); ?>
                    <div class="sf-ai-api-key-field">
                        <label for="sf_openai_api_key">
                            <strong>OpenAI API Key</strong>
                            <span class="description">Required to use AI features</span>
                        </label>
                        <div class="sf-ai-api-key-input-wrapper">
                            <input 
                                type="password" 
                                id="sf_openai_api_key" 
                                name="sf_openai_api_key" 
                                value="<?php echo esc_attr($openai_api_key); ?>" 
                                class="sf-ai-api-key-input"
                                placeholder="sk-..."
                            >
                            <button type="button" class="sf-ai-toggle-password" onclick="this.previousElementSibling.type = this.previousElementSibling.type === 'password' ? 'text' : 'password'; this.textContent = this.previousElementSibling.type === 'password' ? '👁️' : '🙈';">
                                👁️
                            </button>
                        </div>
                        <p class="description">
                            Enter your OpenAI API key to enable AI-powered search. 
                            <a href="https://platform.openai.com/api-keys" target="_blank">Get your API key here</a>
                        </p>
                        <?php if (!empty($openai_api_key)): ?>
                            <p class="sf-ai-api-status" style="color: green; margin-top: 10px;">
                                ✓ API key is configured and ready to use
                            </p>
                        <?php else: ?>
                            <p class="sf-ai-api-status" style="color: #d63638; margin-top: 10px;">
                                ⚠️ API key is required to use AI features
                            </p>
                        <?php endif; ?>
                    </div>
                    <p class="submit">
                        <input type="submit" name="sf_save_openai_key" class="button button-primary" value="Save API Key">
                    </p>
                </form>
            </div>
            
            <div class="sf-ai-chat-container">
                <div class="sf-ai-chat-header">
                    <h3>💬 Chat here</h3>
                    <button type="button" class="button button-small" id="sf-ai-clear-history" title="Clear chat history">
                        Clear History
                    </button>
                </div>
                <div class="sf-ai-chat-messages" id="sf-ai-chat-messages">
                    <div class="sf-ai-message sf-ai-message-bot">
                        <div class="sf-ai-avatar">🤖</div>
                        <div class="sf-ai-content">
                            <p>Hello! I'm your AI assistant. I can help you find any WordPress setting on your site.</p>
                            <p><strong>Try asking me:</strong></p>
                            <ul>
                                <li>"How do I change the site logo?"</li>
                                <li>"Where can I modify the site colors?"</li>
                                <li>"How to change the homepage display?"</li>
                                <li>"Where are the comment settings?"</li>
                            </ul>
                            <p>I'll provide step-by-step instructions and direct links to the settings!</p>
                        </div>
                    </div>
                </div>
                
                <div class="sf-ai-chat-input-container">
                    <form id="sf-ai-chat-form" class="sf-ai-chat-form">
                        <input 
                            type="text" 
                            id="sf-ai-chat-input" 
                            class="sf-ai-chat-input" 
                            placeholder="Ask me anything about your WordPress settings..."
                            autocomplete="off"
                            <?php echo empty($openai_api_key) ? 'disabled' : ''; ?>
                        >
                        <button type="submit" class="sf-ai-chat-send" id="sf-ai-chat-send" <?php echo empty($openai_api_key) ? 'disabled' : ''; ?>>
                            <span class="sf-ai-send-icon">📤</span>
                            <span class="sf-ai-send-text">Send</span>
                        </button>
                    </form>
                    <div class="sf-ai-loading" id="sf-ai-loading" style="display: none;">
                        <span class="sf-ai-spinner"></span>
                        <span>AI is thinking...</span>
                    </div>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        // Pass settings data to JavaScript
        window.sfAISettings = {
            settings: <?php echo json_encode($all_settings); ?>,
            apiKey: <?php echo json_encode($openai_api_key); ?>,
            ajaxUrl: <?php echo json_encode(admin_url('admin-ajax.php')); ?>,
            nonce: <?php echo json_encode(wp_create_nonce('sf_ajax_nonce')); ?>
        };
        </script>
        <?php
    }
    
    /**
     * AJAX handler for AI chat
     */
    public function ajax_ai_chat() {
        check_ajax_referer('sf_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }
        
        // Handle clear history request
        if (isset($_POST['action_clear']) && $_POST['action_clear'] === 'true') {
            delete_user_meta(get_current_user_id(), 'sf_ai_chat_history');
            wp_send_json_success(array('message' => 'Chat history cleared'));
            return;
        }
        
        // Handle get history request
        if (isset($_POST['action_get_history']) && $_POST['action_get_history'] === 'true') {
            $history = get_user_meta(get_current_user_id(), 'sf_ai_chat_history', true);
            wp_send_json_success(array('history' => $history ? $history : array()));
            return;
        }
        
        $user_message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
        
        if (empty($user_message)) {
            wp_send_json_error(array('message' => 'Message is required'));
            return;
        }
        
        // Get OpenAI API key
        $openai_api_key = get_option('sf_openai_api_key', '');
        
        if (empty($openai_api_key)) {
            wp_send_json_error(array(
                'message' => 'OpenAI API key is not configured. Please add it in Settings.',
                'requires_setup' => true
            ));
            return;
        }
        
        // Get all available settings for context
        // Note: scan_all_theme_options() already includes core settings via scan_core_wordpress_settings()
        $all_settings = $this->scan_all_theme_options();
        
        // Log for debugging (only if WP_DEBUG is enabled)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Settings Finder AI: Total settings found: ' . count($all_settings));
        }
        
        // Get theme information
        $theme = wp_get_theme();
        $theme_name = $theme->get('Name');
        
        // Format settings for AI context
        $settings_context = $this->format_settings_for_ai($all_settings, $theme_name, count($all_settings));
        
        // Log context length for debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Settings Finder AI: Context length: ' . strlen($settings_context) . ' characters');
            error_log('Settings Finder AI: User question: ' . $user_message);
        }
        
        // Call OpenAI API
        $response = $this->call_openai_api($user_message, $settings_context, $openai_api_key);
        
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => $response->get_error_message()));
            return;
        }
        
        // Save chat history
        $history = get_user_meta(get_current_user_id(), 'sf_ai_chat_history', true);
        if (!is_array($history)) {
            $history = array();
        }
        
        // Add new conversation to history
        $history[] = array(
            'user' => $user_message,
            'bot' => $response['message'],
            'urls' => isset($response['urls']) ? $response['urls'] : array(),
            'timestamp' => current_time('timestamp')
        );
        
        // Keep only last 50 conversations to prevent excessive storage
        if (count($history) > 50) {
            $history = array_slice($history, -50);
        }
        
        // Save updated history
        update_user_meta(get_current_user_id(), 'sf_ai_chat_history', $history);
        
        wp_send_json_success($response);
    }
    
    /**
     * Format settings for AI context
     * 
     * @param array $settings All available settings
     * @param string $theme_name Active theme name
     * @param int $total_count Total number of settings
     * @return string Formatted context string
     */
    private function format_settings_for_ai($settings, $theme_name = '', $total_count = 0) {
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
                
                // Location/Path (important for navigation instructions)
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
    
    /**
     * Call OpenAI API
     * 
     * @param string $user_message User's question
     * @param string $settings_context Formatted settings context
     * @param string $api_key OpenAI API key
     * @return array|WP_Error Response data or error
     */
    private function call_openai_api($user_message, $settings_context, $api_key) {
        $system_prompt = "You are an expert WordPress settings assistant. Your job is to help users find and navigate to WordPress settings.

CRITICAL INSTRUCTIONS:
1. You have access to a complete database of ALL available WordPress settings for this specific site
2. You MUST ONLY answer questions about settings that exist in the provided settings database
3. If a user asks about something NOT in the database, politely say: 'I don't see that setting available in your WordPress installation. It might not be configured or might be part of a plugin that needs to be activated.'
4. When answering, ALWAYS:
   - Match the user's question to settings in the database by checking: name, description, keywords, and location
   - Provide the exact setting name from the database
   - Explain the location path (e.g., 'Appearance > Customize > Colors')
   - Include step-by-step navigation instructions
   - ALWAYS include the COMPLETE Direct URL in format [URL:complete_full_url_here] for every relevant setting
   - The URL must be COMPLETE from http:// or https:// to the end, including ALL query parameters
   - Example: [URL:http://yoursite.com/wp-admin/customize.php?autofocus[control]=neve_global_colors]
   - Do NOT split URLs - include the ENTIRE URL exactly as shown in the Direct URL field
5. If multiple settings match the question, list ALL of them
6. Be friendly, conversational, and helpful
7. Use the 'Location' field to give clear navigation instructions
8. Reference the 'Description' field to explain what the setting does

RESPONSE FORMAT:
- Start with a direct answer to their question
- Mention the exact setting name(s) from the database
- Provide clear step-by-step instructions using the Location path
- Include ALL relevant Direct URLs using [URL:url] format
- Be concise but thorough

EXAMPLE RESPONSES:
User: How do I change the logo?
You: To change your site logo, go to Appearance > Customize > Site Identity. Look for the Logo or Site Logo option. [URL:http://yoursite.com/wp-admin/customize.php?autofocus[control]=custom_logo]

User: Where are color settings?
You: Color settings can be found in Appearance > Customize. Here are the available color options: [list all color-related settings with URLs]

Now, here is the complete settings database for this WordPress site:";

        $messages = array(
            array(
                'role' => 'system',
                'content' => $system_prompt . "\n\n" . $settings_context
            ),
            array(
                'role' => 'user',
                'content' => $user_message
            )
        );
        
        $api_url = 'https://api.openai.com/v1/chat/completions';
        
        // Try gpt-4o-mini first, fallback to gpt-3.5-turbo
        $models = array('gpt-4o-mini', 'gpt-3.5-turbo');
        $last_error = null;
        
        foreach ($models as $model) {
            $body = array(
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => 1000,
                'temperature' => 0.3 // Lower temperature for more consistent, accurate responses
            );
            
            $response = wp_remote_post($api_url, array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json'
                ),
                'body' => json_encode($body),
                'timeout' => 30
            ));
            
            if (is_wp_error($response)) {
                $last_error = $response;
                continue; // Try next model
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = json_decode(wp_remote_retrieve_body($response), true);
            
            if ($response_code !== 200) {
                $error_message = isset($response_body['error']['message']) 
                    ? $response_body['error']['message'] 
                    : 'OpenAI API error: ' . $response_code;
                
                // If it's a model-specific error, try next model
                if (isset($response_body['error']['code']) && $response_body['error']['code'] === 'model_not_found') {
                    $last_error = new WP_Error('openai_error', $error_message);
                    continue;
                }
                
                return new WP_Error('openai_error', $error_message);
            }
            
            if (!isset($response_body['choices'][0]['message']['content'])) {
                $last_error = new WP_Error('openai_error', 'Invalid response from OpenAI API');
                continue;
            }
            
            $ai_response = $response_body['choices'][0]['message']['content'];
            
            // Parse URLs from response
            // The regex needs to handle brackets in URLs like autofocus[control]=value
            // We'll match [URL:...] where the closing ] is followed by whitespace, punctuation, or end of string
            $urls = array();
            // Match [URL:...] where ... can contain brackets, but the closing ] is followed by space/punctuation/end
            // This pattern: [URL:...] where ] is followed by (space|end|punctuation) to distinguish from brackets in URL
            // Match [URL:...] where the closing ] is followed by whitespace, punctuation, or end
            // Use a greedy match that captures everything until ] followed by space/punctuation/end
            preg_match_all('/\[URL:([^\]]*(?:\[[^\]]*\][^\]]*)*)\](?=\s|$|\.|,|;|\)|:|\n|\[)/', $ai_response, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $url) {
                    $url = trim($url);
                    // URLs with brackets in query strings won't pass filter_var, so check differently
                    // Accept if it looks like a URL (contains http, https, wp-admin, customize.php, etc.)
                    if (preg_match('/^(https?:\/\/|\/|wp-admin)/', $url) || 
                        strpos($url, 'customize.php') !== false || 
                        strpos($url, 'admin.php') !== false ||
                        strpos($url, 'options-') !== false ||
                        strpos($url, 'autofocus') !== false) {
                        // Ensure it's a complete URL - if it starts with wp-admin, prepend site URL
                        if (strpos($url, 'wp-admin') === 0) {
                            $url = admin_url($url);
                        } elseif (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
                            // Relative URL starting with /, make it absolute
                            $url = home_url($url);
                        } elseif (!preg_match('/^https?:\/\//', $url)) {
                            // If it doesn't start with http:// or https://, try to make it absolute
                            if (strpos($url, 'customize.php') !== false || strpos($url, 'admin.php') !== false) {
                                $url = admin_url($url);
                            }
                        }
                        $urls[] = $url;
                    }
                }
                // Remove URL markers from response - match the same pattern
                $ai_response = preg_replace('/\[URL:([^\]]*(?:\[[^\]]*\][^\]]*)*)\](?=\s|$|\.|,|;|\)|:|\n|\[)/', '', $ai_response);
            }
            
            // Log successful response for debugging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Settings Finder AI: Response received from ' . $model);
                error_log('Settings Finder AI: URLs found: ' . count($urls));
            }
            
            return array(
                'message' => trim($ai_response),
                'urls' => $urls
            );
        }
        
        // If all models failed, return the last error
        if ($last_error) {
            return $last_error;
        }
        
        return new WP_Error('openai_error', 'Failed to get response from OpenAI API');
    }
}

// Load autoloader
require_once SF_PLUGIN_DIR . 'includes/class-autoloader.php';
OptionMap_Autoloader::register();

// Initialize legacy SettingsFinder for backward compatibility
// This ensures all existing methods continue to work during migration
SettingsFinder::get_instance();

// Initialize new OOP plugin structure
OptionMap_Plugin::get_instance();

// Add admin notice
add_action('admin_notices', function() {
    if (isset($_GET['page']) && strpos($_GET['page'], 'settings-finder') !== false) {
        // Notice removed per user request
    }
});

