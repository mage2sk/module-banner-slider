<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use Panth\BannerSlider\Model\ResourceModel\Slider as SliderResource;

class Slider extends AbstractModel implements IdentityInterface
{
    public const CACHE_TAG = 'panth_banner_slider';

    protected $_eventPrefix = 'panth_banner_slider';

    protected $_cacheTag = self::CACHE_TAG;

    protected function _construct(): void
    {
        $this->_init(SliderResource::class);
    }

    public function getIdentities(): array
    {
        $identities = [self::CACHE_TAG];
        if ($this->getId()) {
            $identities[] = self::CACHE_TAG . '_' . $this->getId();
        }
        return $identities;
    }
}
