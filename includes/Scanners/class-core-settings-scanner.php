<?php
/**
 * Core Settings Scanner class
 *
 * Scans WordPress core settings
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
 * Core Settings Scanner class
 */
class OptionMap_Core_Settings_Scanner extends OptionMap_Scanner_Base {

    /**
     * Scan for settings
     *
     * @return array Array of settings
     */
    public function scan() {
        $settings = array();
        
        // Comprehensive list of WordPress core settings
        $core_settings = array(
            // General Settings
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
            $settings[] = $this->normalize_setting(array_merge($setting, array(
                'type' => 'core_setting',
                'source' => 'WordPress Core'
            )));
        }
        
        return $settings;
    }
}

