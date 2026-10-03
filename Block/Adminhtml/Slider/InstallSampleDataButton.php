<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Block\Adminhtml\Slider;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Escaper;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class InstallSampleDataButton implements ButtonProviderInterface
{
    private Context $context;
    private Escaper $escaper;

    public function __construct(Context $context, Escaper $escaper)
    {
        $this->context = $context;
        $this->escaper = $escaper;
    }

    public function getButtonData(): array
    {
        if (!$this->context->getAuthorization()->isAllowed('Panth_BannerSlider::slider')) {
            return [];
        }

        $url = $this->context->getUrlBuilder()->getUrl('panth_bannerslider/sampleData/install');
        $message = (string)__('Install the sample sliders and banners? Sliders that already exist are skipped.');

        return [
            'label' => __('Install Sample Data'),
            'class' => 'secondary',
            'on_click' => sprintf(
                "deleteConfirm('%s', '%s', {data: {}})",
                $this->escaper->escapeJs($message),
                $this->escaper->escapeJs($url)
            ),
            'sort_order' => 10,
        ];
    }
}
