<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Test\Unit\Controller\Adminhtml\Slide;

use Magento\Catalog\Model\ImageUploader;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Panth\BannerSlider\Controller\Adminhtml\Slide\Save;
use Panth\BannerSlider\Helper\Data as BannerHelper;
use Panth\BannerSlider\Model\ResourceModel\Slide as SlideResource;
use Panth\BannerSlider\Model\Slide;
use Panth\BannerSlider\Model\SlideFactory;
use Panth\BannerSlider\Test\Unit\Controller\Adminhtml\AbstractControllerTestCase;
use Psr\Log\LoggerInterface;

class SaveTest extends AbstractControllerTestCase
{
    private Slide $slide;
    private ?array $savedData = null;
    private bool $saveCalled = false;

    private function controller(
        array $post,
        array $params = [],
        array $tmpFiles = [],
        ?ImageUploader $uploader = null,
        ?\Exception $saveError = null,
        ?int $existingId = null,
        ?LoggerInterface $logger = null,
        ?DataPersistorInterface $persistor = null
    ): Save {
        $context = $this->buildContext($params, $post);

        $this->slide = $this->model(Slide::class, 'slide_id');
        $factory = $this->createStub(SlideFactory::class);
        $factory->method('create')->willReturn($this->slide);

        $resource = $this->createStub(SlideResource::class);
        $resource->method('load')->willReturnCallback(
            function ($object, $id) use ($existingId) {
                if ($existingId !== null && (int)$id === $existingId) {
                    $object->setId($existingId);
                }
                return $this->createStub(SlideResource::class);
            }
        );
        $resource->method('save')->willReturnCallback(
            function ($object) use ($saveError) {
                $this->saveCalled = true;
                if ($saveError) {
                    throw $saveError;
                }
                $this->savedData = $object->getData();
                if (!$object->getId()) {
                    $object->setId(55);
                }
                return $this->createStub(SlideResource::class);
            }
        );

        $mediaDir = $this->createStub(ReadInterface::class);
        $mediaDir->method('isFile')->willReturnCallback(static fn($path) => in_array($path, $tmpFiles, true));
        $filesystem = $this->createStub(Filesystem::class);
        $filesystem->method('getDirectoryRead')->willReturn($mediaDir);

        $helper = new BannerHelper(
            $this->createStub(\Magento\Framework\App\Helper\Context::class),
            $this->createStub(\Magento\Store\Model\StoreManagerInterface::class),
            $this->createStub(\Panth\BannerSlider\Model\ResourceModel\Slider\CollectionFactory::class),
            $this->createStub(\Panth\BannerSlider\Model\ResourceModel\Slide\CollectionFactory::class),
            $this->createStub(\Magento\Framework\Stdlib\DateTime\TimezoneInterface::class)
        );

        return new Save(
            $context,
            $factory,
            $resource,
            $uploader ?? $this->createStub(ImageUploader::class),
            $logger ?? $this->createStub(LoggerInterface::class),
            $filesystem,
            $helper,
            $persistor
        );
    }

    public function testEmptyPostRedirectsToGrid(): void
    {
        $this->controller([])->execute();
        $this->assertSame('*/*/', $this->redirect['path']);
        $this->assertFalse($this->saveCalled);
    }

    public function testMissingSlideShowsErrorAndDoesNotSave(): void
    {
        $this->controller(['slide_id' => '12', 'title' => 'x'])->execute();

        $this->assertSame('*/*/', $this->redirect['path']);
        $this->assertSame(['This slide no longer exists.'], $this->messages['error']);
        $this->assertFalse($this->saveCalled);
    }

    public function testUnsafeLinkIsRejected(): void
    {
        $this->controller(['slide_id' => '12', 'link_url' => 'javascript:alert(1)'], [], [], null, null, 12)
            ->execute();

        $this->assertSame('*/*/edit', $this->redirect['path']);
        $this->assertSame(['slide_id' => 12], $this->redirect['params']);
        $this->assertStringContainsString('link URL must be', $this->messages['error'][0]);
        $this->assertFalse($this->saveCalled);
    }

