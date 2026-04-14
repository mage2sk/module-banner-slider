<?php
/**
 * Copyright © Panth Infotech. All rights reserved.
 */
declare(strict_types=1);

namespace Panth\BannerSlider\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class TransitionEffect implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'fade', 'label' => __('Fade')],
            ['value' => 'slide', 'label' => __('Slide')],
        ];
    }
}
