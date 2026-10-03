<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Controller\Adminhtml\Slide;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Panth\BannerSlider\Model\SlideFactory;
use Panth\BannerSlider\Model\ResourceModel\Slide as SlideResource;

class Delete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Panth_BannerSlider::slide';

    private SlideFactory $slideFactory;
    private SlideResource $slideResource;

    public function __construct(
        Action\Context $context,
        SlideFactory $slideFactory,
        SlideResource $slideResource
    ) {
        parent::__construct($context);
        $this->slideFactory = $slideFactory;
        $this->slideResource = $slideResource;
    }

    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('slide_id');
        $redirect = $this->resultRedirectFactory->create();

        if ($id) {
            try {
                $slide = $this->slideFactory->create();
                $this->slideResource->load($slide, $id);
                $this->slideResource->delete($slide);
                $this->messageManager->addSuccessMessage(__('The slide has been deleted.'));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }

        return $redirect->setPath('*/*/');
    }
}
