<?php
/**
 * Backend Model for Banner Slides Data
 * Handles data serialization when saving configuration
 *
 * @category  Panth
 * @package   Panth_BannerSlider
 * @author    Panth
 * @copyright Copyright (c) Panth
 */

declare(strict_types=1);

namespace Panth\BannerSlider\Model\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;

class SlidesData extends Value
{
    /**
     * @var Json
     */
    private Json $json;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param Json $json
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        Json $json,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->json = $json;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Process data before save
     *
     * @return $this
     */
    public function beforeSave(): self
    {
        $value = $this->getValue();

        if (is_array($value)) {
            $value = array_filter($value, function ($row) {
                return !empty($row) && is_array($row);
            });
            $this->setValue($this->json->serialize(array_values($value)));
        }

        return parent::beforeSave();
    }

    /**
     * Process data after load
     *
     * @return $this
     */
    protected function _afterLoad(): self
    {
        $value = $this->getValue();
        if ($value && is_string($value)) {
            try {
                $decoded = $this->json->unserialize($value);
                if (!is_array($decoded)) {
                    $decoded = [];
                }
                $this->setValue($decoded);
            } catch (\InvalidArgumentException $e) {
                $this->setValue([]);
            }
        }
        return parent::_afterLoad();
    }
}
