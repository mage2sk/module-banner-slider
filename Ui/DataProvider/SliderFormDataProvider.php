<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Ui\DataProvider;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Panth\BannerSlider\Model\ResourceModel\Slider\CollectionFactory;

class SliderFormDataProvider extends AbstractDataProvider
{
    private ?array $loadedData = null;

    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
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
        return $this->loadedData;
    }
}
