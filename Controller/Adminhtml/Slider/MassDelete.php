<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Controller\Adminhtml\Slider;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Ui\Component\MassAction\Filter;
use Panth\BannerSlider\Model\ResourceModel\Slider\CollectionFactory;
use Panth\BannerSlider\Model\ResourceModel\Slider as SliderResource;

class MassDelete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Panth_BannerSlider::slider';

    private Filter $filter;
    private CollectionFactory $collectionFactory;
    private SliderResource $sliderResource;

    public function __construct(
        Action\Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        SliderResource $sliderResource
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->sliderResource = $sliderResource;
    }

    public function execute()
    {
        $count = 0;
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            foreach ($collection as $slider) {
                $this->sliderResource->delete($slider);
                $count++;
            }
            $this->messageManager->addSuccessMessage(__('A total of %1 slider(s) have been deleted.', $count));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
