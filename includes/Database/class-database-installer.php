<?php
/**
 * Database Installer class
 *
 * Handles database table creation and updates
 *
 * @package OptionMap
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database Installer class
 */
class OptionMap_Database_Installer {

    /**
     * Create database table
     */
    public function create_table() {
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
     * Run installation
     */
    public function install() {
        $this->create_table();
        update_option('sf_version', SF_VERSION);
        update_option('sf_last_scan', current_time('timestamp'));
    }
}

