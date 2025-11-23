# Option Map - WordPress Settings Finder

🔍 **Find any WordPress setting in seconds. Stop hunting through menus - search and discover all settings in one friendly place.**

[![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](LICENSE)

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Installation](#installation)
- [Usage](#usage)
- [Architecture](#architecture)
- [Development](#development)
- [Contributing](#contributing)
- [License](#license)

## 🎯 Overview

Option Map is a powerful WordPress plugin that helps you find and access any WordPress setting quickly and easily. Instead of navigating through multiple menus and submenus, simply search for what you need and get instant access.

### What Problem Does It Solve?

WordPress has hundreds of settings scattered across different admin pages:
- General Settings
- Appearance > Customize
- Theme-specific options
- Plugin settings
- Custom post types
- And much more...

Finding the right setting can be time-consuming. Option Map scans your entire WordPress installation, indexes all available settings, and provides a unified search interface.

## ✨ Features

### 🔍 Universal Search
- **Smart Search**: Search across all WordPress settings using keywords
- **Category Filtering**: Browse settings by category (General, Appearance, Content, Media, SEO)
- **Real-time Results**: Instant search results as you type

### 📊 Comprehensive Scanning
Option Map automatically scans and indexes:

- **WordPress Core Settings** (30+ settings)
  - General, Writing, Reading, Discussion, Media, Permalink settings
  - All core WordPress configuration options

- **Theme Settings**
  - Customizer panels, sections, and controls
  - Theme modifications (theme_mods)
  - Theme database options
  - Menu locations
  - Widget areas
  - Page templates
  - Block patterns
  - Custom post types
  - Post/page metaboxes
  - Theme support features

- **Dashboard Pages**
  - All admin menu items and submenus
  - Theme-specific admin pages

### 🤖 AI-Assisted Search
- **Natural Language Queries**: Ask questions in plain English
- **Context-Aware Responses**: AI understands your WordPress setup
- **Direct Links**: Get direct links to relevant settings
- **Chat History**: Conversation history is preserved

### 🎨 User-Friendly Interface
- **Modern UI**: Clean, intuitive interface
- **Quick Access Buttons**: Common searches at your fingertips
- **Category Browsing**: Explore settings by category
- **Direct Navigation**: One-click access to any setting

## 📦 Installation

### Method 1: Manual Installation

1. Download the plugin files
2. Upload the `Option-Map` folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Navigate to **Option Map** in the admin menu

### Method 2: Git Clone

```bash
cd wp-content/plugins
git clone https://github.com/kushh23/optionMap.git Option-Map
```

Then activate the plugin through WordPress admin.

## 🚀 Usage

### Basic Search

1. Go to **Option Map** in your WordPress admin menu
2. Type what you're looking for in the search box (e.g., "logo", "comments", "homepage")
3. Click on any result to go directly to that setting

### Category Browsing

1. Use the category sidebar to filter settings
2. Click on any category (General, Appearance, Content, etc.)
3. Browse all settings in that category

### AI-Assisted Search

1. Go to **Option Map > AI Assisted Search**
2. Enter your OpenAI API key (Settings > General)
3. Ask questions like:
   - "Where can I change my site logo?"
   - "How do I disable comments?"
   - "Where are the permalink settings?"

### Theme Settings

1. Go to **Option Map > Theme Settings**
2. View all theme-specific settings
3. Filter and search through theme options

## 🏗️ Architecture

Option Map is built with clean object-oriented principles:

### Directory Structure

```
Option-Map/
├── settings-finder.php          # Main plugin file
├── ARCHITECTURE.md              # Detailed architecture documentation
├── README.md                    # This file
├── includes/                    # Core classes
│   ├── class-autoloader.php     # PSR-4 autoloader
│   ├── class-plugin.php         # Main plugin orchestrator
│   ├── Core/                    # Core utilities
│   │   ├── class-url-validator.php
│   │   ├── class-settings-formatter.php
│   │   ├── class-settings-aggregator.php
│   │   └── class-scanner-factory.php
│   ├── Scanners/                # Scanner classes
│   │   ├── interface-scanner.php
│   │   ├── abstract-class-scanner-base.php
│   │   ├── class-customizer-scanner.php
│   │   ├── class-theme-mods-scanner.php
│   │   ├── class-database-options-scanner.php
│   │   ├── class-menu-locations-scanner.php
│   │   ├── class-widget-areas-scanner.php
│   │   ├── class-page-templates-scanner.php
│   │   ├── class-block-patterns-scanner.php
│   │   ├── class-custom-post-types-scanner.php
│   │   ├── class-post-metaboxes-scanner.php
│   │   ├── class-theme-support-scanner.php
│   │   ├── class-core-settings-scanner.php
│   │   └── class-dashboard-pages-scanner.php
│   ├── Admin/                   # Admin interface
│   │   ├── class-admin-menu.php
│   │   ├── class-asset-manager.php
│   │   └── Ajax/                # AJAX handlers
│   │       ├── abstract-class-ajax-handler.php
│   │       ├── class-search-ajax-handler.php
│   │       ├── class-refresh-scan-ajax-handler.php
│   │       └── class-ai-chat-ajax-handler.php
│   ├── AI/                      # AI integration
│   │   ├── interface-ai-provider.php
│   │   ├── class-openai-provider.php
│   │   ├── class-chat-history-manager.php
│   │   └── class-ai-context-formatter.php
│   ├── Database/                # Database layer
│   │   ├── interface-repository.php
│   │   └── class-database-installer.php
│   └── Settings/                # Settings management
│       └── class-settings-manager.php
└── assets/                      # Frontend assets
    ├── css/
    │   ├── admin.css
    │   └── ai-search.css
    ├── js/
    │   ├── admin.js
    │   └── ai-search.js
    └── logo/
        └── logo.png
```

### Key Design Principles

- **Object-Oriented Architecture**: Clean separation of concerns
- **Dependency Injection**: Loose coupling between components
- **Interface-Based Design**: Extensible and testable
- **PSR-4 Autoloading**: Modern PHP standards
- **WordPress Coding Standards**: Follows WordPress best practices

### Core Components

#### Scanners
Each scanner is responsible for discovering a specific type of setting:
- **Customizer Scanner**: Finds all Customizer panels, sections, and controls
- **Core Settings Scanner**: Indexes WordPress core settings
- **Theme Mods Scanner**: Discovers theme modification settings
- And 9 more specialized scanners...

#### Factory Pattern
The `Scanner_Factory` manages all scanner instances and provides a unified interface for scanning.

#### AJAX Handlers
All AJAX requests are handled through dedicated handler classes that extend an abstract base class.

#### AI Integration
The AI system uses a provider interface, making it easy to swap AI providers (currently OpenAI).

For detailed architecture information, see [ARCHITECTURE.md](ARCHITECTURE.md).

## 🛠️ Development

### Requirements

- WordPress 5.0+
- PHP 7.4+
- MySQL 5.6+ or MariaDB 10.0+

### Setting Up Development Environment

1. Clone the repository:
```bash
git clone https://github.com/kushh23/optionMap.git
cd Option-Map
```

2. Set up a local WordPress installation
3. Symlink or copy the plugin to your WordPress plugins directory
4. Activate the plugin

### Code Structure

The plugin follows WordPress coding standards and uses:
- **PSR-4 Autoloading**: Classes are automatically loaded
- **Namespaces**: `OptionMap_` prefix for all classes
- **Interfaces**: For extensibility
- **Abstract Classes**: For shared functionality

### Adding a New Scanner

1. Create a new class extending `OptionMap_Scanners_Scanner_Base`
2. Implement the `scan()` method
3. Register it in `OptionMap_Plugin::register_scanners()`

Example:
```php
class OptionMap_Scanners_My_Scanner extends OptionMap_Scanners_Scanner_Base {
    public function scan() {
        $settings = array();
        // Your scanning logic here
        return $settings;
    }
}
```

### Extending AI Providers

1. Implement `OptionMap_AI_AI_Provider_Interface`
2. Register your provider in the plugin initialization

## 🤝 Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Coding Standards

- Follow WordPress Coding Standards
- Use PSR-4 autoloading
- Add PHPDoc comments to all public methods
- Write descriptive commit messages

## 📝 Changelog

### Version 1.0.0
- Initial release
- Complete OOP refactoring
- 12 scanner types
- AI-assisted search
- Universal theme support
- Modern UI/UX

## 🐛 Known Issues

- Some theme-specific settings may not be detected if themes use non-standard registration methods
- AI features require OpenAI API key

## 🔮 Future Roadmap

- [ ] Support for plugin settings scanning
- [ ] Export/import settings index
- [ ] Settings change history
- [ ] Bulk settings operations
- [ ] Multi-language support
- [ ] Additional AI providers (Claude, Gemini)

## 📄 License

This plugin is licensed under the GPL v2 or later.

```
Copyright (C) 2024 Option Map Contributors

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
```

## 👥 Credits

- **Developer**: Kush Namdev, Stefan Cotitosu (AKA Support Ninjas)
- **Repository**: [GitHub](https://github.com/kushh23/optionMap)
- **Built with**: WordPress, PHP, JavaScript

## 📞 Support

For issues, feature requests, or questions:
- Open an issue on [GitHub](https://github.com/kushh23/optionMap/issues)
- Check the [ARCHITECTURE.md](ARCHITECTURE.md) for technical details

## 🙏 Acknowledgments

- WordPress community for the amazing platform
- All contributors and testers
- OpenAI for the AI API

---

**Made with ❤️ for the WordPress community**

