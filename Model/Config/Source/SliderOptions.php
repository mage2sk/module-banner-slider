<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Panth\BannerSlider\Model\ResourceModel\Slider\CollectionFactory;

class SliderOptions implements OptionSourceInterface
{
    private CollectionFactory $collectionFactory;

    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => __('-- Select Slider --')]];

        $collection = $this->collectionFactory->create();
        $collection->setOrder('title', 'ASC');

        foreach ($collection as $slider) {
            $options[] = [
                'value' => $slider->getId(),
                'label' => $slider->getData('title') . ' (' . $slider->getData('identifier') . ')',
            ];
        }

        return $options;
    }
}
