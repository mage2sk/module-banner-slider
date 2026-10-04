<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Test\Unit\Controller\Adminhtml\SampleData;

use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Module\Dir\Reader as ModuleDirReader;
use Magento\Framework\Serialize\Serializer\Json;
use Panth\BannerSlider\Controller\Adminhtml\SampleData\Install;
use Panth\BannerSlider\Model\ResourceModel\Slide as SlideResource;
use Panth\BannerSlider\Model\ResourceModel\Slider as SliderResource;
use Panth\BannerSlider\Model\Slide;
use Panth\BannerSlider\Model\SlideFactory;
use Panth\BannerSlider\Model\Slider;
use Panth\BannerSlider\Model\SliderFactory;
use Panth\BannerSlider\Test\Unit\Controller\Adminhtml\AbstractControllerTestCase;
use Psr\Log\LoggerInterface;

class InstallTest extends AbstractControllerTestCase
{
    private string $tmp;
    private array $savedSliders = [];
    private array $savedSlides = [];

    protected function setUp(): void
    {
        $this->tmp = sys_get_temp_dir() . '/panth_bs_install_' . uniqid('', true);
        mkdir($this->tmp . '/module/Setup/SampleData/images', 0777, true);
        mkdir($this->tmp . '/media/bannerslider', 0777, true);
    }

    protected function tearDown(): void
    {
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->tmp, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->tmp);
    }

    private function writeJson(string $name, array $data): void
    {
        file_put_contents($this->tmp . '/module/Setup/SampleData/' . $name, json_encode($data));
    }

    private function controller(array $existingIdentifiers = [], ?\Exception $saveError = null): Install
    {
        $context = $this->buildContext();

        $sliderFactory = $this->createStub(SliderFactory::class);
        $sliderFactory->method('create')->willReturnCallback(fn() => $this->model(Slider::class, 'slider_id'));
        $slideFactory = $this->createStub(SlideFactory::class);
        $slideFactory->method('create')->willReturnCallback(fn() => $this->model(Slide::class, 'slide_id'));

        $sliderResource = $this->createStub(SliderResource::class);
        $sliderResource->method('load')->willReturnCallback(
            function ($object, $value, $field = null) use ($existingIdentifiers) {
                if ($field === 'identifier' && in_array($value, $existingIdentifiers, true)) {
                    $object->setId(99);
                }
                return $this->createStub(SliderResource::class);
            }
        );
        $sliderResource->method('save')->willReturnCallback(
            function ($object) use ($saveError) {
                if ($saveError) {
                    throw $saveError;
                }
                $object->setId(100 + count($this->savedSliders));
                $this->savedSliders[] = $object->getData();
                return $this->createStub(SliderResource::class);
            }
        );
        $slideResource = $this->createStub(SlideResource::class);
        $slideResource->method('save')->willReturnCallback(
            function ($object) {
                $this->savedSlides[] = $object->getData();
                return $this->createStub(SlideResource::class);
            }
        );

        $media = $this->createStub(WriteInterface::class);
        $media->method('getAbsolutePath')->willReturnCallback(fn($path) => $this->tmp . '/media/' . $path);
        $filesystem = $this->createStub(Filesystem::class);
        $filesystem->method('getDirectoryWrite')->willReturn($media);

        $reader = $this->createStub(ModuleDirReader::class);
        $reader->method('getModuleDir')->willReturn($this->tmp . '/module');

        return new Install(
            $context,
            $sliderFactory,
            $slideFactory,
            $sliderResource,
            $slideResource,
            $filesystem,
            $reader,
            new Json(),
            $this->createStub(LoggerInterface::class)
        );
    }

    public function testWarnsWhenNoJsonFilesExist(): void
    {
        $this->controller()->execute();

        $this->assertCount(1, $this->messages['warning']);
        $this->assertStringContainsString('No sample data JSON files found in', $this->messages['warning'][0]);
        $this->assertSame('*/slider/', $this->redirect['path']);
    }

    public function testInstallsSlidersSlidesAndCopiesImages(): void
    {
        file_put_contents($this->tmp . '/module/Setup/SampleData/images/hero.jpg', 'img');
        $this->writeJson('home.json', [
            'slider' => ['identifier' => 'home', 'title' => 'Home'],
            'slides' => [
                ['title' => 'One', 'desktop_image' => 'hero.jpg', 'mobile_image' => 'hero_m.jpg'],
                ['title' => 'Two'],
            ],
        ]);

        $this->controller()->execute();

        $this->assertSame([['identifier' => 'home', 'title' => 'Home', 'slider_id' => 100]], $this->savedSliders);
        $this->assertCount(2, $this->savedSlides);
        $this->assertSame(100, $this->savedSlides[0]['slider_id']);
        $this->assertSame('bannerslider/hero.jpg', $this->savedSlides[0]['desktop_image']);
        $this->assertSame('bannerslider/hero_m.jpg', $this->savedSlides[0]['mobile_image']);
        $this->assertArrayNotHasKey('desktop_image', $this->savedSlides[1]);
        $this->assertFileExists($this->tmp . '/media/bannerslider/hero.jpg');
        $this->assertSame(['Sample data installed: 1 slider(s) and 2 banner(s) created.'], $this->messages['success']);
        $this->assertSame('*/slider/', $this->redirect['path']);
    }

    public function testExistingImagesAreNotOverwritten(): void
    {
        file_put_contents($this->tmp . '/module/Setup/SampleData/images/hero.jpg', 'new');
        file_put_contents($this->tmp . '/media/bannerslider/hero.jpg', 'customised');
        $this->writeJson('home.json', ['slider' => ['identifier' => 'home']]);

        $this->controller()->execute();

        $this->assertSame('customised', file_get_contents($this->tmp . '/media/bannerslider/hero.jpg'));
    }

    public function testExistingSlidersAreSkipped(): void
    {
        $this->writeJson('a.json', ['slider' => ['identifier' => 'home'], 'slides' => [['title' => 'x']]]);
        $this->writeJson('b.json', ['slider' => ['identifier' => 'promo']]);

        $this->controller(['home'])->execute();

        $this->assertCount(1, $this->savedSliders);
        $this->assertSame('promo', $this->savedSliders[0]['identifier']);
        $this->assertSame([], $this->savedSlides);
        $this->assertSame(['Skipped 1 slider(s) that already exist: home'], $this->messages['notice']);
        $this->assertSame(['Sample data installed: 1 slider(s) and 0 banner(s) created.'], $this->messages['success']);
    }

    public function testFilesWithoutSliderKeyAreIgnored(): void
    {
        $this->writeJson('bad.json', ['slides' => [['title' => 'orphan']]]);

        $this->controller()->execute();

        $this->assertSame([], $this->savedSliders);
        $this->assertSame([], $this->savedSlides);
        $this->assertSame(['No sample data JSON files found.'], $this->messages['warning']);
    }

    public function testSaveFailureIsReportedAsError(): void
    {
        $this->writeJson('home.json', ['slider' => ['identifier' => 'home']]);

        $this->controller([], new \Exception('table missing'))->execute();

        $this->assertSame(['Error installing sample data: table missing'], $this->messages['error']);
        $this->assertSame([], $this->messages['success']);
        $this->assertSame('*/slider/', $this->redirect['path']);
    }
}
