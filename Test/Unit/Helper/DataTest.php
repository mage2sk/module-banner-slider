<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Test\Unit\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Panth\BannerSlider\Helper\Data;
use Panth\BannerSlider\Model\ResourceModel\Slide\Collection as SlideCollection;
use Panth\BannerSlider\Model\ResourceModel\Slide\CollectionFactory as SlideCollectionFactory;
use Panth\BannerSlider\Model\ResourceModel\Slider\Collection as SliderCollection;
use Panth\BannerSlider\Model\ResourceModel\Slider\CollectionFactory as SliderCollectionFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
{
    private array $sliderFilters = [];
    private array $slideFilters = [];

    private function helper(?DataObject $firstSlider = null, array $slides = []): Data
    {
        $store = $this->createStub(Store::class);
        $store->method('getId')->willReturn(3);
        $store->method('getBaseUrl')->willReturnCallback(
            static fn($type = null) => $type === UrlInterface::URL_TYPE_MEDIA
                ? 'https://shop.test/media/'
                : 'https://shop.test/'
        );
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $sliderCollection = $this->createStub(SliderCollection::class);
        $sliderCollection->method('addFieldToFilter')->willReturnCallback(
            function ($field, $cond = null) use (&$sliderCollection) {
                $this->sliderFilters[] = [$field, $cond];
                return $sliderCollection;
            }
        );
        $sliderCollection->method('setOrder')->willReturnSelf();
        $sliderCollection->method('setPageSize')->willReturnSelf();
        $sliderCollection->method('getFirstItem')->willReturn($firstSlider ?? new DataObject());
        $sliderFactory = $this->createStub(SliderCollectionFactory::class);
        $sliderFactory->method('create')->willReturn($sliderCollection);

        $slideCollection = $this->createStub(SlideCollection::class);
        $slideCollection->method('addFieldToFilter')->willReturnCallback(
            function ($field, $cond = null) use (&$slideCollection) {
                $this->slideFilters[] = [$field, $cond];
                return $slideCollection;
            }
        );
        $slideCollection->method('setOrder')->willReturnSelf();
        $slideCollection->method('getData')->willReturn($slides);
        $slideFactory = $this->createStub(SlideCollectionFactory::class);
        $slideFactory->method('create')->willReturn($slideCollection);

        $timezone = $this->createStub(TimezoneInterface::class);
        $timezone->method('date')->willReturn(new \DateTime('2026-05-17 10:00:00'));

        return new Data(
            $this->createStub(Context::class),
            $storeManager,
            $sliderFactory,
            $slideFactory,
            $timezone
        );
    }

    private function slider(array $data): DataObject
    {
        return new DataObject($data + ['id' => $data['slider_id'] ?? null]);
    }

    public static function linkProvider(): array
    {
        return [
            'https' => ['https://example.com/page', true],
            'http' => ['http://example.com', true],
            'mailto' => ['mailto:info@example.com', true],
            'tel' => ['tel:+441234567', true],
            'relative' => ['/sale/shoes.html', true],
            'relative no slash' => ['sale.html?x=1', true],
            'anchor' => ['#section', true],
            'uppercase scheme' => ['HTTPS://example.com', true],
            'javascript' => ['javascript:alert(1)', false],
            'javascript mixed case' => ['JaVaScRiPt:alert(1)', false],
            'javascript with whitespace' => [" java\tscript:alert(1)", false],
            'javascript html entity' => ['javascript&colon;alert(1)', false],
            'javascript percent encoded' => ['%6Aavascript:alert(1)', false],
            'data uri' => ['data:text/html;base64,PHNjcmlwdD4=', false],
            'vbscript' => ['vbscript:msgbox', false],
            'backslash' => ['\\\\evil.com', false],
            'empty' => ['', false],
            'only control chars' => ["\x00\x01 ", false],
        ];
    }

    #[DataProvider('linkProvider')]
    public function testIsAllowedLinkUrl(string $url, bool $expected): void
    {
        $this->assertSame($expected, $this->helper()->isAllowedLinkUrl($url));
    }

    public function testGetLinkUrlReturnsTrimmedSafeUrl(): void
    {
        $this->assertSame('https://a.test/x', $this->helper()->getLinkUrl(['link_url' => '  https://a.test/x ']));
    }

    public function testGetLinkUrlRejectsEmptyHashAndUnsafe(): void
    {
        $helper = $this->helper();
        $this->assertSame('', $helper->getLinkUrl([]));
        $this->assertSame('', $helper->getLinkUrl(['link_url' => '#']));
        $this->assertSame('', $helper->getLinkUrl(['link_url' => '   ']));
        $this->assertSame('', $helper->getLinkUrl(['link_url' => 'javascript:alert(1)']));
    }

    public function testGetLinkTargetOnlyAllowsBlank(): void
    {
        $helper = $this->helper();
        $this->assertSame('_blank', $helper->getLinkTarget(['link_target' => '_blank']));
        $this->assertSame('', $helper->getLinkTarget(['link_target' => '_self']));
        $this->assertSame('', $helper->getLinkTarget(['link_target' => '_parent']));
        $this->assertSame('', $helper->getLinkTarget([]));
    }

    public function testGetSliderByIdentifierReturnsNullForEmptyIdentifier(): void
    {
        $helper = $this->helper($this->slider(['slider_id' => 1]));
        $this->assertNull($helper->getSliderByIdentifier(''));
        $this->assertSame([], $this->sliderFilters);
    }

    public function testGetSliderByIdentifierFiltersByIdentifierActiveAndStore(): void
    {
        $helper = $this->helper($this->slider(['slider_id' => 7, 'identifier' => 'home']));

        $result = $helper->getSliderByIdentifier('home');

        $this->assertSame(7, $result['slider_id']);
        $this->assertSame('home', $result['identifier']);
        $this->assertContains(['identifier', 'home'], $this->sliderFilters);
        $this->assertContains(['is_active', 1], $this->sliderFilters);
        $this->assertContains(['store_id', ['in' => [0, 3]]], $this->sliderFilters);
    }

    public function testGetSliderByIdentifierReturnsNullWhenNotFound(): void
    {
        $this->assertNull($this->helper()->getSliderByIdentifier('missing'));
    }

    public function testGetSlidesBySliderIdAppliesDateAndStoreFilters(): void
    {
        $slides = [['slide_id' => 1], ['slide_id' => 2]];
        $helper = $this->helper(null, $slides);

        $this->assertSame($slides, $helper->getSlidesBySliderId(9));
        $this->assertContains(['slider_id', 9], $this->slideFilters);
        $this->assertContains(['is_active', 1], $this->slideFilters);
        $this->assertContains(['store_id', ['in' => [0, 3]]], $this->slideFilters);
        $this->assertContains(
            [['date_from', 'date_from'], [['null' => true], ['lteq' => '2026-05-17']]],
            $this->slideFilters
        );
        $this->assertContains(
            [['date_to', 'date_to'], [['null' => true], ['gteq' => '2026-05-17']]],
            $this->slideFilters
        );
    }

    public function testGetSlidesByIdentifierReturnsEmptyWithoutSlider(): void
    {
        $helper = $this->helper(null, [['slide_id' => 1]]);
        $this->assertSame([], $helper->getSlidesByIdentifier('nope'));
        $this->assertFalse($helper->isEnabled('nope'));
        $this->assertSame([], $this->slideFilters);
    }

    public function testGetSlidesByIdentifierUsesSliderId(): void
    {
        $helper = $this->helper($this->slider(['slider_id' => '5']), [['slide_id' => 11]]);
        $this->assertSame([['slide_id' => 11]], $helper->getSlidesByIdentifier('home'));
        $this->assertContains(['slider_id', 5], $this->slideFilters);
        $this->assertTrue($helper->isEnabled('home'));
    }

    public function testIsEnabledFalseWhenSliderHasNoSlides(): void
    {
        $this->assertFalse($this->helper($this->slider(['slider_id' => 5]), [])->isEnabled('home'));
    }

    public function testGetSliderConfigDefaultsWhenSliderMissing(): void
    {
        $this->assertSame([
            'autoplay' => true,
            'autoplaySpeed' => 5000,
            'transitionSpeed' => 600,
            'effect' => 'fade',
            'loop' => true,
            'showArrows' => true,
            'showDots' => true,
            'pauseOnHover' => true,
        ], $this->helper()->getSliderConfig('missing'));
    }

    public function testGetSliderConfigMapsAndCastsSliderValues(): void
    {
        $helper = $this->helper($this->slider([
            'slider_id' => 2,
            'autoplay' => '0',
            'autoplay_speed' => '3000',
            'transition_speed' => '250',
            'effect' => 'slide',
            'is_loop' => '0',
            'show_arrows' => '0',
            'show_dots' => '1',
            'pause_on_hover' => '0',
        ]));

        $this->assertSame([
            'autoplay' => false,
            'autoplaySpeed' => 3000,
            'transitionSpeed' => 250,
            'effect' => 'slide',
            'loop' => false,
            'showArrows' => false,
            'showDots' => true,
            'pauseOnHover' => false,
        ], $helper->getSliderConfig('home'));
    }

    public function testGetSliderConfigUnknownEffectFallsBackToFadeAndDefaults(): void
    {
        $config = $this->helper($this->slider(['slider_id' => 2, 'effect' => 'zoom']))->getSliderConfig('home');
        $this->assertSame('fade', $config['effect']);
        $this->assertTrue($config['autoplay']);
        $this->assertSame(5000, $config['autoplaySpeed']);
        $this->assertSame(600, $config['transitionSpeed']);
    }

    public function testGetImageUrl(): void
    {
        $helper = $this->helper();
        $this->assertSame('', $helper->getImageUrl(''));
        $this->assertSame('https://shop.test/media/bannerslider/a.jpg', $helper->getImageUrl('/bannerslider/a.jpg'));
        $this->assertSame('https://shop.test/media/bannerslider/a.jpg', $helper->getImageUrl('bannerslider/a.jpg'));
    }

    public function testGetResponsiveImagesUsesAllThreeWhenSet(): void
    {
        $this->assertSame([
            'desktop' => 'https://shop.test/media/d.jpg',
            'tablet' => 'https://shop.test/media/t.jpg',
            'mobile' => 'https://shop.test/media/m.jpg',
        ], $this->helper()->getResponsiveImages([
            'desktop_image' => 'd.jpg',
            'tablet_image' => 't.jpg',
            'mobile_image' => 'm.jpg',
        ]));
    }

    public function testGetResponsiveImagesFallsBackToDesktop(): void
    {
        $this->assertSame([
            'desktop' => 'https://shop.test/media/d.jpg',
            'tablet' => 'https://shop.test/media/d.jpg',
            'mobile' => 'https://shop.test/media/d.jpg',
        ], $this->helper()->getResponsiveImages(['desktop_image' => 'd.jpg']));
    }

    public function testGetResponsiveImagesTabletOnlyFillsEverySize(): void
    {
        $images = $this->helper()->getResponsiveImages(['tablet_image' => 't.jpg']);
        $this->assertSame('https://shop.test/media/t.jpg', $images['desktop']);
        $this->assertSame('https://shop.test/media/t.jpg', $images['tablet']);
        $this->assertSame('https://shop.test/media/t.jpg', $images['mobile']);
    }

    public function testGetResponsiveImagesMobileOnlyFillsEverySize(): void
    {
        $this->assertSame(
            [
                'desktop' => 'https://shop.test/media/m.jpg',
                'tablet' => 'https://shop.test/media/m.jpg',
                'mobile' => 'https://shop.test/media/m.jpg',
            ],
            $this->helper()->getResponsiveImages(['mobile_image' => 'm.jpg'])
        );
    }

    public function testGetResponsiveImagesDesktopAndMobileKeepTabletOnDesktop(): void
    {
        $images = $this->helper()->getResponsiveImages(['desktop_image' => 'd.jpg', 'mobile_image' => 'm.jpg']);
        $this->assertSame('https://shop.test/media/d.jpg', $images['tablet']);
        $this->assertSame('https://shop.test/media/m.jpg', $images['mobile']);
    }

    public function testGetResponsiveImagesEmptySlide(): void
    {
        $this->assertSame(
            ['desktop' => '', 'tablet' => '', 'mobile' => ''],
            $this->helper()->getResponsiveImages([])
        );
    }
}
