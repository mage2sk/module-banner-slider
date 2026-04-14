# Panth Banner Slider - User Guide

## Overview

Panth Banner Slider provides a complete banner slider solution for Magento 2 stores. It uses a two-entity architecture (Sliders and Slides) so you can create multiple sliders, each with its own set of slides and configuration.

## Getting Started

After installation, navigate to **Panth Extensions > Banner Slider** in the Magento admin panel.

## Managing Sliders

### Create a Slider

1. Go to **Panth Extensions > Banner Slider > Manage Sliders**
2. Click **Add New Slider**
3. Fill in the required fields:
   - **Identifier**: A unique key used to reference this slider (e.g., `homepage_hero`)
   - **Title**: A descriptive name for the slider
   - **Is Active**: Enable or disable the slider
4. Configure slider behavior:
   - **Autoplay**: Enable automatic slide advancement
   - **Autoplay Speed**: Time between slides in milliseconds (default: 5000)
   - **Transition Speed**: Animation duration in milliseconds (default: 600)
   - **Effect**: Choose between `fade` and `slide` transitions
   - **Loop**: Enable infinite loop
   - **Show Arrows**: Display previous/next navigation arrows
   - **Show Dots**: Display pagination dots
   - **Pause on Hover**: Pause autoplay when the user hovers over the slider
5. Click **Save Slider**

## Managing Slides (Banners)

### Create a Slide

1. Go to **Panth Extensions > Banner Slider > Manage Banners**
2. Click **Add New Slide**
3. Fill in the fields:
   - **Slider**: Select the parent slider
   - **Title**: Slide title (for admin reference)
   - **Desktop Image**: The main banner image
   - **Tablet Image** (optional): Image for tablet viewports
   - **Mobile Image** (optional): Image for mobile viewports
   - **Content HTML**: Rich overlay content (supports WYSIWYG editor)
   - **Link URL**: Clickable link for the entire slide
   - **Alt Text**: Accessibility text for the image
   - **Sort Order**: Controls display order
   - **Start Date / End Date**: Schedule the slide visibility
   - **Store View**: Restrict to specific store views
4. Click **Save Slide**

## Placing a Slider on a Page

### Using Widget

1. Go to **Content > Widgets > Add Widget**
2. Select **Banner Slider** as the widget type
3. Configure the widget:
   - **Slider Identifier**: Enter the identifier of the slider you created
   - **Template**: Auto-detected based on active theme (Luma or Hyva)
4. Choose the layout update where you want the slider to appear
5. Save the widget

### Using CMS Block/Page

Insert the widget code directly:

```
{{widget type="Panth\BannerSlider\Block\Widget\BannerSlider" identifier="homepage_hero"}}
```

## Configuration Options

| Option | Description | Default |
|--------|-------------|---------|
| Autoplay | Auto-advance slides | Enabled |
| Autoplay Speed | Milliseconds between slides | 5000 |
| Transition Speed | Animation duration (ms) | 600 |
| Effect | Transition type (fade/slide) | fade |
| Loop | Infinite loop | Enabled |
| Show Arrows | Navigation arrows | Enabled |
| Show Dots | Pagination dots | Enabled |
| Pause on Hover | Pause autoplay on hover | Enabled |

## CSS Customization

The slider uses CSS custom properties for easy theming:

```css
:root {
    --banner-height-desktop: 500px;
    --banner-height-tablet: 400px;
    --banner-height-mobile: 320px;
    --banner-border-radius: 0;
    --banner-overlay-bg: rgba(0,0,0,0.25);
    --banner-content-max-width: 800px;
    --banner-transition-speed: 600ms;
    --banner-arrow-icon-size: 20px;
}
```

## Troubleshooting

### Slider not displaying
- Verify the slider is set to **Active**
- Verify at least one slide is active and assigned to the slider
- Check that the slider identifier in the widget matches the one configured in admin
- Flush Magento cache after making changes

### Images not showing
- Ensure images have been uploaded successfully (check `pub/media/bannerslider/` directory)
- Verify file permissions on the media directory

### Slides not appearing on schedule
- Confirm the **Start Date** and **End Date** are set correctly
- Dates use the `Y-m-d` format
- Check your server timezone settings

### Widget not rendering
- Run `bin/magento setup:upgrade` and `bin/magento cache:flush`
- Verify the Panth_BannerSlider module is enabled: `bin/magento module:status Panth_BannerSlider`
