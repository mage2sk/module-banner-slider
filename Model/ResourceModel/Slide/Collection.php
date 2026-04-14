<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 */
declare(strict_types=1);

namespace Panth\BannerSlider\Model\ResourceModel\Slide;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Panth\BannerSlider\Model\Slide;
use Panth\BannerSlider\Model\ResourceModel\Slide as SlideResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'slide_id';
    protected $_eventPrefix = 'panth_banner_slide_collection';

    protected function _construct(): void
    {
        $this->_init(Slide::class, SlideResource::class);
    }
}
