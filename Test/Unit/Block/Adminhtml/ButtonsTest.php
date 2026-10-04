<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Test\Unit\Block\Adminhtml;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Panth\BannerSlider\Block\Adminhtml\Slide\Edit\DeleteButton as SlideDeleteButton;
use Panth\BannerSlider\Block\Adminhtml\Slide\Edit\SaveAndContinueButton as SlideSaveAndContinue;
use Panth\BannerSlider\Block\Adminhtml\Slide\Edit\SaveButton as SlideSave;
use Panth\BannerSlider\Block\Adminhtml\Slider\Edit\DeleteButton as SliderDeleteButton;
use Panth\BannerSlider\Block\Adminhtml\Slider\Edit\SaveAndContinueButton as SliderSaveAndContinue;
use Panth\BannerSlider\Block\Adminhtml\Slider\Edit\SaveButton as SliderSave;
use Panth\BannerSlider\Block\Adminhtml\Slider\InstallSampleDataButton;
use PHPUnit\Framework\TestCase;

class ButtonsTest extends TestCase
{
    private function context(array $params = [], bool $allowed = true): Context
    {
        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')->willReturnCallback(static fn($k) => $params[$k] ?? null);
        $url = $this->createStub(UrlInterface::class);
        $url->method('getUrl')->willReturnCallback(
            static fn($route, $p = []) => 'https://admin.test/' . $route . ($p ? '/' . http_build_query($p) : '')
        );
        $auth = $this->createStub(AuthorizationInterface::class);
        $auth->method('isAllowed')->willReturn($allowed);

        $context = $this->createStub(Context::class);
        $context->method('getRequest')->willReturn($request);
        $context->method('getUrlBuilder')->willReturn($url);
        $context->method('getAuthorization')->willReturn($auth);
        return $context;
    }

    public function testSlideDeleteButtonHiddenForNewSlide(): void
    {
        $this->assertSame([], (new SlideDeleteButton($this->context()))->getButtonData());
        $this->assertSame([], (new SlideDeleteButton($this->context(['slide_id' => 'abc'])))->getButtonData());
    }

    public function testSlideDeleteButtonPointsToDeleteUrl(): void
    {
        $data = (new SlideDeleteButton($this->context(['slide_id' => '7'])))->getButtonData();
        $this->assertSame('delete', $data['class']);
        $this->assertStringContainsString('https://admin.test/*/*/delete/slide_id=7', $data['on_click']);
        $this->assertStringStartsWith('deleteConfirm(', $data['on_click']);
    }

    public function testSliderDeleteButton(): void
    {
        $this->assertSame([], (new SliderDeleteButton($this->context()))->getButtonData());
        $data = (new SliderDeleteButton($this->context(['slider_id' => 3])))->getButtonData();
        $this->assertStringContainsString('https://admin.test/*/*/delete/slider_id=3', $data['on_click']);
        $this->assertSame(20, $data['sort_order']);
    }

    public function testSaveButtonsTriggerExpectedEvents(): void
    {
        foreach ([new SlideSave(), new SliderSave()] as $button) {
            $data = $button->getButtonData();
            $this->assertSame('save', $data['data_attribute']['mage-init']['button']['event']);
            $this->assertStringContainsString('primary', $data['class']);
        }
        foreach ([new SlideSaveAndContinue(), new SliderSaveAndContinue()] as $button) {
            $data = $button->getButtonData();
            $this->assertSame('saveAndContinueEdit', $data['data_attribute']['mage-init']['button']['event']);
        }
    }

    public function testInstallSampleDataButtonHiddenWithoutPermission(): void
    {
        $button = new InstallSampleDataButton($this->context([], false), $this->createStub(Escaper::class));
        $this->assertSame([], $button->getButtonData());
    }

    public function testInstallSampleDataButtonEscapesJs(): void
    {
        $escaper = $this->createStub(Escaper::class);
        $escaper->method('escapeJs')->willReturnCallback(static fn($v) => 'ESC[' . $v . ']');

        $data = (new InstallSampleDataButton($this->context(), $escaper))->getButtonData();

        $this->assertSame('Install Sample Data', (string)$data['label']);
        $this->assertStringContainsString('ESC[https://admin.test/panth_bannerslider/sampleData/install]', $data['on_click']);
        $this->assertStringContainsString('ESC[Install the sample sliders', $data['on_click']);
    }
}
