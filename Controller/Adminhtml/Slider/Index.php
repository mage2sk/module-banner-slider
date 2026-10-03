<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Controller\Adminhtml\Slider;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'Panth_BannerSlider::slider';

    private PageFactory $pageFactory;

    public function __construct(Action\Context $context, PageFactory $pageFactory)
    {
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
    }

    public function execute()
    {
        $page = $this->pageFactory->create();
        $page->setActiveMenu('Panth_BannerSlider::slider');
        $page->getConfig()->getTitle()->prepend(__('Banner Sliders'));
        return $page;
    }
}
