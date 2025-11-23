<?php
/**
 * OpenAI Provider class
 *
 * Handles communication with OpenAI API
 *
 * @package OptionMap
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Ensure interface is loaded first
if (!interface_exists('OptionMap_AI_Provider_Interface')) {
    require_once SF_PLUGIN_DIR . 'includes/AI/interface-ai-provider.php';
}

/**
 * OpenAI Provider class
 */
class OptionMap_OpenAI_Provider implements OptionMap_AI_Provider_Interface {

    /**
     * API key
     *
     * @var string
     */
    private $api_key;

    /**
     * Constructor
     *
     * @param string $api_key OpenAI API key
     */
    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    /**
     * Send message to AI and get response
     *
     * @param string $user_message User's message
     * @param string $context Context/settings data
     * @return array|WP_Error Response data or error
     */
    public function chat($user_message, $context) {
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
                'content' => $system_prompt . "\n\n" . $context
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
                'temperature' => 0.3
            );
            
            $response = wp_remote_post($api_url, array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type' => 'application/json'
                ),
                'body' => json_encode($body),
                'timeout' => 30
            ));
            
            if (is_wp_error($response)) {
                $last_error = $response;
                continue;
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = json_decode(wp_remote_retrieve_body($response), true);
            
            if ($response_code !== 200) {
                $error_message = isset($response_body['error']['message']) 
                    ? $response_body['error']['message'] 
                    : 'OpenAI API error: ' . $response_code;
                
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
            $urls = array();
            preg_match_all('/\[URL:([^\]]*(?:\[[^\]]*\][^\]]*)*)\](?=\s|$|\.|,|;|\)|:|\n|\[)/', $ai_response, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $url) {
                    $url = trim($url);
                    if (preg_match('/^(https?:\/\/|\/|wp-admin)/', $url) || 
                        strpos($url, 'customize.php') !== false || 
                        strpos($url, 'admin.php') !== false ||
                        strpos($url, 'options-') !== false ||
                        strpos($url, 'autofocus') !== false) {
                        if (strpos($url, 'wp-admin') === 0) {
                            $url = admin_url($url);
                        } elseif (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
                            $url = home_url($url);
                        } elseif (!preg_match('/^https?:\/\//', $url)) {
                            if (strpos($url, 'customize.php') !== false || strpos($url, 'admin.php') !== false) {
                                $url = admin_url($url);
                            }
                        }
                        $urls[] = $url;
                    }
                }
                $ai_response = preg_replace('/\[URL:([^\]]*(?:\[[^\]]*\][^\]]*)*)\](?=\s|$|\.|,|;|\)|:|\n|\[)/', '', $ai_response);
            }
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Settings Finder AI: Response received from ' . $model);
                error_log('Settings Finder AI: URLs found: ' . count($urls));
            }
            
            return array(
                'message' => trim($ai_response),
                'urls' => $urls
            );
        }
        
        return $last_error ? $last_error : new WP_Error('openai_error', 'Failed to get response from OpenAI API');
    }
}

