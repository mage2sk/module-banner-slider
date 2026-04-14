<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Slider extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('panth_banner_slider', 'slider_id');
    }
}
