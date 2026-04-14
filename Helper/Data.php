<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Banner Slider Helper — reads slider config + slides from database
 */
declare(strict_types=1);

namespace Panth\BannerSlider\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\BannerSlider\Model\ResourceModel\Slider\CollectionFactory as SliderCollectionFactory;
use Panth\BannerSlider\Model\ResourceModel\Slide\CollectionFactory as SlideCollectionFactory;

class Data extends AbstractHelper
{
    private StoreManagerInterface $storeManager;
    private SliderCollectionFactory $sliderCollectionFactory;
    private SlideCollectionFactory $slideCollectionFactory;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        SliderCollectionFactory $sliderCollectionFactory,
        SlideCollectionFactory $slideCollectionFactory
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->sliderCollectionFactory = $sliderCollectionFactory;
        $this->slideCollectionFactory = $slideCollectionFactory;
    }

    /**
     * Load slider entity by identifier
     */
    public function getSliderByIdentifier(string $identifier): ?array
    {
        if (empty($identifier)) {
            return null;
        }

        $storeId = (int)$this->storeManager->getStore()->getId();

        $collection = $this->sliderCollectionFactory->create();
        $collection->addFieldToFilter('identifier', $identifier);
        $collection->addFieldToFilter('is_active', 1);
        $collection->addFieldToFilter('store_id', ['in' => [0, $storeId]]);
        $collection->setPageSize(1);

        $slider = $collection->getFirstItem();
        return $slider->getId() ? $slider->getData() : null;
    }

    /**
     * Get active slides for a slider by its identifier
     */
    public function getSlidesByIdentifier(string $identifier): array
    {
        $slider = $this->getSliderByIdentifier($identifier);
        if (!$slider) {
            return [];
        }

        return $this->getSlidesBySliderId((int)$slider['slider_id']);
    }

    /**
     * Get active slides for a slider by slider_id
     */
    public function getSlidesBySliderId(int $sliderId): array
    {
        $storeId = (int)$this->storeManager->getStore()->getId();
        $today = date('Y-m-d');

        $collection = $this->slideCollectionFactory->create();
        $collection->addFieldToFilter('slider_id', $sliderId);
        $collection->addFieldToFilter('is_active', 1);
        $collection->addFieldToFilter('store_id', ['in' => [0, $storeId]]);

        // Date range filters
        $collection->addFieldToFilter(
            ['date_from', 'date_from'],
            [['null' => true], ['lteq' => $today]]
        );
        $collection->addFieldToFilter(
            ['date_to', 'date_to'],
            [['null' => true], ['gteq' => $today]]
        );

        $collection->setOrder('sort_order', 'ASC');

        return $collection->getData();
    }

    /**
     * Check if slider has active slides
     */
    public function isEnabled(string $identifier): bool
    {
        return !empty($this->getSlidesByIdentifier($identifier));
    }

    /**
     * Get slider config from the slider entity (per-slider settings)
     */
    public function getSliderConfig(string $identifier): array
    {
        $slider = $this->getSliderByIdentifier($identifier);

        if (!$slider) {
            // Defaults
            return [
                'autoplay' => true,
                'autoplaySpeed' => 5000,
                'transitionSpeed' => 600,
                'effect' => 'fade',
                'loop' => true,
                'showArrows' => true,
                'showDots' => true,
                'pauseOnHover' => true,
            ];
        }

        return [
            'autoplay'        => (bool)($slider['autoplay'] ?? 1),
            'autoplaySpeed'   => (int)($slider['autoplay_speed'] ?? 5000),
            'transitionSpeed' => (int)($slider['transition_speed'] ?? 600),
            'effect'          => $slider['effect'] ?? 'fade',
            'loop'            => (bool)($slider['is_loop'] ?? 1),
            'showArrows'      => (bool)($slider['show_arrows'] ?? 1),
            'showDots'        => (bool)($slider['show_dots'] ?? 1),
            'pauseOnHover'    => (bool)($slider['pause_on_hover'] ?? 1),
        ];
    }

    // --- Image Helpers ---

    public function getImageUrl(string $imagePath): string
    {
        if (empty($imagePath)) {
            return '';
        }
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . ltrim($imagePath, '/');
    }

    public function getResponsiveImages(array $slide): array
    {
        $desktop = !empty($slide['desktop_image']) ? $this->getImageUrl($slide['desktop_image']) : '';
        $tablet = !empty($slide['tablet_image']) ? $this->getImageUrl($slide['tablet_image']) : $desktop;
        $mobile = !empty($slide['mobile_image']) ? $this->getImageUrl($slide['mobile_image']) : ($tablet ?: $desktop);

        return ['desktop' => $desktop, 'tablet' => $tablet, 'mobile' => $mobile];
    }
}
