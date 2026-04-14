<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\UrlInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class Actions extends Column
{
    private UrlInterface $urlBuilder;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $name = $this->getData('name');
                $item[$name]['edit'] = [
                    'href' => $this->urlBuilder->getUrl('panth_bannerslider/slide/edit', ['slide_id' => $item['slide_id']]),
                    'label' => __('Edit'),
                ];
                $item[$name]['delete'] = [
                    'href' => $this->urlBuilder->getUrl('panth_bannerslider/slide/delete', ['slide_id' => $item['slide_id']]),
                    'label' => __('Delete'),
                    'confirm' => [
                        'title' => __('Delete Slide'),
                        'message' => __('Are you sure you want to delete this slide?'),
                    ],
                    'post' => true,
                ];
            }
        }
        return $dataSource;
    }
}
