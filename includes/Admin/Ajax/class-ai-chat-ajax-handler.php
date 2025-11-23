<?php
/**
 * AI Chat AJAX Handler class
 *
 * Handles AI chat AJAX requests
 *
 * @package OptionMap
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Ensure base class is loaded
if (!class_exists('OptionMap_Ajax_Handler_Base')) {
    require_once SF_PLUGIN_DIR . 'includes/Admin/Ajax/abstract-class-ajax-handler.php';
}

/**
 * AI Chat AJAX Handler class
 */
class OptionMap_AI_Chat_Ajax_Handler extends OptionMap_Ajax_Handler_Base {

    /**
     * Settings Manager
     *
     * @var OptionMap_Settings_Manager
     */
    private $settings_manager;

    /**
     * Scanner Factory
     *
     * @var OptionMap_Scanner_Factory
     */
    private $scanner_factory;

    /**
     * Settings Aggregator
     *
     * @var OptionMap_Settings_Aggregator
     */
    private $aggregator;

    /**
     * Settings Formatter
     *
     * @var OptionMap_Settings_Formatter
     */
    private $formatter;

    /**
     * AI Provider
     *
     * @var OptionMap_AI_Provider_Interface
     */
    private $ai_provider;

    /**
     * Chat History Manager
     *
     * @var OptionMap_Chat_History_Manager
     */
    private $history_manager;

    /**
     * Legacy SettingsFinder instance
     *
     * @var SettingsFinder
     */
    private $legacy_finder;

    /**
     * Constructor
     *
     * @param OptionMap_Settings_Manager $settings_manager Settings manager
     * @param OptionMap_Scanner_Factory $scanner_factory Scanner factory
     * @param OptionMap_Settings_Aggregator $aggregator Settings aggregator
     * @param OptionMap_Settings_Formatter $formatter Settings formatter
     * @param OptionMap_Chat_History_Manager $history_manager Chat history manager
     * @param SettingsFinder $legacy_finder Legacy finder instance
     */
    public function __construct(
        OptionMap_Settings_Manager $settings_manager,
        OptionMap_Scanner_Factory $scanner_factory,
        OptionMap_Settings_Aggregator $aggregator,
        OptionMap_Settings_Formatter $formatter,
        OptionMap_Chat_History_Manager $history_manager,
        $legacy_finder = null
    ) {
        parent::__construct('sf_ai_chat');
        
        $this->settings_manager = $settings_manager;
        $this->scanner_factory = $scanner_factory;
        $this->aggregator = $aggregator;
        $this->formatter = $formatter;
        $this->history_manager = $history_manager;
        $this->legacy_finder = $legacy_finder;
    }

    /**
     * Handle AJAX request
     */
    public function handle() {
        $this->verify_nonce();
        $this->verify_capability();
        
        // Handle clear history request
        if (isset($_POST['action_clear']) && $_POST['action_clear'] === 'true') {
            $this->history_manager->clear_history();
            wp_send_json_success(array('message' => 'Chat history cleared'));
            return;
        }
        
        // Handle get history request
        if (isset($_POST['action_get_history']) && $_POST['action_get_history'] === 'true') {
            $history = $this->history_manager->get_history();
            wp_send_json_success(array('history' => $history));
            return;
        }
        
        $user_message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
        
        if (empty($user_message)) {
            wp_send_json_error(array('message' => 'Message is required'));
            return;
        }
        
        // Get OpenAI API key
        $openai_api_key = $this->settings_manager->get_openai_api_key();
        
        if (empty($openai_api_key)) {
            wp_send_json_error(array(
                'message' => 'OpenAI API key is not configured. Please add it in Settings.',
                'requires_setup' => true
            ));
            return;
        }
        
        // Initialize AI provider
        if (!$this->ai_provider) {
            $this->ai_provider = new OptionMap_OpenAI_Provider($openai_api_key);
        }
        
        // Get all settings from scanners (includes core settings)
        $scanner_results = $this->scanner_factory->scan_all();
        $all_settings = $this->aggregator->aggregate(array_values($scanner_results));
        
        // Get theme information
        $theme = wp_get_theme();
        $theme_name = $theme->get('Name');
        
        // Format settings for AI context
        $settings_context = $this->formatter->format_for_ai($all_settings, $theme_name, count($all_settings));
        
        // Call AI provider
        $response = $this->ai_provider->chat($user_message, $settings_context);
        
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => $response->get_error_message()));
            return;
        }
        
        // Save chat history
        $this->history_manager->add_conversation(
            $user_message,
            $response['message'],
            isset($response['urls']) ? $response['urls'] : array()
        );
        
        wp_send_json_success($response);
    }
}

