<?php
declare(strict_types=1);

/**
 * Enabled Field Renderer
 *
 * @category  Panth
 * @package   Panth_BannerSlider
 * @author    Panth
 * @copyright Copyright (c) Panth
 */

namespace Panth\BannerSlider\Block\Adminhtml\System\Config\Field;

use Magento\Framework\View\Element\Html\Select;

class Enabled extends Select
{
    /**
     * Set "name" for <select> element
     *
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * Set "id" for <select> element
     *
     * @param string $value
     * @return $this
     */
    public function setInputId($value)
    {
        return $this->setId($value);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->getOptions()) {
            $this->setOptions([
                ['label' => __('Yes'), 'value' => '1'],
                ['label' => __('No'), 'value' => '0']
            ]);
        }
        return parent::_toHtml();
    }
}
