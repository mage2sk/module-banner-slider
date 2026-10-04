<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Controller\Adminhtml\Slider;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Panth\BannerSlider\Model\SliderFactory;
use Panth\BannerSlider\Model\ResourceModel\Slider as SliderResource;

class Delete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Panth_BannerSlider::slider';

    private SliderFactory $sliderFactory;
    private SliderResource $sliderResource;

    public function __construct(Action\Context $context, SliderFactory $sliderFactory, SliderResource $sliderResource)
    {
        parent::__construct($context);
        $this->sliderFactory = $sliderFactory;
        $this->sliderResource = $sliderResource;
    }

    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('slider_id');
        if ($id) {
            try {
                $slider = $this->sliderFactory->create();
                $this->sliderResource->load($slider, $id);
                $this->sliderResource->delete($slider);
                $this->messageManager->addSuccessMessage(__('The slider has been deleted.'));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }
        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
