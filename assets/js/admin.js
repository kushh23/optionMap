/* Settings Finder Admin JavaScript */

(function($) {
    'use strict';

    window.SF_Admin = {
        
        init: function() {
            this.bindEvents();
            this.initSearch();
            this.initQuickCards();
        },
        
        bindEvents: function() {
            const self = this;
            
            // Search input
            $('#sf-search-input').on('input', this.debounce(function() {
                self.performSearch();
            }, 300));
            
            // Enter key
            $('#sf-search-input').on('keypress', function(e) {
                if (e.which === 13) {
                    self.performSearch();
                }
            });
        },
        
        initSearch: function() {
            this.allSettings = $('.sf-setting-card').clone();
        },
        
        performSearch: function() {
            const query = $('#sf-search-input').val().toLowerCase();
            const $grid = $('#sf-settings-grid');
            const $emptyState = $('.sf-empty-state');
            
            if (query === '') {
                $grid.html(this.allSettings.clone());
                $emptyState.hide();
                return;
            }
            
            const $filtered = this.allSettings.filter(function() {
                const $card = $(this);
                const text = $card.text().toLowerCase();
                const keywords = $card.data('keywords') || '';
                return text.includes(query) || keywords.includes(query);
            });
            
            if ($filtered.length > 0) {
                $grid.html($filtered.clone());
                $emptyState.hide();
            } else {
                $grid.html('');
                $emptyState.show();
            }
        },
        
        quickSearch: function(term) {
            $('#sf-search-input').val(term);
            this.performSearch();
        },
        
        // Handle quick card clicks (for new card structure)
        initQuickCards: function() {
            const self = this;
            $('.sf-quick-card').on('click', function() {
                const title = $(this).find('.sf-quick-title').text();
                // Extract search term from title (e.g., "Logo & Title" -> "logo")
                const searchTerm = title.toLowerCase().split(' ')[0];
                self.quickSearch(searchTerm);
            });
        },
        
        selectCategory: function(element, category) {
            $('.sf-category-item').removeClass('active');
            $(element).addClass('active');
            
            const $grid = $('#sf-settings-grid');
            const $emptyState = $('.sf-empty-state');
            
            if (category === 'all') {
                $grid.html(this.allSettings.clone());
                $emptyState.hide();
            } else {
                const $filtered = this.allSettings.filter(function() {
                    return $(this).data('category') === category;
                });
                
                if ($filtered.length > 0) {
                    $grid.html($filtered.clone());
                    $emptyState.hide();
                } else {
                    $grid.html('');
                    $emptyState.show();
                }
            }
        },
        
        debounce: function(func, wait) {
            let timeout;
            return function() {
                const context = this;
                const args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    func.apply(context, args);
                }, wait);
            };
        }
    };
    
    // Initialize
    $(document).ready(function() {
        if ($('.settings-finder-wrap').length > 0) {
            SF_Admin.init();
        }
    });
    
})(jQuery);