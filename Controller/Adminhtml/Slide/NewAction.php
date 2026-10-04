<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Controller\Adminhtml\Slide;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;

class NewAction extends Action
{
    public const ADMIN_RESOURCE = 'Panth_BannerSlider::slide';

    public function execute()
    {
        return $this->resultFactory->create(ResultFactory::TYPE_FORWARD)->forward('edit');
    }
}
