<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Test\Unit\Controller\Adminhtml\Slider;

use Magento\Ui\Component\MassAction\Filter;
use Panth\BannerSlider\Controller\Adminhtml\Slider\MassDelete;
use Panth\BannerSlider\Model\ResourceModel\Slider as SliderResource;
use Panth\BannerSlider\Model\ResourceModel\Slider\Collection as SliderCollection;
use Panth\BannerSlider\Model\ResourceModel\Slider\CollectionFactory as SliderCollectionFactory;
use Panth\BannerSlider\Model\Slider;
use Panth\BannerSlider\Test\Unit\Controller\Adminhtml\AbstractControllerTestCase;

class MassDeleteTest extends AbstractControllerTestCase
{
    private function collection(array $sliders): SliderCollectionFactory
    {
        $collection = $this->createStub(SliderCollection::class);
        $collection->method('getIterator')->willReturn(new \ArrayIterator($sliders));
        $factory = $this->createStub(SliderCollectionFactory::class);
        $factory->method('create')->willReturn($collection);
        return $factory;
    }

    public function testDeletesEverySelectedSlider(): void
    {
        $sliders = [$this->model(Slider::class, 'slider_id'), $this->model(Slider::class, 'slider_id')];
        $factory = $this->collection($sliders);
        $filter = $this->createMock(Filter::class);
        $filter->expects($this->once())->method('getCollection')->willReturnArgument(0);
        $resource = $this->createMock(SliderResource::class);
        $deleted = [];
        $resource->expects($this->exactly(2))->method('delete')->willReturnCallback(
            function ($slider) use (&$deleted, $resource) {
                $deleted[] = $slider;
                return $resource;
            }
        );

        (new MassDelete($this->buildContext(), $filter, $factory, $resource))->execute();

        $this->assertSame($sliders, $deleted);
        $this->assertSame(['A total of 2 slider(s) have been deleted.'], $this->messages['success']);
        $this->assertSame('*/*/', $this->redirect['path']);
    }

    public function testEmptySelectionReportsZero(): void
    {
        $filter = $this->createStub(Filter::class);
        $filter->method('getCollection')->willReturnArgument(0);
        $resource = $this->createMock(SliderResource::class);
        $resource->expects($this->never())->method('delete');

        (new MassDelete($this->buildContext(), $filter, $this->collection([]), $resource))->execute();

        $this->assertSame(['A total of 0 slider(s) have been deleted.'], $this->messages['success']);
    }

    public function testFailureIsReportedAndStillRedirectsToGrid(): void
    {
        $filter = $this->createStub(Filter::class);
        $filter->method('getCollection')->willThrowException(new \Exception('Selection is empty'));
        $resource = $this->createMock(SliderResource::class);
        $resource->expects($this->never())->method('delete');

        (new MassDelete($this->buildContext(), $filter, $this->collection([]), $resource))->execute();

        $this->assertSame(['Selection is empty'], $this->messages['error']);
        $this->assertSame([], $this->messages['success']);
        $this->assertSame('*/*/', $this->redirect['path']);
    }
}
