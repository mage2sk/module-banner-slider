<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Test\Unit\Controller\Adminhtml;

use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Framework\View\Page\Title;
use Magento\Ui\Component\MassAction\Filter;
use Panth\BannerSlider\Controller\Adminhtml\Slide\Delete as SlideDelete;
use Panth\BannerSlider\Controller\Adminhtml\Slide\Edit as SlideEdit;
use Panth\BannerSlider\Controller\Adminhtml\Slide\MassDelete;
use Panth\BannerSlider\Controller\Adminhtml\Slider\Delete as SliderDelete;
use Panth\BannerSlider\Model\ResourceModel\Slide as SlideResource;
use Panth\BannerSlider\Model\ResourceModel\Slide\Collection as SlideCollection;
use Panth\BannerSlider\Model\ResourceModel\Slide\CollectionFactory as SlideCollectionFactory;
use Panth\BannerSlider\Model\ResourceModel\Slider as SliderResource;
use Panth\BannerSlider\Model\Slide;
use Panth\BannerSlider\Model\SlideFactory;
use Panth\BannerSlider\Model\Slider;
use Panth\BannerSlider\Model\SliderFactory;

class DeleteActionsTest extends AbstractControllerTestCase
{
    public function testSlideDeleteWithoutIdOnlyRedirects(): void
    {
        $context = $this->buildContext([]);
        $resource = $this->createMock(SlideResource::class);
        $resource->expects($this->never())->method('delete');

        (new SlideDelete($context, $this->createStub(SlideFactory::class), $resource))->execute();

        $this->assertSame('*/*/', $this->redirect['path']);
        $this->assertSame([], $this->messages['success']);
    }

    public function testSlideDeleteLoadsAndDeletes(): void
    {
        $context = $this->buildContext(['slide_id' => '8']);
        $slide = $this->model(Slide::class, 'slide_id');
        $factory = $this->createStub(SlideFactory::class);
        $factory->method('create')->willReturn($slide);
        $resource = $this->createMock(SlideResource::class);
        $resource->expects($this->once())->method('load')->with($slide, 8)->willReturnSelf();
        $resource->expects($this->once())->method('delete')->with($slide)->willReturnSelf();

        (new SlideDelete($context, $factory, $resource))->execute();

        $this->assertSame(['The slide has been deleted.'], $this->messages['success']);
        $this->assertSame('*/*/', $this->redirect['path']);
    }

    public function testSlideDeleteErrorIsReported(): void
    {
        $context = $this->buildContext(['slide_id' => '8']);
        $factory = $this->createStub(SlideFactory::class);
        $factory->method('create')->willReturn($this->model(Slide::class, 'slide_id'));
        $resource = $this->createStub(SlideResource::class);
        $resource->method('delete')->willThrowException(new \Exception('locked'));

        (new SlideDelete($context, $factory, $resource))->execute();

        $this->assertSame(['locked'], $this->messages['error']);
        $this->assertSame('*/*/', $this->redirect['path']);
    }

    public function testSliderDeleteLoadsAndDeletes(): void
    {
        $context = $this->buildContext(['slider_id' => '3']);
        $slider = $this->model(Slider::class, 'slider_id');
        $factory = $this->createStub(SliderFactory::class);
        $factory->method('create')->willReturn($slider);
        $resource = $this->createMock(SliderResource::class);
        $resource->expects($this->once())->method('load')->with($slider, 3)->willReturnSelf();
        $resource->expects($this->once())->method('delete')->with($slider)->willReturnSelf();

        (new SliderDelete($context, $factory, $resource))->execute();

        $this->assertSame(['The slider has been deleted.'], $this->messages['success']);
    }

