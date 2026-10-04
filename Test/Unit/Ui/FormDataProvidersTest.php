<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Test\Unit\Ui;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Panth\BannerSlider\Model\ResourceModel\Slide\Collection as SlideCollection;
use Panth\BannerSlider\Model\ResourceModel\Slide\CollectionFactory as SlideCollectionFactory;
use Panth\BannerSlider\Model\ResourceModel\Slider\Collection as SliderCollection;
use Panth\BannerSlider\Model\ResourceModel\Slider\CollectionFactory as SliderCollectionFactory;
use Panth\BannerSlider\Ui\DataProvider\SlideFormDataProvider;
use Panth\BannerSlider\Ui\DataProvider\SliderFormDataProvider;
use PHPUnit\Framework\TestCase;

class FormDataProvidersTest extends TestCase
{
    private function item(array $data): DataObject
    {
        return new DataObject($data);
    }

    public function testSliderProviderIndexesItemsByIdAndCaches(): void
    {
        $collection = $this->createMock(SliderCollection::class);
        $collection->expects($this->once())->method('getItems')->willReturn([
            $this->item(['id' => 1, 'title' => 'A']),
            $this->item(['id' => 2, 'title' => 'B']),
        ]);
        $factory = $this->createStub(SliderCollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        $provider = new SliderFormDataProvider('n', 'slider_id', 'slider_id', $factory);
        $expected = [1 => ['id' => 1, 'title' => 'A'], 2 => ['id' => 2, 'title' => 'B']];

        $this->assertSame($expected, $provider->getData());
        $this->assertSame($expected, $provider->getData());
    }

    public function testSliderProviderRestoresPersistedValuesAfterAFailedSave(): void
    {
        $collection = $this->createStub(SliderCollection::class);
        $collection->method('getItems')->willReturn([$this->item(['id' => 4, 'title' => 'Saved'])]);
        $factory = $this->createStub(SliderCollectionFactory::class);
        $factory->method('create')->willReturn($collection);
        $persistor = $this->createMock(DataPersistorInterface::class);
        $persistor->expects($this->once())->method('get')->with('panth_banner_slider')
            ->willReturn(['slider_id' => '4', 'title' => 'Typed', 'identifier' => 'bad id']);
        $persistor->expects($this->once())->method('clear')->with('panth_banner_slider');

        $provider = new SliderFormDataProvider('n', 'slider_id', 'slider_id', $factory, [], [], $persistor);
        $data = $provider->getData();

        $this->assertSame(['slider_id' => '4', 'title' => 'Typed', 'identifier' => 'bad id'], $data[4]);
    }

    public function testSliderProviderPutsPersistedNewSliderUnderEmptyKey(): void
    {
        $collection = $this->createStub(SliderCollection::class);
        $collection->method('getItems')->willReturn([]);
        $factory = $this->createStub(SliderCollectionFactory::class);
        $factory->method('create')->willReturn($collection);
        $persistor = $this->createStub(DataPersistorInterface::class);
        $persistor->method('get')->willReturn(['title' => 'New one']);

        $data = (new SliderFormDataProvider('n', 'slider_id', 'slider_id', $factory, [], [], $persistor))->getData();

        $this->assertSame(['' => ['title' => 'New one']], $data);
    }

    private function slideProvider(
        array $items,
        array $existingFiles,
        array $persisted = [],
        ?DataPersistorInterface $persistor = null,
        array $sizes = []
    ): SlideFormDataProvider {
        $collection = $this->createStub(SlideCollection::class);
        $collection->method('getItems')->willReturn($items);
        $factory = $this->createStub(SlideCollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        $store = $this->createStub(Store::class);
        $store->method('getBaseUrl')->willReturn('https://shop.test/media/');
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $dir = $this->createStub(ReadInterface::class);
        $dir->method('isFile')->willReturnCallback(static fn($p) => in_array($p, $existingFiles, true));
        $dir->method('stat')->willReturnCallback(
            static function ($p) use ($sizes) {
                if (!array_key_exists($p, $sizes)) {
                    throw new \RuntimeException('missing ' . $p);
                }
                return ['size' => $sizes[$p]];
            }
        );
        $filesystem = $this->createStub(Filesystem::class);
        $filesystem->method('getDirectoryRead')->willReturn($dir);

        if ($persistor === null) {
            $persistor = $this->createStub(DataPersistorInterface::class);
            $persistor->method('get')->willReturn($persisted);
        }

        return new SlideFormDataProvider(
            'n',
            'slide_id',
            'slide_id',
            $factory,
            $persistor,
            $storeManager,
            $filesystem
        );
    }

    public function testSlideImagesAreExpandedForUploader(): void
    {
        $provider = $this->slideProvider(
            [$this->item(['id' => 5, 'title' => 'S', 'desktop_image' => '/bannerslider/hero.jpg'])],
            ['bannerslider/hero.jpg'],
            [],
            null,
            ['bannerslider/hero.jpg' => 2048]
        );

        $data = $provider->getData();

        $this->assertSame('S', $data[5]['title']);
        $this->assertSame([[
            'name' => 'hero.jpg',
            'url' => 'https://shop.test/media/bannerslider/hero.jpg',
            'file' => 'bannerslider/hero.jpg',
            'size' => 2048,
            'type' => 'image/jpg',
        ]], $data[5]['desktop_image']);
        $this->assertArrayNotHasKey('tablet_image', $data[5]);
    }

    public function testSlideImageFallsBackToTmpLocation(): void
    {
        $provider = $this->slideProvider(
            [$this->item(['id' => 5, 'mobile_image' => 'bannerslider/m.png'])],
            ['bannerslider/tmp/m.png'],
            [],
            null,
            ['bannerslider/tmp/m.png' => 10]
        );

        $image = $provider->getData()[5]['mobile_image'][0];

        $this->assertSame('https://shop.test/media/bannerslider/tmp/m.png', $image['url']);
        $this->assertSame('bannerslider/m.png', $image['file']);
        $this->assertSame(10, $image['size']);
        $this->assertSame('image/png', $image['type']);
    }

    public function testMissingImageHasZeroSizeButKeepsUrl(): void
    {
        $image = $this->slideProvider(
            [$this->item(['id' => 5, 'tablet_image' => 'bannerslider/gone.webp'])],
            []
        )->getData()[5]['tablet_image'][0];

        $this->assertSame(0, $image['size']);
        $this->assertSame('https://shop.test/media/bannerslider/gone.webp', $image['url']);
    }

    public function testPersistedFormDataOverridesAndIsCleared(): void
    {
        $persistor = $this->createMock(DataPersistorInterface::class);
        $persistor->expects($this->once())->method('get')->with('panth_banner_slide')
            ->willReturn(['slide_id' => 5, 'title' => 'Unsaved']);
        $persistor->expects($this->once())->method('clear')->with('panth_banner_slide');

        $provider = $this->slideProvider([$this->item(['id' => 5, 'title' => 'Stored'])], [], [], $persistor);

        $data = $provider->getData();
        $this->assertSame(['slide_id' => 5, 'title' => 'Unsaved'], $data[5]);
        $this->assertSame($data, $provider->getData());
    }
}
