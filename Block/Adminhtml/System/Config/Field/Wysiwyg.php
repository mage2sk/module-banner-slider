<?php
declare(strict_types=1);

/**
 * WYSIWYG Editor Field Renderer
 *
 * @category  Panth
 * @package   Panth_BannerSlider
 * @author    Panth
 * @copyright Copyright (c) Panth
 */

namespace Panth\BannerSlider\Block\Adminhtml\System\Config\Field;

use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\Data\Form\Element\Factory;

class Wysiwyg extends AbstractBlock
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
     * @var Factory
     */
    protected $_elementFactory;

    /**
     * @param \Magento\Framework\View\Element\Context $context
     * @param Factory $elementFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Context $context,
        Factory $elementFactory,
        array $data = []
    ) {
        $this->_elementFactory = $elementFactory;
        parent::__construct($context, $data);
    }

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

        $html = '<div class="admin__field-control banner-slide-wysiwyg-container" style="margin-top: 10px;">';

        // Textarea that will be enhanced with WYSIWYG
        $html .= '<textarea
                    name="' . $this->escapeHtml($inputName) . '"
                    id="' . $this->escapeHtml($inputId) . '"
                    class="admin__control-textarea wysiwyg-editor"
                    rows="8"
                    style="width: 100%;"><%- ' . $columnName . ' %></textarea>';

        $html .= '</div>';

        return $html;
    }
}
