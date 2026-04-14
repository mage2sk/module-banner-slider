<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 * Banner Slider Widget Block
 */
declare(strict_types=1);

namespace Panth\BannerSlider\Block\Widget;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Widget\Block\BlockInterface;
use Panth\BannerSlider\Helper\Data as BannerHelper;
use Magento\Framework\Serialize\Serializer\Json;
use Panth\Core\Helper\Theme as ThemeHelper;

class BannerSlider extends Template implements BlockInterface
{
    protected $_template = 'Panth_BannerSlider::widget/banner_slider.phtml';

    private BannerHelper $bannerHelper;
    private Json $json;
    private ThemeHelper $themeHelper;

    public function __construct(
        Context $context,
        BannerHelper $bannerHelper,
        Json $json,
        ThemeHelper $themeHelper,
        array $data = []
    ) {
        $this->bannerHelper = $bannerHelper;
        $this->json = $json;
        $this->themeHelper = $themeHelper;
        parent::__construct($context, $data);
    }

    public function getTemplate(): string
    {
        $template = parent::getTemplate();
        if ($this->themeHelper->isHyva()) {
            $map = ['Panth_BannerSlider::widget/banner_slider.phtml' => 'Panth_BannerSlider::widget/banner_slider_hyva.phtml'];
            $template = $map[$template] ?? $template;
        }
        return $template;
    }

    public function getIdentifier(): string
    {
        return (string)$this->getData('identifier');
    }

    public function getSlides(): array
    {
        return $this->bannerHelper->getSlidesByIdentifier($this->getIdentifier());
    }

    public function canDisplay(): bool
    {
        return !empty($this->getIdentifier()) && !empty($this->getSlides());
    }

    /**
     * Per-slider config from database
     */
    public function getSliderConfig(): array
    {
        return $this->bannerHelper->getSliderConfig($this->getIdentifier());
    }

    public function getSliderConfigJson(): string
    {
        return $this->json->serialize($this->getSliderConfig());
    }

    public function getUniqueId(): string
    {
        return 'banner_slider_' . $this->getIdentifier() . '_' . uniqid();
    }

    public function getHelper(): BannerHelper
    {
        return $this->bannerHelper;
    }
}
