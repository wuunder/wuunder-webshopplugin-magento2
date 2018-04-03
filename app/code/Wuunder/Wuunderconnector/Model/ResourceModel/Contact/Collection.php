<?php

namespace Wuunder\Wuunderconnector\Model\ResourceModel\Contact;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Contact Resource Model Collection
 */
class Collection extends AbstractCollection
{
    /**
     * Initialize resource collection
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Wuunder\Wuunderconnector\Model\WuunderShipment', 'Wuunder\Wuunderconnector\Model\ResourceModel\Contact');
    }
}

?>
