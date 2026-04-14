<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Model;

use Magento\Framework\Model\AbstractModel;
use Panth\BannerSlider\Model\ResourceModel\Slider as SliderResource;

class Slider extends AbstractModel
{
    protected $_eventPrefix = 'panth_banner_slider';

    protected function _construct(): void
    {
        $this->_init(SliderResource::class);
    }
}
