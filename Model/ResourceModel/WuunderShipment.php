<?php

namespace Wuunder\Wuunderconnector\Model\ResourceModel;

class WuunderShipment extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

  public function _construct()
  {
      $this->_init('wuunder_shipment', 'shipment_id');
  }

}
