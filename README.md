# Panth Banner Slider for Magento 2

A responsive banner slider module for Magento 2 with full admin management, responsive images (desktop/tablet/mobile), content overlays, and both Luma and Hyva theme support.

## Features

- **Two-entity architecture**: Sliders (parent) with Slides (children) for flexible organization
- **Full admin CRUD**: Manage sliders and slides via dedicated admin grids and forms
- **Responsive images**: Separate desktop, tablet, and mobile image uploads per slide
- **Content overlays**: Rich HTML content with WYSIWYG editor support on each slide
- **Configurable per slider**: Autoplay, autoplay speed, transition speed, fade/slide effect, loop, arrows, dots, pause-on-hover
- **Widget integration**: Place sliders anywhere via CMS widget with identifier selection
- **Hyva + Luma support**: Auto-detects active theme and renders Alpine.js (Hyva) or RequireJS (Luma) templates
- **Touch/swipe support**: Mobile-friendly touch gestures for navigation
- **Keyboard navigation**: Arrow key support for accessibility
- **Date scheduling**: Set start/end dates for individual slides
- **Store view scoping**: Per-store slide and slider visibility
- **Sample data**: Pre-built slider configurations with sample images for quick setup
- **CSS variable theming**: All visual values driven by CSS custom properties for easy theme customization

## Requirements

- Magento 2.4.6 or later
- PHP 8.1, 8.2, 8.3, or 8.4
- Panth Core module (`mage2kishan/module-core`)

## Installation via Composer

```bash
composer require mage2kishan/module-banner-slider
bin/magento module:enable Panth_BannerSlider
bin/magento setup:upgrade
bin/magento cache:flush
```

## Support

- **Email**: kishansavaliyakb@gmail.com
- **Website**: [kishansavaliya.com](https://kishansavaliya.com)
