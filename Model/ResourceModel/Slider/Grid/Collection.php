<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Model\ResourceModel\Slider\Grid;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Event\ManagerInterface;
use Psr\Log\LoggerInterface;
use Panth\BannerSlider\Model\ResourceModel\Slider as SliderResource;

class Collection extends SearchResult
{
    protected $_idFieldName = 'slider_id';

    public function __construct(
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        $mainTable = 'panth_banner_slider',
        $resourceModel = SliderResource::class
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
    }

    protected function _initSelect()
    {
        parent::_initSelect();
        $this->addFilterToMap('slider_id', 'main_table.slider_id');
        return $this;
    }

    protected function _afterLoad()
    {
        parent::_afterLoad();
        foreach ($this->_items as $item) {
            if ($item->getData('slider_id')) {
                $item->setId($item->getData('slider_id'));
            }
        }
        return $this;
    }
}
