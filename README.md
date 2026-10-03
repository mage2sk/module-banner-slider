# Magento 2 Banner Slider

Panth Banner Slider adds admin-managed banner sliders to Magento 2. A slider record holds the playback settings (transition effect, autoplay, loop, arrows, dots, pause on hover) and any number of slide records hold the images, an optional HTML overlay, a link, a sort order and a date range. A slider is placed on the storefront with the "Banner Slider" widget, a `{{widget}}` directive in CMS content, or a layout XML block, and is looked up by its identifier.

The module does not change any existing Magento behaviour. It adds two database tables, an admin menu under "Panth Extensions", two admin grids with edit forms, an image upload endpoint and one widget. It is used by merchants who manage promotional banners themselves and by developers who need a slider block in a theme. Two storefront templates are shipped: a Hyva template built on Alpine.js and a Luma template built on a small jQuery/RequireJS component. The template is selected automatically from the active theme through `Panth\Core\Helper\Theme`.

Product page: [kishansavaliya.com/magento-2-banner-slider.html](https://kishansavaliya.com/magento-2-banner-slider.html)

## Features

- Sliders and slides are separate records: one slider can have many slides, and each slide is assigned to one slider ("Assign to Slider").
- Admin grids "Manage Sliders" and "Manage Banners" with search, filters, sorting and row actions; the banner grid has a mass "Delete" action.
- Per-slider settings: "Transition Effect" (Fade or Slide), "Autoplay", "Autoplay Speed (ms)", "Transition Speed (ms)", "Infinite Loop", "Show Navigation Arrows", "Show Pagination Dots", "Pause on Hover".
- Per-slide "Desktop Image", "Tablet Image" and "Mobile Image" uploads (jpg, jpeg, png, gif, webp; 10 MB limit; the file extension and MIME type are both checked), rendered with a `<picture>` element. Tablet falls back to desktop; mobile falls back to tablet, then desktop.
- "Content Overlay HTML" edited with the classic WYSIWYG editor (Page Builder is disabled for this field); "Link URL" makes the slide image clickable and "Link Target" can open it in a new tab.
- "Image Alt Text" per slide.
- "Active From" / "Active To" dates: a slide is shown only when the current date in the store timezone is inside the range; either bound can be left empty.
- Store view scoping on both sliders and slides: records saved for "All Store Views" (store 0) or the current store view are shown. When a slider exists for both "All Store Views" and the current store view with the same identifier, the store view specific slider is used.
- Slides ordered by "Sort Order"; inactive sliders and slides are skipped.
- Storefront controls: previous/next arrows, pagination dots, touch swipe, left/right arrow keys while the slider or a control inside it has keyboard focus (the slider is focusable; keys typed in form fields are ignored), autoplay with pause on hover and while keyboard focus is inside the slider; autoplay does not run when the visitor prefers reduced motion. Arrows and dots are only rendered when a slider has more than one slide.
- The first slide image is loaded eagerly with `fetchpriority="high"`; later slides use `loading="lazy"`.
- Visual values (heights, overlay colour, radius) are read from CSS custom properties, with defaults listed in `etc/theme-config.json`.
- "Install Sample Data" button on the "Manage Sliders" grid asks for confirmation and then sends a POST request with the admin form key that creates five example sliders with slides and copies their images to `pub/media/bannerslider/`; existing identifiers are skipped. The install action does not answer GET requests.
- Uploaded file names are checked against the `Panth_Core` upload extension policy before they are stored.

## Compatibility

| Platform | Versions |
|---|---|
| Magento Open Source | 2.4.4 to 2.4.8 |
| Adobe Commerce | 2.4.4 to 2.4.8 |
| PHP | 8.1, 8.2, 8.3, 8.4 (`~8.1.0||~8.2.0||~8.3.0||~8.4.0`) |
| Themes | Hyva (Alpine.js template) and Luma (RequireJS template) |

The Magento version range is the one published on the product page. Composer constraints on Magento packages: `magento/framework ^103.0`, `magento/module-backend ^102.0`, `magento/module-cms ^104.0`, `magento/module-store ^101.1`, `magento/module-widget ^101.2`, `magento/module-media-storage ^100.4`, `magento/module-catalog ^104.0`, `magento/module-config ^101.2`, `magento/module-ui ^101.2`.

## Requirements

- Magento Open Source or Adobe Commerce 2.4.4 to 2.4.8
- PHP 8.1, 8.2, 8.3 or 8.4
- `mage2kishan/module-core` `^1.0.17` (required; provides the "Panth Extensions" admin menu and ACL parent, the theme detection helper and the upload extension policy)
- Suggested: `hyva-themes/magento2-default-theme` when the Hyva template is used

## Installation

```bash
composer require mage2kishan/module-banner-slider
bin/magento module:enable Panth_Core Panth_BannerSlider
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

`setup:di:compile` is only needed in production mode. `setup:static-content:deploy` is needed because the module ships a LESS file and a RequireJS component under `view/frontend/web/`.

Check the result:

```bash
bin/magento module:status Panth_BannerSlider
```

## Configuration

The module has no section under Stores > Configuration and writes nothing to `core_config_data`. Everything is configured per record in the admin under "Panth Extensions" > "Banner Slider":

- "Manage Sliders" (route `panth_bannerslider/slider/index`)
- "Manage Banners" (route `panth_bannerslider/slide/index`)

### Manage Sliders

The grid lists ID, "Slider Name", "Identifier", "Active", "Effect", "Autoplay" and "Created", with edit and delete row actions. Deleting a slider keeps its slides and sets their Slider to none, so they can be assigned to another slider. Buttons: "Add New Slider" and "Install Sample Data". The edit form has these fieldsets.

"General Information"

| Setting | Default | What it does |
|---|---|---|
| Slider Name | (required) | Internal name shown in the grid and in the "Assign to Slider" dropdown. |
| Identifier | (required) | Key used by the widget, the CMS directive and layout XML, for example `homepage_hero`. Unique per store view. A store view specific slider overrides an "All Store Views" slider with the same identifier on that store view. |
| Active | Yes | Inactive sliders render nothing. |
| Store View | All Store Views | Store view the slider is looked up for. |

"Slider Behavior"

| Setting | Default | What it does |
|---|---|---|
| Transition Effect | Fade | "Fade" or "Slide". Both templates honour it: "Slide" moves the slides horizontally (Luma with a CSS transform, Hyva with Alpine.js transitions), "Fade" cross-fades them. |
| Autoplay | Yes | Advances slides automatically. |
| Autoplay Speed (ms) | 5000 | Time between slide changes. |
| Transition Speed (ms) | 600 | Duration of the animation. |
| Infinite Loop | Yes | Wraps from the last slide to the first. When off, next/previous stop at the ends. |
| Show Navigation Arrows | Yes | Renders previous/next buttons. |
| Show Pagination Dots | Yes | Renders one dot per slide. |
| Pause on Hover | Yes | Stops autoplay while the pointer is over the slider. |

"How to Use This Slider" shows the widget, template and layout XML snippets listed under Usage below.

### Manage Banners

The grid lists ID, "Slider", "Title", "Active", "Image" thumbnail, "Link URL", "Sort Order", "Store View" and "Created", with a mass "Delete" action and edit/delete row actions. Button: "Add New Slide". The edit form has these fieldsets.

"General Information"

| Setting | Default | What it does |
|---|---|---|
| Assign to Slider | (required) | Parent slider, listed as "Slider Name (identifier)". |
| Title | empty | Internal title for admin reference. |
| Active | Yes | Inactive slides are not rendered. |
| Store View | All Store Views | Store view the slide is shown in. |
| Sort Order | 0 | Ascending order inside the slider. |

"Images"

| Setting | Default | What it does |
|---|---|---|
| Desktop Image | empty | Shown at 1024 px and above. Recommended 1920 x 600 px. |
| Tablet Image | empty | Shown between 768 px and 1023 px. Falls back to the desktop image. |
| Mobile Image | empty | Shown below 768 px. Falls back to the tablet image, then the desktop image. |
| Image Alt Text | empty | `alt` attribute of the image. When empty, "Banner N" is used. |

"Content & Link"

| Setting | Default | What it does |
|---|---|---|
| Content Overlay HTML | empty | HTML rendered on top of the image; CMS directives are supported. On the Hyva template the link wrapper is omitted when overlay content is present so links inside the overlay stay clickable. |
| Link URL | empty | Relative path or a URL using http, https, mailto or tel; leave empty for no link. Other schemes such as `javascript:` or `data:` are rejected on save and are not rendered. |
| Link Target | Same Window | "Same Window" or "New Tab". "New Tab" renders the link with `target="_blank"` and `rel="noopener noreferrer"`. |

"Schedule"

| Setting | Default | What it does |
|---|---|---|
| Active From | empty | First date the slide is shown. |
| Active To | empty | Last date the slide is shown. |

Images are uploaded to `pub/media/bannerslider/` (temporary uploads go to `pub/media/bannerslider/tmp/`).

## Usage

### Widget

Content > Widgets > Add Widget, type "Banner Slider". Parameters:

| Parameter | Type | Notes |
|---|---|---|
| Slider Identifier | text, required | The "Identifier" of the slider. |
| Template | select | "Default Template (Luma)" (default) or "Hyva Template". When the active theme is Hyva, the default template is replaced by the Hyva template automatically. |

### CMS page or block

```
{{widget type="Panth\BannerSlider\Block\Widget\BannerSlider" identifier="homepage_hero"}}
```

### Layout XML

```xml
<block class="Panth\BannerSlider\Block\Widget\BannerSlider" name="my.banner.slider">
    <arguments>
        <argument name="identifier" xsi:type="string">homepage_hero</argument>
    </arguments>
</block>
```

### PHTML template

```php
<?= $block->getLayout()->createBlock(\Panth\BannerSlider\Block\Widget\BannerSlider::class)->setData('identifier', 'homepage_hero')->toHtml() ?>
```

### Storefront behaviour

The block renders nothing when the identifier is empty, the slider is missing or inactive for the current store view, or no slide passes the active, store view and date filters. Each slider is wrapped in a `div.panth-banner-slider` container with a unique id, so several sliders can be placed on one page. On Luma the arrow keys are bound at document level and move every slider on the page; on Hyva they are bound at window level per slider.

Overlay HTML is processed by the CMS block filter (so directives such as `{{media}}`, `{{store}}` and `{{widget}}` work) and is then output unescaped, as authored in the admin editor.

The block implements `Magento\Framework\DataObject\IdentityInterface`, so saving or deleting a slider or slide invalidates the full page cache entries of pages that show it.

### Templates that can be overridden

- `Panth_BannerSlider::widget/banner_slider.phtml` (Luma; uses `Panth_BannerSlider/js/banner-slider` through `data-mage-init`; styles come from `view/frontend/web/css/source/_module.less`)
- `Panth_BannerSlider::widget/banner_slider_hyva.phtml` (Hyva; the Alpine.js component and its CSS are inline in the template)

Both templates read these CSS custom properties, with fallbacks: `--banner-height-desktop`, `--banner-height-tablet`, `--banner-height-mobile`, `--banner-border-radius`, `--banner-overlay-bg`, `--banner-content-max-width`, `--banner-transition-speed`, `--banner-arrow-icon-size` (Luma only).

## Developer Notes

- Module name: `Panth_BannerSlider`; Composer package: `mage2kishan/module-banner-slider`; namespace: `Panth\BannerSlider`.
- Loads after `Magento_Cms`, `Magento_Store`, `Magento_Widget`, `Magento_Backend` and `Panth_Core`.
- `Block\Widget\BannerSlider` (implements `Magento\Widget\Block\BlockInterface`): `getIdentifier()`, `getSlides()`, `canDisplay()`, `getSliderConfig()`, `getSliderConfigJson()`, `getUniqueId()`, `getHelper()`, `getTemplate()`.
- `Helper\Data`: `getSliderByIdentifier()`, `getSlidesByIdentifier()`, `getSlidesBySliderId()`, `isEnabled()`, `getSliderConfig()`, `getImageUrl()`, `getResponsiveImages()`.
- Models `Model\Slider` (event prefix `panth_banner_slider`, cache identities `panth_banner_slider` and `panth_banner_slider_<id>`) and `Model\Slide` (event prefix `panth_banner_slide`, cache identities `panth_banner_slide_<id>` and `panth_banner_slider_<slider_id>`), with resource models and collections under `Model\ResourceModel`.
- Option sources: `Model\Config\Source\TransitionEffect` (fade, slide) and `Model\Config\Source\SliderOptions` (all sliders).
- Virtual type `Panth\BannerSlider\ImageUploader` (`Magento\Catalog\Model\ImageUploader` with base path `bannerslider`) is injected into `Controller\Adminhtml\Upload\Image` and `Controller\Adminhtml\Slide\Save`.
- `etc/frontend/di.xml` registers the module with `Panth\Core\ViewModel\ThemeConfig`; `etc/theme-config.json` holds the default CSS variable values.
- Admin route: `panth_bannerslider` (controllers `slider/*`, `slide/*`, `upload/image`, `sampleData/install`). `sampleData/install` accepts POST only; the grid button is provided by `Block\Adminhtml\Slider\InstallSampleDataButton`.
- ACL resources: `Panth_BannerSlider::slider` ("Banner Slider - Manage Sliders") and `Panth_BannerSlider::slide` ("Banner Slider - Manage Banners"), under `Panth_Core::panth_extensions`.
- UI components: `panth_bannerslider_slider_listing`, `panth_bannerslider_slider_form`, `panth_bannerslider_slide_listing`, `panth_bannerslider_slide_form`.
- Database tables (`etc/db_schema.xml`): `panth_banner_slider` (unique on `identifier` + `store_id`) and `panth_banner_slide` (indexed on `slider_id`, `is_active`, `sort_order`).
- Sample data lives in `Setup/SampleData/*.json` with images in `Setup/SampleData/images/`.
- No plugins, observers, cron jobs, console commands or web API routes are declared.

## Uninstallation

```bash
bin/magento module:disable Panth_BannerSlider
composer remove mage2kishan/module-banner-slider
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

The tables `panth_banner_slider` and `panth_banner_slide` and the images in `pub/media/bannerslider/` are not removed. The module stores no values in `core_config_data`. Widget instances and CMS content that reference the block class must be removed by hand.

## Support

- Product page: [kishansavaliya.com/magento-2-banner-slider.html](https://kishansavaliya.com/magento-2-banner-slider.html)
- Contact: [kishansavaliya.com/contact](https://kishansavaliya.com/contact)
- Email: kishansavaliyakb@gmail.com
- GitHub issues: [github.com/mage2sk/module-banner-slider/issues](https://github.com/mage2sk/module-banner-slider/issues)

## Documentation

[USER_GUIDE.md](USER_GUIDE.md) covers creating sliders and slides, placing a slider with the widget or a CMS directive, the slider options, CSS customisation and troubleshooting.

## License

Commercial software license. See [LICENSE.txt](LICENSE.txt) in this repository.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## Links

- Website: [kishansavaliya.com](https://kishansavaliya.com)
- All extensions: [kishansavaliya.com/magento-extensions.html](https://kishansavaliya.com/magento-extensions.html)
- GitHub: [github.com/mage2sk/module-banner-slider](https://github.com/mage2sk/module-banner-slider)
- Packagist: [packagist.org/packages/mage2kishan/module-banner-slider](https://packagist.org/packages/mage2kishan/module-banner-slider)
