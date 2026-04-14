<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 */
declare(strict_types=1);

namespace Panth\BannerSlider\Model;

use Magento\Framework\Model\AbstractModel;
use Panth\BannerSlider\Model\ResourceModel\Slide as SlideResource;

class Slide extends AbstractModel
{
    protected $_eventPrefix = 'panth_banner_slide';

    protected function _construct(): void
    {
        $this->_init(SlideResource::class);
    }

    public function getIdentities(): array
    {
        return ['panth_banner_slide_' . $this->getId()];
    }
}
