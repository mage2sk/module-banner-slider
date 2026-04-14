<?php
/**
 * Banner Slides Dynamic Rows Configuration
 *
 * @category  Panth
 * @package   Panth_BannerSlider
 * @author    Panth
 * @copyright Copyright (c) Panth
 */

declare(strict_types=1);

namespace Panth\BannerSlider\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Data\Form\Element\Factory as ElementFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;
use Panth\BannerSlider\Block\Adminhtml\System\Config\Field\DatePicker;
use Panth\BannerSlider\Block\Adminhtml\System\Config\Field\Enabled;
use Panth\BannerSlider\Block\Adminhtml\System\Config\Field\Image;
use Panth\BannerSlider\Block\Adminhtml\System\Config\Field\Wysiwyg;

class Slides extends AbstractFieldArray
{
    /**
     * @var ElementFactory
     */
    private ElementFactory $elementFactory;

    /**
     * @var Json
     */
    private Json $json;

    /**
     * @var Image|null
     */
    private ?Image $imageRenderer = null;

    /**
     * @var Wysiwyg|null
     */
    private ?Wysiwyg $wysiwygRenderer = null;

    /**
     * @var DatePicker|null
     */
    private ?DatePicker $dateRenderer = null;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param ElementFactory $elementFactory
     * @param Json $json
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        ElementFactory $elementFactory,
        Json $json,
        array $data = []
    ) {
        $this->elementFactory = $elementFactory;
        $this->json = $json;
        parent::__construct($context, $data);
    }

    /**
     * Set template for custom layout
     *
     * @return void
     */
    protected function _construct(): void
    {
        parent::_construct();
        $this->setTemplate('Panth_BannerSlider::system/config/slides.phtml');
    }

    /**
     * Prepare rendering the new field by adding all the needed columns
     *
     * @return void
     */
    protected function _prepareToRender(): void
    {
        $this->addColumn('identifier', [
            'label' => __('Identifier'),
            'class' => 'required-entry',
            'style' => 'width:150px'
        ]);

        $this->addColumn('enabled', [
            'label' => __('Enabled'),
            'class' => 'required-entry',
            'renderer' => $this->getEnabledRenderer()
        ]);

        $this->addColumn('desktop_image', [
            'label' => __('Desktop Image'),
            'renderer' => $this->getImageRenderer()
        ]);

        $this->addColumn('tablet_image', [
            'label' => __('Tablet Image'),
            'renderer' => $this->getImageRenderer()
        ]);

        $this->addColumn('mobile_image', [
            'label' => __('Mobile Image'),
            'renderer' => $this->getImageRenderer()
        ]);

        $this->addColumn('content_html', [
            'label' => __('Content HTML'),
            'renderer' => $this->getWysiwygRenderer()
        ]);

        $this->addColumn('link_url', [
            'label' => __('Link URL'),
            'style' => 'width:150px'
        ]);

        $this->addColumn('date_from', [
            'label' => __('Start Date'),
            'renderer' => $this->getDateRenderer()
        ]);

        $this->addColumn('date_to', [
            'label' => __('End Date'),
            'renderer' => $this->getDateRenderer()
        ]);

        $this->addColumn('sort_order', [
            'label' => __('Sort Order'),
            'class' => 'validate-number',
            'style' => 'width:60px'
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Banner Slide');
    }

    /**
     * Get enabled dropdown renderer
     *
     * @return Enabled
     */
    private function getEnabledRenderer(): Enabled
    {
        return $this->getLayout()->createBlock(
            Enabled::class,
            '',
            ['data' => ['is_render_to_js_template' => true]]
        );
    }

    /**
     * Get image upload renderer
     *
     * @return Image
     */
    private function getImageRenderer(): Image
    {
        if (!$this->imageRenderer) {
            $this->imageRenderer = $this->getLayout()->createBlock(
                Image::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->imageRenderer;
    }

    /**
     * Get WYSIWYG editor renderer
     *
     * @return Wysiwyg
     */
    private function getWysiwygRenderer(): Wysiwyg
    {
        if (!$this->wysiwygRenderer) {
            $this->wysiwygRenderer = $this->getLayout()->createBlock(
                Wysiwyg::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->wysiwygRenderer;
    }

    /**
     * Get date picker renderer
     *
     * @return DatePicker
     */
    private function getDateRenderer(): DatePicker
    {
        if (!$this->dateRenderer) {
            $this->dateRenderer = $this->getLayout()->createBlock(
                DatePicker::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->dateRenderer;
    }

    /**
     * Prepare existing row data object
     *
     * @param DataObject $row
     * @return void
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];

        $enabled = $row->getData('enabled');
        if ($enabled !== null) {
            $options['option_' . $this->getEnabledRenderer()->calcOptionHash($enabled)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * Get columns as JSON for JavaScript
     *
     * @return string
     */
    public function getColumnsJson(): string
    {
        $columns = [];
        foreach ($this->_columns as $columnName => $column) {
            $columns[$columnName] = [
                'label' => (string)($column['label'] ?? ''),
                'class' => $column['class'] ?? '',
                'style' => $column['style'] ?? '',
                'renderer' => isset($column['renderer'])
            ];
        }
        return $this->json->serialize($columns);
    }

    /**
     * Get value as JSON for JavaScript
     *
     * @return string
     */
    public function getValueJson(): string
    {
        $value = $this->getElement()->getValue();
        if (is_array($value)) {
            return $this->json->serialize($value);
        }
        return '[]';
    }

    /**
     * Get media base URL
     *
     * @return string
     */
    public function getMediaUrl(): string
    {
        return $this->_storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
    }

    /**
     * Get upload URL for AJAX
     *
     * @return string
     */
    public function getUploadUrl(): string
    {
        return $this->getUrl('panth_bannerslider/upload/image');
    }
}
