<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Controller\Adminhtml\Slide;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Ui\Component\MassAction\Filter;
use Panth\BannerSlider\Model\ResourceModel\Slide\CollectionFactory;
use Panth\BannerSlider\Model\ResourceModel\Slide as SlideResource;

class MassDelete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Panth_BannerSlider::slide';

    private Filter $filter;
    private CollectionFactory $collectionFactory;
    private SlideResource $slideResource;

    public function __construct(
        Action\Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        SlideResource $slideResource
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->slideResource = $slideResource;
    }

    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $count = 0;

        foreach ($collection as $slide) {
            $this->slideResource->delete($slide);
            $count++;
        }

        $this->messageManager->addSuccessMessage(__('A total of %1 slide(s) have been deleted.', $count));
        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }
}
