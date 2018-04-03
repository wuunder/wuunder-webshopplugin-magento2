<?php

namespace Wuunder\Wuunderconnector\Model;

// use Magento\Framework\Model\AbstractModel;

class WuunderShipment extends \Magento\Framework\Model\AbstractModel
{
    /**
     * CMS page cache tag.
     */
    const CACHE_TAG = 'wuunder_wuunderconnector_wuundershipment';

    /**
     * @var string
     */
    protected $_cacheTag = 'wuunder_wuunderconnector_wuundershipment';

    /**
     * Prefix of model events names.
     *
     * @var string
     */
    protected $_eventPrefix = 'wuunder_wuunderconnector_wuundershipment';

    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        // $this->_init('Wuunder\Wuunderconnector\Model\ResourceModel\WuunderShipment');
        $this->_init(\Wuunder\Wuunderconnector\Model\ResourceModel\WuunderShipment::class);
    }

}
