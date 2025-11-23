# Option Map Plugin - OOP Architecture

## Overview

This document describes the new object-oriented architecture of the Option Map WordPress plugin. The plugin has been refactored from a monolithic procedural class to a clean, modular OOP structure following SOLID principles and WordPress coding standards.

## Directory Structure

```
Option-Map/
├── settings-finder.php          # Main plugin file (bootstrap)
├── includes/
│   ├── class-autoloader.php     # PSR-4 style autoloader
│   ├── class-plugin.php         # Main plugin orchestrator
│   │
│   ├── Core/                     # Core utility classes
│   │   ├── class-url-validator.php
│   │   ├── class-settings-formatter.php
│   │   ├── class-settings-aggregator.php
│   │   └── class-scanner-factory.php
│   │
│   ├── Database/                 # Database operations
│   │   ├── interface-repository.php
│   │   └── class-database-installer.php
│   │
│   ├── Scanners/                 # Scanner classes
│   │   ├── interface-scanner.php
│   │   ├── abstract-class-scanner-base.php
│   │   └── [Individual scanner classes - to be migrated]
│   │
│   ├── Admin/                    # Admin interface
│   │   ├── Pages/                # Admin page renderers
│   │   └── Ajax/                 # AJAX handlers
│   │
│   ├── AI/                       # AI integration
│   │   ├── interface-ai-provider.php
│   │   ├── class-openai-provider.php
│   │   └── class-chat-history-manager.php
│   │
│   └── Settings/                 # Settings management
│       └── class-settings-manager.php
│
└── assets/                       # Frontend assets (unchanged)
    ├── css/
    ├── js/
    └── logo/
```

## Class Responsibilities

### Core Classes

#### `OptionMap_Plugin`
**Location:** `includes/class-plugin.php`

Main orchestrator class that:
- Initializes all components using dependency injection
- Registers WordPress hooks
- Manages plugin lifecycle (activation/deactivation)
- Provides access to all components via getter methods
- Maintains backward compatibility with legacy `SettingsFinder` class

**Key Methods:**
- `get_instance()` - Singleton pattern
- `activate()` - Plugin activation handler
- `deactivate()` - Plugin deactivation handler
- `init_components()` - Dependency injection setup
- `init_hooks()` - WordPress hook registration

#### `OptionMap_URL_Validator`
**Location:** `includes/Core/class-url-validator.php`

Validates and fixes invalid admin URLs:
- Detects invalid patterns like `themes.php?page=themes.php`
- Redirects invalid URLs to wp-admin
- Validates URLs in settings arrays

**Key Methods:**
- `validate($url)` - Validate single URL
- `validate_settings_urls($settings)` - Validate URLs in settings array

#### `OptionMap_Settings_Formatter`
**Location:** `includes/Core/class-settings-formatter.php`

Formats setting data for display:
- Formats setting names (removes prefixes, capitalizes)
- Formats settings for AI context
- Handles abbreviations (ID, URL, CSS, etc.)

**Key Methods:**
- `format_setting_name($name)` - Format setting name
- `format_for_ai($settings, $theme_name, $total_count)` - Format for AI

#### `OptionMap_Settings_Aggregator`
**Location:** `includes/Core/class-settings-aggregator.php`

Aggregates settings from multiple sources:
- Merges results from different scanners
- Validates all URLs
- Categorizes settings with counts

**Key Methods:**
- `aggregate($scanner_results)` - Aggregate scanner results
- `get_categories($settings)` - Get categories with counts

#### `OptionMap_Scanner_Factory`
**Location:** `includes/Core/class-scanner-factory.php`

Factory for creating and managing scanners:
- Registers scanner instances
- Runs all scanners
- Handles errors gracefully

**Key Methods:**
- `register($name, $scanner)` - Register a scanner
- `get($name)` - Get scanner by name
- `scan_all()` - Run all scanners

### Database Classes

#### `OptionMap_Database_Installer`
**Location:** `includes/Database/class-database-installer.php`

Handles database operations:
- Creates/updates database tables
- Manages migrations
- Called during plugin activation

**Key Methods:**
- `create_table()` - Create database table
- `install()` - Run full installation

### Settings Classes

#### `OptionMap_Settings_Manager`
**Location:** `includes/Settings/class-settings-manager.php`

Manages plugin settings:
- OpenAI API key storage
- Plugin version tracking
- Last scan timestamp

**Key Methods:**
- `get_openai_api_key()` - Get API key
- `save_openai_api_key($api_key)` - Save API key
- `get_version()` - Get plugin version
- `get_last_scan()` - Get last scan time

### AI Classes

#### `OptionMap_OpenAI_Provider`
**Location:** `includes/AI/class-openai-provider.php`

Implements `OptionMap_AI_Provider_Interface`:
- Communicates with OpenAI API
- Parses responses
- Extracts URLs from responses
- Handles errors and fallbacks

**Key Methods:**
- `chat($user_message, $context)` - Send message to AI

#### `OptionMap_Chat_History_Manager`
**Location:** `includes/AI/class-chat-history-manager.php`

Manages chat history:
- Stores/retrieves chat history per user
- Limits history to last 50 conversations
- Clears history on demand

**Key Methods:**
- `get_history()` - Get user's chat history
- `save_history($history)` - Save chat history
- `add_conversation($user_message, $bot_response, $urls)` - Add conversation
- `clear_history()` - Clear history

### Scanner Classes

