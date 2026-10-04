<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Controller\Adminhtml\Slide;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'Panth_BannerSlider::slide';

    private PageFactory $pageFactory;

    public function __construct(Action\Context $context, PageFactory $pageFactory)
    {
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
    }

    public function execute()
    {
        $page = $this->pageFactory->create();
        $page->setActiveMenu('Panth_BannerSlider::slide');
        $page->getConfig()->getTitle()->prepend(__('Banner Slides'));
        return $page;
    }
}
