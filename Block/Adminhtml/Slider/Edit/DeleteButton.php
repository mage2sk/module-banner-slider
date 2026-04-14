<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Block\Adminhtml\Slider\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Backend\Block\Widget\Context;

class DeleteButton implements ButtonProviderInterface
{
    private Context $context;

    public function __construct(Context $context) { $this->context = $context; }

    public function getButtonData(): array
    {
        $id = (int)$this->context->getRequest()->getParam('slider_id');
        if (!$id) return [];

        return [
            'label' => __('Delete'),
            'class' => 'delete',
            'on_click' => 'deleteConfirm(\'' . __('Are you sure?') . '\', \'' . $this->context->getUrlBuilder()->getUrl('*/*/delete', ['slider_id' => $id]) . '\', {data: {form_key: window.FORM_KEY}})',
            'sort_order' => 20,
        ];
    }
}
