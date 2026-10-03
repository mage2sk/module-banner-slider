<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class SliderActions extends Column
{
    private UrlInterface $urlBuilder;

    public function __construct(ContextInterface $context, UiComponentFactory $uiComponentFactory, UrlInterface $urlBuilder, array $components = [], array $data = [])
    {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $name = $this->getData('name');
                $item[$name]['edit'] = [
                    'href' => $this->urlBuilder->getUrl('panth_bannerslider/slider/edit', ['slider_id' => $item['slider_id']]),
                    'label' => __('Edit'),
                ];
                $item[$name]['delete'] = [
                    'href' => $this->urlBuilder->getUrl('panth_bannerslider/slider/delete', ['slider_id' => $item['slider_id']]),
                    'label' => __('Delete'),
                    'confirm' => ['title' => __('Delete Slider'), 'message' => __('Are you sure?')],
                    'post' => true,
                ];
            }
        }
        return $dataSource;
    }
}
