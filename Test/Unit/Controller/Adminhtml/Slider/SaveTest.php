<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Test\Unit\Controller\Adminhtml\Slider;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Panth\BannerSlider\Controller\Adminhtml\Slider\Save;
use Panth\BannerSlider\Model\ResourceModel\Slider as SliderResource;
use Panth\BannerSlider\Model\Slider;
use Panth\BannerSlider\Model\SliderFactory;
use Panth\BannerSlider\Test\Unit\Controller\Adminhtml\AbstractControllerTestCase;
use Psr\Log\LoggerInterface;

class SaveTest extends AbstractControllerTestCase
{
    private ?array $savedData = null;
    private bool $saveCalled = false;

    private function controller(
        array $post,
        array $params = [],
        ?int $existingId = null,
        ?\Exception $error = null,
        ?DataPersistorInterface $persistor = null
    ): Save {
        $context = $this->buildContext($params, $post);
        $slider = $this->model(Slider::class, 'slider_id');
        $factory = $this->createStub(SliderFactory::class);
        $factory->method('create')->willReturn($slider);

        $resource = $this->createStub(SliderResource::class);
        $resource->method('load')->willReturnCallback(
            function ($object, $id) use ($existingId) {
                if ($existingId !== null && (int)$id === $existingId) {
                    $object->setId($existingId);
                }
                return $this->createStub(SliderResource::class);
            }
        );
        $resource->method('save')->willReturnCallback(
            function ($object) use ($error) {
                $this->saveCalled = true;
                if ($error) {
                    throw $error;
                }
                $this->savedData = $object->getData();
                if (!$object->getId()) {
                    $object->setId(31);
                }
                return $this->createStub(SliderResource::class);
            }
        );

        return new Save($context, $factory, $resource, $this->createStub(LoggerInterface::class), $persistor);
    }

    public function testEmptyPostRedirectsToGrid(): void
    {
        $this->controller([])->execute();
        $this->assertSame('*/*/', $this->redirect['path']);
        $this->assertFalse($this->saveCalled);
    }

    public function testMissingSliderIsReported(): void
    {
        $this->controller(['slider_id' => '4'])->execute();
        $this->assertSame(['This slider no longer exists.'], $this->messages['error']);
        $this->assertSame('*/*/', $this->redirect['path']);
        $this->assertFalse($this->saveCalled);
    }

    public function testNewSliderFromNestedScopeIsSavedWithoutFormKeys(): void
    {
        $this->controller(['data' => [
            'slider_id' => '',
            'title' => 'Home',
            'identifier' => 'home',
            'form_key' => 'f',
            'key' => 'k',
        ]])->execute();

        $this->assertSame(['title' => 'Home', 'identifier' => 'home'], $this->savedData);
        $this->assertSame(['The slider has been saved.'], $this->messages['success']);
        $this->assertSame('*/*/', $this->redirect['path']);
    }

    public function testBackParamRedirectsToEditWithNewId(): void
    {
        $this->controller(['title' => 'Home'], ['back' => 'edit'])->execute();
        $this->assertSame('*/*/edit', $this->redirect['path']);
        $this->assertSame(['slider_id' => 31], $this->redirect['params']);
    }

    public function testExistingSliderIsUpdated(): void
    {
        $this->controller(['slider_id' => '4', 'title' => 'New'], [], 4)->execute();
        $this->assertEquals(4, $this->savedData['slider_id']);
        $this->assertSame('New', $this->savedData['title']);
    }

    public function testSaveErrorReturnsToEdit(): void
    {
        $this->controller(['slider_id' => '4', 'identifier' => 'dup'], [], 4, new \Exception('Duplicate identifier'))
            ->execute();
        $this->assertSame(['Duplicate identifier'], $this->messages['error']);
        $this->assertSame('*/*/edit', $this->redirect['path']);
        $this->assertSame(['slider_id' => 4], $this->redirect['params']);
    }

    public static function invalidIdentifiers(): array
    {
        return [
            'space' => ['home banner'],
            'leading digit' => ['9home'],
            'symbol' => ['home!'],
            'empty' => ['   '],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidIdentifiers')]
    public function testInvalidIdentifierIsRejectedAndValuesPersisted(string $identifier): void
    {
        $persistor = $this->createMock(DataPersistorInterface::class);
        $persistor->expects($this->once())->method('set')
            ->with('panth_banner_slider', ['title' => 'Home', 'identifier' => trim($identifier)]);

        $this->controller(['title' => 'Home', 'identifier' => $identifier], [], null, null, $persistor)->execute();

        $this->assertFalse($this->saveCalled);
        $this->assertStringContainsString('identifier must start with a letter', $this->messages['error'][0]);
        $this->assertSame('*/*/new', $this->redirect['path']);
    }

    public function testIdentifierIsTrimmedBeforeSaving(): void
    {
        $this->controller(['title' => 'Home', 'identifier' => '  home_hero-2  '])->execute();
        $this->assertSame('home_hero-2', $this->savedData['identifier']);
    }

    public function testDuplicateIdentifierShowsFriendlyMessage(): void
    {
        $this->controller(
            ['title' => 'Copy', 'identifier' => 'home'],
            [],
            null,
            new AlreadyExistsException(__('Unique constraint violation found'))
        )->execute();

        $this->assertSame(
            ['A slider with this identifier already exists for the selected store view.'],
            $this->messages['error']
        );
        $this->assertSame('*/*/new', $this->redirect['path']);
    }

    public function testDuplicateOnExistingSliderReturnsToItsEditPage(): void
    {
        $this->controller(
            ['slider_id' => '4', 'identifier' => 'home'],
            [],
            4,
            new AlreadyExistsException(__('Unique constraint violation found'))
        )->execute();

        $this->assertSame('*/*/edit', $this->redirect['path']);
        $this->assertSame(['slider_id' => 4], $this->redirect['params']);
    }

    public function testSuccessfulSaveClearsPersistedValues(): void
    {
        $persistor = $this->createMock(DataPersistorInterface::class);
        $persistor->expects($this->never())->method('set');
        $persistor->expects($this->once())->method('clear')->with('panth_banner_slider');

        $this->controller(['title' => 'Home', 'identifier' => 'home', 'store_id' => '0'], [], null, null, $persistor)
            ->execute();

        $this->assertSame('0', $this->savedData['store_id']);
        $this->assertSame(['The slider has been saved.'], $this->messages['success']);
    }
}
