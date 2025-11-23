/**
 * AI Search Chat Interface JavaScript
 */

(function($) {
    'use strict';
    
    const AI_Search = {
        chatMessages: null,
        chatForm: null,
        chatInput: null,
        chatSend: null,
        loadingIndicator: null,
        clearHistoryBtn: null,
        settings: null,
        chatHistory: [],
        
        init: function() {
            // Get elements
            this.chatMessages = $('#sf-ai-chat-messages');
            this.chatForm = $('#sf-ai-chat-form');
            this.chatInput = $('#sf-ai-chat-input');
            this.chatSend = $('#sf-ai-chat-send');
            this.loadingIndicator = $('#sf-ai-loading');
            this.clearHistoryBtn = $('#sf-ai-clear-history');
            
            // Get settings from window object
            if (typeof window.sfAISettings !== 'undefined') {
                this.settings = window.sfAISettings;
            }
            
            // Bind events
            this.chatForm.on('submit', this.handleSubmit.bind(this));
            this.chatInput.on('keydown', this.handleKeyDown.bind(this));
            this.clearHistoryBtn.on('click', this.clearHistory.bind(this));
            
            // Load chat history
            this.loadHistory();
            
            // Focus input on load
            this.chatInput.focus();
        },
        
        loadHistory: function() {
            $.ajax({
                url: this.settings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sf_ai_chat',
                    action_get_history: 'true',
                    nonce: this.settings.nonce
                },
                success: (response) => {
                    if (response.success && response.data.history && response.data.history.length > 0) {
                        this.chatHistory = response.data.history;
                        
                        // Clear the welcome message
                        this.chatMessages.find('.sf-ai-message-bot').first().remove();
                        
                        // Load all history messages
                        response.data.history.forEach((item) => {
                            this.addMessage('user', item.user);
                            this.addMessage('bot', item.bot, item.urls || []);
                        });
                    }
                },
                error: () => {
                    // Silently fail - history is optional
                }
            });
        },
        
        clearHistory: function() {
            if (!confirm('Are you sure you want to clear the chat history?')) {
                return;
            }
            
            $.ajax({
                url: this.settings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sf_ai_chat',
                    action_clear: 'true',
                    nonce: this.settings.nonce
                },
                success: (response) => {
                    if (response.success) {
                        // Clear chat messages
                        this.chatMessages.empty();
                        
                        // Add welcome message back
                        this.addMessage('bot', 
                            'Hello! I\'m your AI assistant. I can help you find any WordPress setting on your site.\n\n' +
                            '<strong>Try asking me:</strong>\n' +
                            '• "How do I change the site logo?"\n' +
                            '• "Where can I modify the site colors?"\n' +
                            '• "How to change the homepage display?"\n' +
                            '• "Where are the comment settings?"\n\n' +
                            'I\'ll provide step-by-step instructions and direct links to the settings!'
                        );
                        
                        this.chatHistory = [];
                    }
                },
                error: () => {
                    alert('Failed to clear chat history. Please try again.');
                }
            });
        },
        
        handleSubmit: function(e) {
            e.preventDefault();
            
            const message = this.chatInput.val().trim();
            
            if (!message) {
                return;
            }
            
            // Check if API key is configured
            if (!this.settings || !this.settings.apiKey) {
                this.addMessage('bot', '⚠️ OpenAI API key is not configured. Please add it in Settings to use AI features.');
                return;
            }
            
            // Add user message to chat
            this.addMessage('user', message);
            
            // Clear input
            this.chatInput.val('');
            
            // Show loading
            this.showLoading();
            
            // Send to server
            this.sendMessage(message);
        },
        
        handleKeyDown: function(e) {
            // Allow Enter to submit, but Shift+Enter for new line
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.chatForm.submit();
            }
        },
        
        sendMessage: function(message) {
            $.ajax({
                url: this.settings.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sf_ai_chat',
                    message: message,
                    nonce: this.settings.nonce
                },
                success: (response) => {
                    this.hideLoading();
                    
                    if (response.success) {
                        this.addMessage('bot', response.data.message, response.data.urls || []);
                    } else {
                        let errorMsg = response.data.message || 'An error occurred. Please try again.';
                        
                        if (response.data.requires_setup) {
                            errorMsg += ' <a href="' + this.settings.adminUrl + 'admin.php?page=settings-finder" target="_blank">Configure API Key</a>';
                        }
                        
                        this.addMessage('bot', errorMsg);
                    }
                },
                error: (xhr, status, error) => {
                    this.hideLoading();
                    let errorMsg = 'Failed to connect to AI service. Please try again.';
                    
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMsg = xhr.responseJSON.data.message;
                    }
                    
                    this.addMessage('bot', errorMsg);
                }
            });
        },
        
        addMessage: function(type, message, urls = []) {
            const messageDiv = $('<div>').addClass('sf-ai-message').addClass('sf-ai-message-' + type);
            
            // Avatar
            const avatar = $('<div>').addClass('sf-ai-avatar');
            avatar.text(type === 'user' ? '👤' : '🤖');
            
            // Content
            const content = $('<div>').addClass('sf-ai-content');
            
            // Parse message (support basic markdown-like formatting)
            const formattedMessage = this.formatMessage(message);
            content.html(formattedMessage);
            
            // Add URL buttons if available
            if (urls && urls.length > 0) {
                const urlButtons = $('<div>').addClass('sf-ai-url-buttons');
                
                urls.forEach((url) => {
                    const button = $('<a>')
                        .addClass('sf-ai-url-button')
                        .attr('href', url)
                        .attr('target', '_blank')
                        .text('Go to Setting →');
                    urlButtons.append(button);
                });
                
                content.append(urlButtons);
            }
            
            messageDiv.append(avatar);
            messageDiv.append(content);
            
            this.chatMessages.append(messageDiv);
            
            // Scroll to bottom
            this.scrollToBottom();
        },
        
        formatMessage: function(message) {
            // Convert line breaks
            message = message.replace(/\n/g, '<br>');
            
            // Convert **bold** to <strong>
            message = message.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
            
            // Convert *italic* to <em>
            message = message.replace(/\*(.+?)\*/g, '<em>$1</em>');
            
            // Convert numbered lists
            message = message.replace(/^\d+\.\s(.+)$/gm, '<li>$1</li>');
            message = message.replace(/(<li>.*<\/li>)/s, '<ol>$1</ol>');
            
            // Convert bullet lists
            message = message.replace(/^[-*]\s(.+)$/gm, '<li>$1</li>');
            message = message.replace(/(<li>.*<\/li>)/s, '<ul>$1</ul>');
            
            // Wrap paragraphs
            const paragraphs = message.split('<br><br>');
            if (paragraphs.length > 1) {
                message = paragraphs.map(p => {
                    p = p.trim();
                    if (p && !p.match(/^<(ul|ol|li|strong|em)/)) {
                        return '<p>' + p + '</p>';
                    }
                    return p;
                }).join('');
            } else if (message && !message.match(/^<(ul|ol|p|li|strong|em)/)) {
                message = '<p>' + message + '</p>';
            }
            
            return message;
        },
        
        showLoading: function() {
            this.loadingIndicator.show();
            this.chatSend.prop('disabled', true);
            this.chatInput.prop('disabled', true);
        },
        
        hideLoading: function() {
            this.loadingIndicator.hide();
            this.chatSend.prop('disabled', false);
            this.chatInput.prop('disabled', false);
            this.chatInput.focus();
        },
        
        scrollToBottom: function() {
            this.chatMessages.scrollTop(this.chatMessages[0].scrollHeight);
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        // Only initialize on AI search page
        if ($('.sf-ai-search-wrap').length > 0) {
            AI_Search.init();
        }
    });
    
})(jQuery);

