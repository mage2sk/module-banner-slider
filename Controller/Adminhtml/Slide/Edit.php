<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Controller\Adminhtml\Slide;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;
use Panth\BannerSlider\Model\SlideFactory;

class Edit extends Action
{
    public const ADMIN_RESOURCE = 'Panth_BannerSlider::slide';

    private PageFactory $pageFactory;
    private SlideFactory $slideFactory;

    public function __construct(
        Action\Context $context,
        PageFactory $pageFactory,
        SlideFactory $slideFactory
    ) {
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
        $this->slideFactory = $slideFactory;
    }

    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('slide_id');
        $slide = $this->slideFactory->create();

        if ($id) {
            $slide->load($id);
            if (!$slide->getId()) {
                $this->messageManager->addErrorMessage(__('This slide no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }

        $page = $this->pageFactory->create();
        $page->setActiveMenu('Panth_BannerSlider::slide');
        $page->getConfig()->getTitle()->prepend(
            $id ? __('Edit Slide: %1', $slide->getData('title') ?: '#' . $id) : __('New Banner Slide')
        );

        return $page;
    }
}
