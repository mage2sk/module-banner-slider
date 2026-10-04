<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Slider extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('panth_banner_slider', 'slider_id');
    }

    protected function _afterDelete(AbstractModel $object)
    {
        $sliderId = (int)$object->getId();
        if ($sliderId > 0) {
            $this->getConnection()->update(
                $this->getTable('panth_banner_slide'),
                ['slider_id' => 0],
                ['slider_id = ?' => $sliderId]
            );
        }
        return parent::_afterDelete($object);
    }
}