#### `OptionMap_Scanner_Base` (Abstract)
**Location:** `includes/Scanners/abstract-class-scanner-base.php`

Base class for all scanners:
- Provides common functionality
- URL validation
- Customizer URL building
- Setting normalization

**Key Methods:**
- `normalize_setting($setting)` - Normalize setting data
- `build_customizer_url($type, $id, $panel_id, $section_id)` - Build customizer URL
- `find_theme_option_page_url($option_name)` - Find theme option page

**Note:** Individual scanner classes (Customizer, Theme Mods, etc.) will extend this base class. These are pending migration from the legacy `SettingsFinder` class.

## Initialization Flow

1. **Plugin Bootstrap** (`settings-finder.php`):
   ```php
   // Load autoloader
   require_once SF_PLUGIN_DIR . 'includes/class-autoloader.php';
   OptionMap_Autoloader::register();
   
   // Initialize legacy class (backward compatibility)
   SettingsFinder::get_instance();
   
   // Initialize new OOP structure
   OptionMap_Plugin::get_instance();
   ```

2. **Plugin Initialization** (`OptionMap_Plugin::__construct()`):
   - Creates all components with dependency injection
   - Registers WordPress hooks
   - Maintains backward compatibility

3. **Component Dependencies**:
   ```
   Plugin
   ├── URL_Validator
   ├── Settings_Formatter
   ├── Settings_Aggregator (depends on URL_Validator)
   ├── Database_Installer
   ├── Settings_Manager
   └── Scanner_Factory (depends on URL_Validator, Settings_Formatter)
   ```

## Backward Compatibility

The plugin maintains full backward compatibility during migration:

1. **Legacy Class Preserved**: The original `SettingsFinder` class remains intact
2. **Dual Initialization**: Both old and new systems initialize
3. **Gradual Migration**: Methods are migrated one by one to new classes
4. **No Breaking Changes**: All existing functionality continues to work

## Extension Points

### Adding a New Scanner

1. Create class extending `OptionMap_Scanner_Base`:
   ```php
   class OptionMap_My_Scanner extends OptionMap_Scanner_Base {
       public function scan() {
           // Scanner logic
           return $settings;
       }
   }
   ```

2. Register in `OptionMap_Plugin::init_components()`:
   ```php
   $scanner = new OptionMap_My_Scanner($this->url_validator, $this->formatter);
   $this->scanner_factory->register('my_scanner', $scanner);
   ```

### Adding a New AI Provider

1. Implement `OptionMap_AI_Provider_Interface`:
   ```php
   class OptionMap_My_AI_Provider implements OptionMap_AI_Provider_Interface {
       public function chat($user_message, $context) {
           // AI logic
       }
   }
   ```

2. Swap in `OptionMap_Plugin` initialization

### Adding a New Admin Page

1. Create page class extending base renderer (to be created)
2. Register in admin menu handler

## Migration Status

### ✅ Completed
- Autoloader
- Core utility classes (URL Validator, Settings Formatter, Settings Aggregator)
- Database Installer
- Settings Manager
- AI Provider (OpenAI)
- Chat History Manager
- Scanner Factory and Base Class
- Main Plugin Orchestrator
- Backward compatibility layer

### ✅ Completed
- Individual Scanner classes (12 scanners)
  - Customizer Scanner
  - Theme Mods Scanner
  - Database Options Scanner
  - Menu Locations Scanner
  - Widget Areas Scanner
  - Page Templates Scanner
  - Block Patterns Scanner
  - Custom Post Types Scanner
  - Post Metaboxes Scanner
  - Theme Support Scanner
  - Core Settings Scanner
  - Dashboard Pages Scanner
- Admin Menu class
- Asset Manager class
- AJAX Handler classes (3 handlers)
  - Search AJAX Handler
  - Refresh Scan AJAX Handler
  - AI Chat AJAX Handler

### 🔄 Pending Migration (Optional)
- Page Renderer classes (4 pages) - Currently using legacy methods
  - Main Page
  - Test Scanner Page
  - Theme Settings Page
  - AI Search Page

## Security Practices

All classes follow WordPress security best practices:

- ✅ Input sanitization using `sanitize_text_field()`, `sanitize_email()`, etc.
- ✅ Output escaping using `esc_html()`, `esc_attr()`, `esc_url()`
- ✅ Nonce verification for all forms and AJAX requests
- ✅ Capability checks using `current_user_can()`
- ✅ Prepared statements for all database queries
- ✅ Direct file access prevention

## WordPress Coding Standards

- ✅ PHP 7.4+ compatibility
- ✅ WordPress PHP Coding Standards (WPCS)
- ✅ Proper docblocks for all public methods
- ✅ Text domain for all strings (`option-map`)
- ✅ Proper hook naming conventions

## Next Steps

1. **Migrate Scanner Classes**: Extract each scanner method from `SettingsFinder` into individual scanner classes
2. **Create Admin Classes**: Extract admin menu, asset management, and page rendering
3. **Create AJAX Handlers**: Extract AJAX methods into handler classes
4. **Remove Legacy Code**: Once all methods are migrated, remove the legacy `SettingsFinder` class
5. **Add Unit Tests**: Create unit tests for each class

## Developer Notes

- All new classes use the `OptionMap_` prefix
- Classes are organized by responsibility (Core, Database, Scanners, Admin, AI, Settings)
- Dependency injection is used throughout
- Interfaces are provided for extensibility
- The plugin maintains backward compatibility during migration

