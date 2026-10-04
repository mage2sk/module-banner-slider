<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Ui\DataProvider;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Panth\BannerSlider\Model\ResourceModel\Slider\CollectionFactory;

class SliderFormDataProvider extends AbstractDataProvider
{
    private ?array $loadedData = null;
    private ?DataPersistorInterface $dataPersistor;

    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = [],
        ?DataPersistorInterface $dataPersistor = null
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData(): array
    {
        if ($this->loadedData !== null) {
            return $this->loadedData;
        }

        $this->loadedData = [];
        foreach ($this->collection->getItems() as $slider) {
            $this->loadedData[$slider->getId()] = $slider->getData();
        }

        if ($this->dataPersistor !== null) {
            $persisted = $this->dataPersistor->get('panth_banner_slider');
            if (!empty($persisted) && is_array($persisted)) {
                $id = $persisted['slider_id'] ?? null;
                $this->loadedData[$id] = $persisted;
                $this->dataPersistor->clear('panth_banner_slider');
            }
        }

        return $this->loadedData;
    }
}
