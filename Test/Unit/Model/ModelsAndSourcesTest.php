<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Test\Unit\Model;

use Magento\Framework\DataObject;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Registry;
use Panth\BannerSlider\Model\Config\Source\SliderOptions;
use Panth\BannerSlider\Model\Config\Source\TransitionEffect;
use Panth\BannerSlider\Model\ResourceModel\Slider\Collection;
use Panth\BannerSlider\Model\ResourceModel\Slider\CollectionFactory;
use Panth\BannerSlider\Model\Slide;
use Panth\BannerSlider\Model\Slider;
use PHPUnit\Framework\TestCase;

class ModelsAndSourcesTest extends TestCase
{
    private function model(string $class, string $idField)
    {
        $resource = $this->createStub(AbstractDb::class);
        $resource->method('getIdFieldName')->willReturn($idField);
        return new $class($this->createStub(Context::class), $this->createStub(Registry::class), $resource);
    }

    public function testSliderIdentitiesForNewAndSavedSlider(): void
    {
        $slider = $this->model(Slider::class, 'slider_id');
        $this->assertSame(['panth_banner_slider'], $slider->getIdentities());

        $slider->setId(6);
        $this->assertSame(['panth_banner_slider', 'panth_banner_slider_6'], $slider->getIdentities());
    }

    public function testSlideIdentitiesIncludeCurrentAndPreviousSlider(): void
    {
        $slide = $this->model(Slide::class, 'slide_id');
        $slide->setData(['slide_id' => 3, 'slider_id' => 1]);
        $slide->setOrigData();
        $slide->setData('slider_id', 2);

        $this->assertSame(
            ['panth_banner_slide_3', 'panth_banner_slider_2', 'panth_banner_slider_1'],
            $slide->getIdentities()
        );
    }

    public function testSlideIdentitiesDeduplicateUnchangedSlider(): void
    {
        $slide = $this->model(Slide::class, 'slide_id');
        $slide->setData(['slide_id' => 3, 'slider_id' => 1]);
        $slide->setOrigData();

        $this->assertSame(['panth_banner_slide_3', 'panth_banner_slider_1'], $slide->getIdentities());
    }

    public function testSlideWithoutSliderOnlyHasOwnTag(): void
    {
        $slide = $this->model(Slide::class, 'slide_id');
        $slide->setData(['slide_id' => 3, 'slider_id' => 0]);

        $this->assertSame(['panth_banner_slide_3'], $slide->getIdentities());
    }

    public function testTransitionEffectOptions(): void
    {
        $options = (new TransitionEffect())->toOptionArray();
        $this->assertSame(['fade', 'slide'], array_column($options, 'value'));
        $this->assertSame('Fade', (string)$options[0]['label']);
    }

    public function testSliderOptionsListSlidersWithIdentifierAndPlaceholder(): void
    {
        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())->method('setOrder')->with('title', 'ASC')->willReturnSelf();
        $collection->method('getIterator')->willReturn(new \ArrayIterator([
            new DataObject(['id' => 1, 'title' => 'Home', 'identifier' => 'home']),
            new DataObject(['id' => 2, 'title' => 'Promo', 'identifier' => 'promo']),
        ]));
        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        $options = (new SliderOptions($factory))->toOptionArray();

        $this->assertCount(3, $options);
        $this->assertSame('', $options[0]['value']);
        $this->assertSame('-- Select Slider --', (string)$options[0]['label']);
        $this->assertSame(['value' => 1, 'label' => 'Home (home)'], $options[1]);
        $this->assertSame(['value' => 2, 'label' => 'Promo (promo)'], $options[2]);
    }
}
