<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use Panth\BannerSlider\Model\ResourceModel\Slide as SlideResource;

class Slide extends AbstractModel implements IdentityInterface
{
    public const CACHE_TAG = 'panth_banner_slide';

    protected $_eventPrefix = 'panth_banner_slide';

    protected function _construct(): void
    {
        $this->_init(SlideResource::class);
    }

    public function getIdentities(): array
    {
        $identities = [self::CACHE_TAG . '_' . $this->getId()];
        foreach ([$this->getData('slider_id'), $this->getOrigData('slider_id')] as $sliderId) {
            if ($sliderId) {
                $identities[] = Slider::CACHE_TAG . '_' . (int)$sliderId;
            }
        }
        return array_values(array_unique($identities));
    }
}