    public function testNewSlideIsNormalisedAndSaved(): void
    {
        $this->controller([
            'data' => [
                'slide_id' => '',
                'title' => 'Summer',
                'form_key' => 'abc',
                'key' => 'k',
                'link_url' => '  ',
                'link_target' => '_top',
                'desktop_image' => '',
                'date_from' => '',
                'date_to' => '',
            ],
        ])->execute();

        $this->assertTrue($this->saveCalled);
        $this->assertArrayNotHasKey('form_key', $this->savedData);
        $this->assertArrayNotHasKey('key', $this->savedData);
        $this->assertArrayNotHasKey('slide_id', $this->savedData);
        $this->assertSame('Summer', $this->savedData['title']);
        $this->assertNull($this->savedData['link_url']);
        $this->assertSame('_self', $this->savedData['link_target']);
        $this->assertNull($this->savedData['desktop_image']);
        $this->assertNull($this->savedData['tablet_image']);
        $this->assertNull($this->savedData['mobile_image']);
        $this->assertNull($this->savedData['date_from']);
        $this->assertNull($this->savedData['date_to']);
        $this->assertSame(['The slide has been saved.'], $this->messages['success']);
        $this->assertSame('*/*/', $this->redirect['path']);
    }

    public function testValidLinkAndTargetAreKept(): void
    {
        $this->controller([
            'title' => 'x',
            'link_url' => ' /sale.html ',
            'link_target' => '_blank',
            'date_from' => '2026-01-01',
        ])->execute();

        $this->assertSame('/sale.html', $this->savedData['link_url']);
        $this->assertSame('_blank', $this->savedData['link_target']);
        $this->assertSame('2026-01-01', $this->savedData['date_from']);
    }

    public function testExistingImagePathIsKeptWithoutMovingFromTmp(): void
    {
        $uploader = $this->createMock(ImageUploader::class);
        $uploader->expects($this->never())->method('moveFileFromTmp');

        $this->controller(
            ['title' => 'x', 'desktop_image' => [['file' => 'bannerslider/hero.jpg', 'name' => 'hero.jpg']]],
            [],
            ['bannerslider/tmp/hero.jpg'],
            $uploader
        )->execute();

        $this->assertSame('bannerslider/hero.jpg', $this->savedData['desktop_image']);
    }

    public function testNewUploadIsMovedFromTmp(): void
    {
        $uploader = $this->createMock(ImageUploader::class);
        $uploader->expects($this->once())
            ->method('moveFileFromTmp')
            ->with('new.png', true)
            ->willReturn('bannerslider/new_1.png');

        $this->controller(
            ['title' => 'x', 'tablet_image' => [['name' => 'new.png']]],
            [],
            ['bannerslider/tmp/new.png'],
            $uploader
        )->execute();

        $this->assertSame('bannerslider/new_1.png', $this->savedData['tablet_image']);
    }

    public function testNewUploadWithoutTmpFileFallsBackToTargetPath(): void
    {
        $uploader = $this->createMock(ImageUploader::class);
        $uploader->expects($this->never())->method('moveFileFromTmp');

        $this->controller(['title' => 'x', 'mobile_image' => [['name' => 'm.png']]], [], [], $uploader)->execute();

        $this->assertSame('bannerslider/m.png', $this->savedData['mobile_image']);
    }

