<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Block\Adminhtml\Slide\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Backend\Block\Widget\Context;

class DeleteButton implements ButtonProviderInterface
{
    private Context $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function getButtonData(): array
    {
        $slideId = (int)$this->context->getRequest()->getParam('slide_id');
        if (!$slideId) {
            return [];
        }

        return [
            'label' => __('Delete'),
            'class' => 'delete',
            'on_click' => 'deleteConfirm(\'' . __('Are you sure you want to delete this slide?') . '\', \''
                . $this->context->getUrlBuilder()->getUrl('*/*/delete', ['slide_id' => $slideId]) . '\', {data: {form_key: window.FORM_KEY}})',
            'sort_order' => 20,
        ];
    }
}
