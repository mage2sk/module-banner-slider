<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Panth\BannerSlider\Model\ResourceModel\Slider\CollectionFactory as SliderCollectionFactory;
use Panth\BannerSlider\Model\ResourceModel\Slide\CollectionFactory as SlideCollectionFactory;

class Data extends AbstractHelper
{
    private const ALLOWED_LINK_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    private StoreManagerInterface $storeManager;
    private SliderCollectionFactory $sliderCollectionFactory;
    private SlideCollectionFactory $slideCollectionFactory;
    private TimezoneInterface $timezone;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        SliderCollectionFactory $sliderCollectionFactory,
        SlideCollectionFactory $slideCollectionFactory,
        TimezoneInterface $timezone
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->sliderCollectionFactory = $sliderCollectionFactory;
        $this->slideCollectionFactory = $slideCollectionFactory;
        $this->timezone = $timezone;
    }

    public function isAllowedLinkUrl(string $url): bool
    {
        $normalized = preg_replace(
            '/[\x00-\x20\x7F]+/',
            '',
            html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8')
        );
        $normalized = rawurldecode((string)$normalized);
        $normalized = (string)preg_replace('/[\x00-\x20\x7F]+/', '', $normalized);

        if ($normalized === '' || strpos($normalized, '\\') !== false) {
            return false;
        }

        if (preg_match('/^([a-z][a-z0-9+.\-]*):/i', $normalized, $matches)) {
            return in_array(strtolower($matches[1]), self::ALLOWED_LINK_SCHEMES, true);
        }

        return true;
    }

    public function getLinkUrl(array $slide): string
    {
        $url = trim((string)($slide['link_url'] ?? ''));
        if ($url === '' || $url === '#' || !$this->isAllowedLinkUrl($url)) {
            return '';
        }
        return $url;
    }

    public function getLinkTarget(array $slide): string
    {
        return ($slide['link_target'] ?? '') === '_blank' ? '_blank' : '';
    }

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
        $collection->setOrder('store_id', 'DESC');
        $collection->setPageSize(1);

        $slider = $collection->getFirstItem();
        return $slider->getId() ? $slider->getData() : null;
    }

    public function getSlidesByIdentifier(string $identifier): array
    {
        $slider = $this->getSliderByIdentifier($identifier);
        if (!$slider) {
            return [];
        }

        return $this->getSlidesBySliderId((int)$slider['slider_id']);
    }

    public function getSlidesBySliderId(int $sliderId): array
    {
        $storeId = (int)$this->storeManager->getStore()->getId();
        $today = $this->timezone->date()->format('Y-m-d');

        $collection = $this->slideCollectionFactory->create();
        $collection->addFieldToFilter('slider_id', $sliderId);
        $collection->addFieldToFilter('is_active', 1);
        $collection->addFieldToFilter('store_id', ['in' => [0, $storeId]]);

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

    public function isEnabled(string $identifier): bool
    {
        return !empty($this->getSlidesByIdentifier($identifier));
    }

    public function getSliderConfig(string $identifier): array
    {
        $slider = $this->getSliderByIdentifier($identifier);

        if (!$slider) {
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
            'effect'          => ($slider['effect'] ?? '') === 'slide' ? 'slide' : 'fade',
            'loop'            => (bool)($slider['is_loop'] ?? 1),
            'showArrows'      => (bool)($slider['show_arrows'] ?? 1),
            'showDots'        => (bool)($slider['show_dots'] ?? 1),
            'pauseOnHover'    => (bool)($slider['pause_on_hover'] ?? 1),
        ];
    }

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
