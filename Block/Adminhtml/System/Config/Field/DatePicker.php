<?php
declare(strict_types=1);

/**
 * Date Picker Field Renderer
 *
 * @category  Panth
 * @package   Panth_BannerSlider
 * @author    Panth
 * @copyright Copyright (c) Panth
 */

namespace Panth\BannerSlider\Block\Adminhtml\System\Config\Field;

use Magento\Framework\View\Element\AbstractBlock;

class DatePicker extends AbstractBlock
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

        $html = '<div class="admin__field-control banner-slide-date-picker">';

        $html .= '<input type="date"
                    name="' . $this->escapeHtml($inputName) . '"
                    id="' . $this->escapeHtml($inputId) . '"
                    value="<%- ' . $columnName . ' %>"
                    class="input-text admin__control-text banner-date-input"
                    style="width: 150px;" />';

        $html .= '</div>';

        return $html;
    }
}
