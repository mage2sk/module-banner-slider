<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Block\Widget;

use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Widget\Block\BlockInterface;
use Panth\BannerSlider\Helper\Data as BannerHelper;
use Magento\Framework\Serialize\Serializer\Json;
use Panth\BannerSlider\Model\Slide;
use Panth\BannerSlider\Model\Slider;
use Panth\Core\Helper\Theme as ThemeHelper;

class BannerSlider extends Template implements BlockInterface, IdentityInterface
{
    protected $_template = 'Panth_BannerSlider::widget/banner_slider.phtml';

    private BannerHelper $bannerHelper;
    private Json $json;
    private ThemeHelper $themeHelper;
    private FilterProvider $filterProvider;
    private ?array $slides = null;

    public function __construct(
        Context $context,
        BannerHelper $bannerHelper,
        Json $json,
        ThemeHelper $themeHelper,
        FilterProvider $filterProvider,
        array $data = []
    ) {
        $this->bannerHelper = $bannerHelper;
        $this->json = $json;
        $this->themeHelper = $themeHelper;
        $this->filterProvider = $filterProvider;
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
        if ($this->slides === null) {
            $this->slides = $this->bannerHelper->getSlidesByIdentifier($this->getIdentifier());
        }
        return $this->slides;
    }

    public function canDisplay(): bool
    {
        return !empty($this->getIdentifier()) && !empty($this->getSlides());
    }

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

    public function getContentHtml(array $slide): string
    {
        $content = (string)($slide['content_html'] ?? '');
        if (trim($content) === '') {
            return '';
        }
        try {
            return (string)$this->filterProvider->getBlockFilter()
                ->setStoreId((int)$this->_storeManager->getStore()->getId())
                ->filter($content);
        } catch (\Exception $e) {
            $this->_logger->error('BannerSlider content filter error: ' . $e->getMessage());
            return $content;
        }
    }

    public function getIdentities(): array
    {
        $identities = [Slider::CACHE_TAG];
        foreach ($this->getSlides() as $slide) {
            if (!empty($slide['slide_id'])) {
                $identities[] = Slide::CACHE_TAG . '_' . (int)$slide['slide_id'];
            }
            if (!empty($slide['slider_id'])) {
                $identities[] = Slider::CACHE_TAG . '_' . (int)$slide['slider_id'];
            }
        }
        return array_values(array_unique($identities));
    }
}
