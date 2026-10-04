<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Test\Unit\Ui;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Panth\BannerSlider\Ui\Component\Listing\Column\Actions;
use Panth\BannerSlider\Ui\Component\Listing\Column\SliderActions;
use Panth\BannerSlider\Ui\Component\Listing\Column\Thumbnail;
use PHPUnit\Framework\TestCase;

class ListingColumnsTest extends TestCase
{
    private function urlBuilder(): UrlInterface
    {
        $url = $this->createStub(UrlInterface::class);
        $url->method('getUrl')->willReturnCallback(
            static fn($route, $params = []) => 'https://admin.test/' . $route . '/' . http_build_query($params)
        );
        return $url;
    }

    public function testSlideActionsAddEditAndDeleteLinks(): void
    {
        $column = new Actions(
            $this->createStub(ContextInterface::class),
            $this->createStub(UiComponentFactory::class),
            $this->urlBuilder(),
            [],
            ['name' => 'actions']
        );

        $result = $column->prepareDataSource(['data' => ['items' => [['slide_id' => 4], ['slide_id' => 9]]]]);
        $items = $result['data']['items'];

        $this->assertSame('https://admin.test/panth_bannerslider/slide/edit/slide_id=4', $items[0]['actions']['edit']['href']);
        $this->assertSame('https://admin.test/panth_bannerslider/slide/delete/slide_id=9', $items[1]['actions']['delete']['href']);
        $this->assertTrue($items[1]['actions']['delete']['post']);
        $this->assertSame('Delete Slide', (string)$items[0]['actions']['delete']['confirm']['title']);
    }

    public function testSliderActionsAddEditAndDeleteLinks(): void
    {
        $column = new SliderActions(
            $this->createStub(ContextInterface::class),
            $this->createStub(UiComponentFactory::class),
            $this->urlBuilder(),
            [],
            ['name' => 'row_actions']
        );

        $items = $column->prepareDataSource(['data' => ['items' => [['slider_id' => 2]]]])['data']['items'];

        $this->assertSame('https://admin.test/panth_bannerslider/slider/edit/slider_id=2', $items[0]['row_actions']['edit']['href']);
        $this->assertSame('https://admin.test/panth_bannerslider/slider/delete/slider_id=2', $items[0]['row_actions']['delete']['href']);
        $this->assertTrue($items[0]['row_actions']['delete']['post']);
    }

    public function testActionColumnsLeaveSourceWithoutItemsUntouched(): void
    {
        $column = new Actions(
            $this->createStub(ContextInterface::class),
            $this->createStub(UiComponentFactory::class),
            $this->urlBuilder(),
            [],
            ['name' => 'actions']
        );
        $this->assertSame(['data' => ['totalRecords' => 0]], $column->prepareDataSource(['data' => ['totalRecords' => 0]]));
    }

    public function testThumbnailBuildsMediaUrlsAndAltText(): void
    {
        $store = $this->createStub(Store::class);
        $store->method('getBaseUrl')->willReturn('https://shop.test/media/');
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $column = new Thumbnail(
            $this->createStub(ContextInterface::class),
            $this->createStub(UiComponentFactory::class),
            $storeManager,
            [],
            ['name' => 'desktop_image']
        );

        $items = $column->prepareDataSource(['data' => ['items' => [
            ['desktop_image' => '/bannerslider/a.jpg', 'alt_text' => 'Alt A', 'title' => 'Title A'],
            ['desktop_image' => 'bannerslider/b.jpg', 'title' => 'Title B'],
            ['desktop_image' => 'bannerslider/c.jpg'],
            ['desktop_image' => ''],
        ]]])['data']['items'];

        $this->assertSame('https://shop.test/media/bannerslider/a.jpg', $items[0]['desktop_image_src']);
        $this->assertSame('https://shop.test/media/bannerslider/a.jpg', $items[0]['desktop_image_orig_src']);
        $this->assertSame('Alt A', $items[0]['desktop_image_alt']);
        $this->assertSame('Title B', $items[1]['desktop_image_alt']);
        $this->assertSame('', $items[2]['desktop_image_alt']);
        $this->assertArrayNotHasKey('desktop_image_src', $items[3]);
    }
}
