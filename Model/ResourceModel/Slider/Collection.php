<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Model\ResourceModel\Slider;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Panth\BannerSlider\Model\Slider;
use Panth\BannerSlider\Model\ResourceModel\Slider as SliderResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'slider_id';

    protected function _construct(): void
    {
        $this->_init(Slider::class, SliderResource::class);
    }
}
