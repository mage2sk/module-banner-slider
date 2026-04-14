<?php
declare(strict_types=1);

/**
 * Image Upload Field Renderer
 *
 * @category  Panth
 * @package   Panth_BannerSlider
 * @author    Panth
 * @copyright Copyright (c) Panth
 */

namespace Panth\BannerSlider\Block\Adminhtml\System\Config\Field;

use Magento\Framework\View\Element\AbstractBlock;

class Image extends AbstractBlock
{
    /**
     * @var string
     */
    protected $_inputName = '';

    /**
     * @var string
     */
    protected $_inputId = '';

    /**
     * Set input name
     *
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        $this->_inputName = $value;
        return $this;
    }

    /**
     * Set input id
     *
     * @param string $value
     * @return $this
     */
    public function setInputId($value)
    {
        $this->_inputId = $value;
        return $this;
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        $columnName = $this->getColumnName();
        $inputName = $this->_inputName;
        $inputId = $this->_inputId;

        $html = '<div class="admin__field-control banner-slide-image-upload">';

        // Hidden input to store the image path
        $html .= '<input type="hidden"
                    name="' . $this->escapeHtml($inputName) . '"
                    id="' . $this->escapeHtml($inputId) . '"
                    value="<%- ' . $columnName . ' %>"
                    class="input-text admin__control-text banner-image-path" />';

        // Image preview container
        $html .= '<div class="banner-image-preview" style="display: none; margin-bottom: 10px;">';
        $html .= '<img src="" alt="Preview" style="max-width: 150px; max-height: 100px; border: 1px solid #ccc; padding: 5px;" />';
        $html .= '</div>';

        // Upload button
        $html .= '<button type="button"
                    class="action-default scalable banner-image-upload-btn"
                    data-input-id="' . $this->escapeHtml($inputId) . '"
                    style="margin-right: 5px;">
                    <span>' . __('Upload Image') . '</span>
                  </button>';

        // Delete button
        $html .= '<button type="button"
                    class="action-default scalable banner-image-delete-btn"
                    data-input-id="' . $this->escapeHtml($inputId) . '"
                    style="display: none;">
                    <span>' . __('Delete') . '</span>
                  </button>';

        // File input (hidden)
        $html .= '<input type="file"
                    accept="image/*"
                    style="display: none;"
                    class="banner-image-file-input"
                    data-input-id="' . $this->escapeHtml($inputId) . '" />';

        $html .= '</div>';

        return $html;
    }
}
