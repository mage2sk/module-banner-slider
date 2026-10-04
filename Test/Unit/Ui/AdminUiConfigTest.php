<?php
declare(strict_types=1);

namespace Panth\BannerSlider\Test\Unit\Ui;

use PHPUnit\Framework\TestCase;

class AdminUiConfigTest extends TestCase
{
    private function xml(string $relative): \SimpleXMLElement
    {
        $path = dirname(__DIR__, 3) . '/' . $relative;
        $this->assertTrue(is_file($path), $relative . ' is missing');
        $xml = simplexml_load_string((string)file_get_contents($path));
        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);
        return $xml;
    }

    public static function storeViewFields(): array
    {
        return [
            'slider form' => ['view/adminhtml/ui_component/panth_bannerslider_slider_form.xml', '//field[@name="store_id"]//options'],
            'slide form' => ['view/adminhtml/ui_component/panth_bannerslider_slide_form.xml', '//field[@name="store_id"]//options'],
            'slide grid' => ['view/adminhtml/ui_component/panth_bannerslider_slide_listing.xml', '//column[@name="store_id"]//options'],
            'slider grid' => ['view/adminhtml/ui_component/panth_bannerslider_slider_listing.xml', '//column[@name="store_id"]//options'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('storeViewFields')]
    public function testStoreViewOptionsIncludeAllStoreViews(string $file, string $xpath): void
    {
        $nodes = $this->xml($file)->xpath($xpath);
        $this->assertCount(1, $nodes);
        $this->assertSame('Magento\Cms\Ui\Component\Listing\Column\Cms\Options', (string)$nodes[0]['class']);
    }

    public function testSliderGridHasMassDeleteAndKeywordProvider(): void
    {
        $xml = $this->xml('view/adminhtml/ui_component/panth_bannerslider_slider_listing.xml');
        $url = $xml->xpath('//massaction/action[@name="delete"]//url');
        $this->assertSame('panth_bannerslider/slider/massDelete', (string)$url[0]['path']);
        $provider = $xml->xpath('//dataSource/dataProvider');
        $this->assertSame('Panth\BannerSlider\Ui\SliderGridDataProvider', (string)$provider[0]['class']);
    }

    public function testSlideGridUsesKeywordProvider(): void
    {
        $provider = $this->xml('view/adminhtml/ui_component/panth_bannerslider_slide_listing.xml')
            ->xpath('//dataSource/dataProvider');
        $this->assertSame('Panth\BannerSlider\Ui\SlideGridDataProvider', (string)$provider[0]['class']);
    }

    public function testKeywordProvidersAreWiredToTheKeywordFilter(): void
    {
        $xml = $this->xml('etc/adminhtml/di.xml');
        foreach (['Slider', 'Slide'] as $entity) {
            $provider = $xml->xpath('//virtualType[@name="Panth\BannerSlider\Ui\\' . $entity . 'GridDataProvider"]');
            $this->assertCount(1, $provider);
            $pool = $xml->xpath(
                '//virtualType[@name="Panth\BannerSlider\Ui\\' . $entity . 'FilterPool"]//item[@name="fulltext"]'
            );
            $this->assertSame('Panth\BannerSlider\Ui\\' . $entity . 'KeywordFilter', trim((string)$pool[0]));
            $filter = $xml->xpath('//virtualType[@name="Panth\BannerSlider\Ui\\' . $entity . 'KeywordFilter"]');
            $this->assertSame('Panth\BannerSlider\Ui\DataProvider\KeywordFilter', (string)$filter[0]['type']);
        }
    }

    public function testSaveControllersReceiveTheDataPersistor(): void
    {
        $xml = $this->xml('etc/adminhtml/di.xml');
        foreach ([
            'Panth\BannerSlider\Controller\Adminhtml\Slide\Save',
            'Panth\BannerSlider\Controller\Adminhtml\Slider\Save',
            'Panth\BannerSlider\Ui\DataProvider\SliderFormDataProvider',
        ] as $type) {
            $arg = $xml->xpath('//type[@name="' . $type . '"]//argument[@name="dataPersistor"]');
            $this->assertCount(1, $arg, $type);
        }
    }

    public function testSliderIdentifierHasFormatValidation(): void
    {
        $rule = $this->xml('view/adminhtml/ui_component/panth_bannerslider_slider_form.xml')
            ->xpath('//field[@name="identifier"]//rule[@name="validate-xml-identifier"]');
        $this->assertCount(1, $rule);
    }
}
