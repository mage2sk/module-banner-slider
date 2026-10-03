<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Controller\Adminhtml\Slider;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;
use Panth\BannerSlider\Model\SliderFactory;

class Edit extends Action
{
    public const ADMIN_RESOURCE = 'Panth_BannerSlider::slider';

    private PageFactory $pageFactory;
    private SliderFactory $sliderFactory;

    public function __construct(Action\Context $context, PageFactory $pageFactory, SliderFactory $sliderFactory)
    {
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
        $this->sliderFactory = $sliderFactory;
    }

    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('slider_id');
        $slider = $this->sliderFactory->create();

        if ($id) {
            $slider->load($id);
            if (!$slider->getId()) {
                $this->messageManager->addErrorMessage(__('This slider no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }

        $page = $this->pageFactory->create();
        $page->setActiveMenu('Panth_BannerSlider::slider');
        $page->getConfig()->getTitle()->prepend(
            $id ? __('Edit Slider: %1', $slider->getData('title')) : __('New Banner Slider')
        );
        return $page;
    }
}