    public function testSliderDeleteWithoutIdAndErrorPath(): void
    {
        $resource = $this->createMock(SliderResource::class);
        $resource->expects($this->never())->method('delete');
        (new SliderDelete($this->buildContext([]), $this->createStub(SliderFactory::class), $resource))->execute();
        $this->assertSame('*/*/', $this->redirect['path']);

        $factory = $this->createStub(SliderFactory::class);
        $factory->method('create')->willReturn($this->model(Slider::class, 'slider_id'));
        $failing = $this->createStub(SliderResource::class);
        $failing->method('delete')->willThrowException(new \Exception('nope'));
        (new SliderDelete($this->buildContext(['slider_id' => 2]), $factory, $failing))->execute();
        $this->assertSame(['nope'], $this->messages['error']);
    }

    public function testMassDeleteDeletesEveryFilteredSlide(): void
    {
        $context = $this->buildContext();
        $slides = [
            $this->model(Slide::class, 'slide_id'),
            $this->model(Slide::class, 'slide_id'),
            $this->model(Slide::class, 'slide_id'),
        ];
        $collection = $this->createStub(SlideCollection::class);
        $collection->method('getIterator')->willReturn(new \ArrayIterator($slides));
        $factory = $this->createStub(SlideCollectionFactory::class);
        $factory->method('create')->willReturn($collection);
        $filter = $this->createStub(Filter::class);
        $filter->method('getCollection')->willReturn($collection);
        $resource = $this->createMock(SlideResource::class);
        $resource->expects($this->exactly(3))->method('delete')->willReturnSelf();

        (new MassDelete($context, $filter, $factory, $resource))->execute();

        $this->assertSame(['A total of 3 slide(s) have been deleted.'], $this->messages['success']);
        $this->assertSame('*/*/', $this->redirect['path']);
    }

    private function editController(array $params, ?Slide $slide, ?Title $title = null): SlideEdit
    {
        $context = $this->buildContext($params);
        $factory = $this->createStub(SlideFactory::class);
        $factory->method('create')->willReturn($slide);

        $config = $this->createStub(PageConfig::class);
        $config->method('getTitle')->willReturn($title ?? $this->createStub(Title::class));
        $page = $this->createStub(Page::class);
        $page->method('getConfig')->willReturn($config);
        $pageFactory = $this->createStub(PageFactory::class);
        $pageFactory->method('create')->willReturn($page);

        return new SlideEdit($context, $pageFactory, $factory);
    }

    public function testEditRedirectsWhenSlideMissing(): void
    {
        $slide = $this->createStub(Slide::class);
        $slide->method('load')->willReturnSelf();
        $slide->method('getId')->willReturn(null);

        $result = $this->editController(['slide_id' => '5'], $slide)->execute();

        $this->assertNotInstanceOf(Page::class, $result);
        $this->assertSame(['This slide no longer exists.'], $this->messages['error']);
        $this->assertSame('*/*/', $this->redirect['path']);
    }

    public function testEditShowsSlideTitle(): void
    {
        $slide = $this->createStub(Slide::class);
        $slide->method('load')->willReturnSelf();
        $slide->method('getId')->willReturn(5);
        $slide->method('getData')->willReturn('Spring');
        $title = $this->createMock(Title::class);
        $title->expects($this->once())->method('prepend')
            ->with($this->callback(static fn($t) => (string)$t === 'Edit Slide: Spring'));

        $this->assertInstanceOf(Page::class, $this->editController(['slide_id' => '5'], $slide, $title)->execute());
    }

    public function testEditFallsBackToIdWhenTitleEmpty(): void
    {
        $slide = $this->createStub(Slide::class);
        $slide->method('load')->willReturnSelf();
        $slide->method('getId')->willReturn(5);
        $slide->method('getData')->willReturn('');
        $title = $this->createMock(Title::class);
        $title->expects($this->once())->method('prepend')
            ->with($this->callback(static fn($t) => (string)$t === 'Edit Slide: #5'));

        $this->editController(['slide_id' => '5'], $slide, $title)->execute();
    }

    public function testNewSlideTitle(): void
    {
        $title = $this->createMock(Title::class);
        $title->expects($this->once())->method('prepend')
            ->with($this->callback(static fn($t) => (string)$t === 'New Banner Slide'));

        $this->editController([], $this->createStub(Slide::class), $title)->execute();
        $this->assertSame([], $this->messages['error']);
    }
}