    public function testMoveFailureIsLoggedAndPathStillSaved(): void
    {
        $uploader = $this->createStub(ImageUploader::class);
        $uploader->method('moveFileFromTmp')->willThrowException(new \Exception('disk full'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with($this->stringContains('disk full'));

        $this->controller(
            ['title' => 'x', 'desktop_image' => [['name' => 'a.jpg']]],
            [],
            ['bannerslider/tmp/a.jpg'],
            $uploader,
            null,
            null,
            $logger
        )->execute();

        $this->assertSame('bannerslider/a.jpg', $this->savedData['desktop_image']);
        $this->assertTrue($this->saveCalled);
    }

    public function testPathTraversalInImageNameIsReducedToBasename(): void
    {
        $this->controller([
            'title' => 'x',
            'desktop_image' => [['file' => '..\\..\\app\\etc\\env.php']],
            'tablet_image' => [['name' => '..']],
            'mobile_image' => [[]],
        ])->execute();

        $this->assertSame('bannerslider/env.php', $this->savedData['desktop_image']);
        $this->assertNull($this->savedData['tablet_image']);
        $this->assertNull($this->savedData['mobile_image']);
    }

    public function testSaveAndContinueRedirectsToEditWithSavedId(): void
    {
        $this->controller(['title' => 'x'], ['back' => 'edit'])->execute();
        $this->assertSame('*/*/edit', $this->redirect['path']);
        $this->assertSame(['slide_id' => 55], $this->redirect['params']);
    }

    public function testExistingSlideKeepsItsId(): void
    {
        $this->controller(['slide_id' => '12', 'title' => 'Updated'], ['back' => '1'], [], null, null, 12)->execute();
        $this->assertSame(12, (int)$this->savedData['slide_id']);
        $this->assertEquals(['slide_id' => 12], $this->redirect['params']);
    }

    public function testSaveExceptionShowsErrorAndReturnsToEdit(): void
    {
        $this->controller(['slide_id' => '12', 'title' => 'x'], [], [], null, new \Exception('DB down'), 12)->execute();

        $this->assertSame(['DB down'], $this->messages['error']);
        $this->assertSame('*/*/edit', $this->redirect['path']);
        $this->assertSame(['slide_id' => 12], $this->redirect['params']);
        $this->assertSame([], $this->messages['success']);
    }

    public function testDateRangeEndingBeforeStartIsRejectedWithoutMovingImages(): void
    {
        $uploader = $this->createMock(ImageUploader::class);
        $uploader->expects($this->never())->method('moveFileFromTmp');

        $this->controller(
            [
                'title' => 'x',
                'date_from' => '10/20/2026',
                'date_to' => '10/10/2026',
                'desktop_image' => [['name' => 'new.png']],
            ],
            [],
            ['bannerslider/tmp/new.png'],
            $uploader
        )->execute();

        $this->assertFalse($this->saveCalled);
        $this->assertSame(['The Active To date cannot be earlier than the Active From date.'], $this->messages['error']);
        $this->assertSame('*/*/new', $this->redirect['path']);
    }

    public function testSameDayRangeIsAccepted(): void
    {
        $this->controller(['title' => 'x', 'date_from' => '10/20/2026', 'date_to' => '10/20/2026'])->execute();

        $this->assertTrue($this->saveCalled);
        $this->assertSame('10/20/2026', $this->savedData['date_to']);
    }

    public function testValidationErrorPersistsTypedValuesForTheForm(): void
    {
        $persistor = $this->createMock(DataPersistorInterface::class);
        $persistor->expects($this->once())->method('set')->with(
            'panth_banner_slide',
            [
                'title' => 'Typed',
                'link_url' => 'javascript:alert(1)',
                'desktop_image' => [['name' => 'up.png', 'url' => 'https://shop.test/media/bannerslider/tmp/up.png']],
            ]
        );
        $persistor->expects($this->never())->method('clear');

        $this->controller(
            [
                'form_key' => 'fk',
                'title' => 'Typed',
                'link_url' => 'javascript:alert(1)',
                'desktop_image' => [['name' => 'up.png', 'url' => 'https://shop.test/media/bannerslider/tmp/up.png']],
            ],
            [],
            [],
            null,
            null,
            null,
            null,
            $persistor
        )->execute();

        $this->assertSame('*/*/new', $this->redirect['path']);
        $this->assertFalse($this->saveCalled);
    }

    public function testSaveExceptionPersistsValuesAndSuccessClearsThem(): void
    {
        $failing = $this->createMock(DataPersistorInterface::class);
        $failing->expects($this->once())->method('set')->with('panth_banner_slide', ['slide_id' => '12', 'title' => 'x']);
        $this->controller(['slide_id' => '12', 'title' => 'x'], [], [], null, new \Exception('DB down'), 12, null, $failing)
            ->execute();

        $ok = $this->createMock(DataPersistorInterface::class);
        $ok->expects($this->never())->method('set');
        $ok->expects($this->once())->method('clear')->with('panth_banner_slide');
        $this->controller(['title' => 'fine'], [], [], null, null, null, null, $ok)->execute();
        $this->assertSame(['The slide has been saved.'], $this->messages['success']);
    }
}
