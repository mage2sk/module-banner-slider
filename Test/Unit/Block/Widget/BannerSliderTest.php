<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Test\Unit\Block\Widget;

use Magento\Cms\Model\Template\Filter;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Panth\BannerSlider\Block\Widget\BannerSlider;
use Panth\BannerSlider\Helper\Data as BannerHelper;
use Panth\Core\Helper\Theme as ThemeHelper;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class BannerSliderTest extends TestCase
{
    private function block(
        BannerHelper $helper,
        bool $isHyva = false,
        array $data = ['identifier' => 'home'],
        ?FilterProvider $filterProvider = null,
        ?LoggerInterface $logger = null
    ): BannerSlider {
        $store = $this->createStub(Store::class);
        $store->method('getId')->willReturn(4);
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $context = $this->createStub(Context::class);
        $context->method('getStoreManager')->willReturn($storeManager);
        $context->method('getLogger')->willReturn($logger ?? $this->createStub(LoggerInterface::class));

        $theme = $this->createStub(ThemeHelper::class);
        $theme->method('isHyva')->willReturn($isHyva);

        return new BannerSlider(
            $context,
            $helper,
            new Json(),
            $theme,
            $filterProvider ?? $this->createStub(FilterProvider::class),
            $data
        );
    }

    public function testTemplateIsLumaByDefault(): void
    {
        $block = $this->block($this->createStub(BannerHelper::class));
        $this->assertSame('Panth_BannerSlider::widget/banner_slider.phtml', $block->getTemplate());
    }

    public function testTemplateSwitchesToHyvaVariant(): void
    {
        $block = $this->block($this->createStub(BannerHelper::class), true);
        $this->assertSame('Panth_BannerSlider::widget/banner_slider_hyva.phtml', $block->getTemplate());
    }

    public function testCustomTemplateIsNotRemappedOnHyva(): void
    {
        $block = $this->block($this->createStub(BannerHelper::class), true);
        $block->setTemplate('Vendor_Theme::custom.phtml');
        $this->assertSame('Vendor_Theme::custom.phtml', $block->getTemplate());
    }

    public function testSlidesAreLoadedOnceAndCached(): void
    {
        $helper = $this->createMock(BannerHelper::class);
        $helper->expects($this->once())
            ->method('getSlidesByIdentifier')
            ->with('home')
            ->willReturn([['slide_id' => 1]]);

        $block = $this->block($helper);
        $this->assertSame([['slide_id' => 1]], $block->getSlides());
        $this->assertSame([['slide_id' => 1]], $block->getSlides());
        $this->assertTrue($block->canDisplay());
    }

    public function testCannotDisplayWithoutIdentifier(): void
    {
        $helper = $this->createStub(BannerHelper::class);
        $helper->method('getSlidesByIdentifier')->willReturn([['slide_id' => 1]]);
        $block = $this->block($helper, false, []);

        $this->assertSame('', $block->getIdentifier());
        $this->assertFalse($block->canDisplay());
    }

    public function testCannotDisplayWithoutSlides(): void
    {
        $helper = $this->createStub(BannerHelper::class);
        $helper->method('getSlidesByIdentifier')->willReturn([]);
        $this->assertFalse($this->block($helper)->canDisplay());
    }

    public function testSliderConfigJsonSerializesHelperConfig(): void
    {
        $helper = $this->createStub(BannerHelper::class);
        $helper->method('getSliderConfig')->willReturn(['autoplay' => false, 'effect' => 'slide']);
        $block = $this->block($helper);

        $this->assertSame(['autoplay' => false, 'effect' => 'slide'], $block->getSliderConfig());
        $this->assertSame('{"autoplay":false,"effect":"slide"}', $block->getSliderConfigJson());
        $this->assertSame($helper, $block->getHelper());
    }

    public function testUniqueIdIsPrefixedAndDiffersPerCall(): void
    {
        $block = $this->block($this->createStub(BannerHelper::class));
        $first = $block->getUniqueId();
        $this->assertStringStartsWith('banner_slider_home_', $first);
        $this->assertNotSame($first, $block->getUniqueId());
    }

    public function testContentHtmlEmptyForBlankContent(): void
    {
        $provider = $this->createMock(FilterProvider::class);
        $provider->expects($this->never())->method('getBlockFilter');
        $block = $this->block($this->createStub(BannerHelper::class), false, ['identifier' => 'x'], $provider);

        $this->assertSame('', $block->getContentHtml([]));
        $this->assertSame('', $block->getContentHtml(['content_html' => "  \n "]));
    }

    public function testContentHtmlIsFilteredWithStoreId(): void
    {
        $filter = $this->createMock(Filter::class);
        $filter->expects($this->once())->method('setStoreId')->with(4)->willReturnSelf();
        $filter->expects($this->once())->method('filter')->with('{{var x}}')->willReturn('<p>done</p>');
        $provider = $this->createStub(FilterProvider::class);
        $provider->method('getBlockFilter')->willReturn($filter);

        $block = $this->block($this->createStub(BannerHelper::class), false, ['identifier' => 'x'], $provider);
        $this->assertSame('<p>done</p>', $block->getContentHtml(['content_html' => '{{var x}}']));
    }

    public function testContentHtmlFallsBackToRawContentOnFilterError(): void
    {
        $filter = $this->createStub(Filter::class);
        $filter->method('setStoreId')->willReturnSelf();
        $filter->method('filter')->willThrowException(new \Exception('boom'));
        $provider = $this->createStub(FilterProvider::class);
        $provider->method('getBlockFilter')->willReturn($filter);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with($this->stringContains('boom'));

        $block = $this->block($this->createStub(BannerHelper::class), false, ['identifier' => 'x'], $provider, $logger);
        $this->assertSame('<b>raw</b>', $block->getContentHtml(['content_html' => '<b>raw</b>']));
    }

    public function testIdentitiesIncludeSlideAndSliderTagsWithoutDuplicates(): void
    {
        $helper = $this->createStub(BannerHelper::class);
        $helper->method('getSlidesByIdentifier')->willReturn([
            ['slide_id' => '1', 'slider_id' => '9'],
            ['slide_id' => '2', 'slider_id' => '9'],
            ['slide_id' => '', 'slider_id' => ''],
        ]);

        $this->assertSame([
            'panth_banner_slider',
            'panth_banner_slide_1',
            'panth_banner_slider_9',
            'panth_banner_slide_2',
        ], $this->block($helper)->getIdentities());
    }

    public function testMageInitJsonCarriesConfigAndPauseLabels(): void
    {
        $helper = $this->createStub(BannerHelper::class);
        $helper->method('getSliderConfig')->willReturn(['autoplay' => true, 'transitionSpeed' => 700]);

        $decoded = json_decode($this->block($helper)->getMageInitJson(), true);

        $this->assertSame(
            [
                'Panth_BannerSlider/js/banner-slider' => [
                    'autoplay' => true,
                    'transitionSpeed' => 700,
                    'labelPause' => 'Pause slideshow',
                    'labelPlay' => 'Play slideshow',
                ],
            ],
            $decoded
        );
    }

    public function testIdentitiesWithoutSlidesContainOnlyGenericTag(): void
    {
        $helper = $this->createStub(BannerHelper::class);
        $helper->method('getSlidesByIdentifier')->willReturn([]);
        $this->assertSame(['panth_banner_slider'], $this->block($helper)->getIdentities());
    }
}
