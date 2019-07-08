<?php 
namespace Wuunder\Wuunderconnector\Model\ResourceModel\QuoteId;
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct(){
        $this->_init(
            "Wuunder\Wuunderconnector\Model\QuoteId", 
            "Wuunder\Wuunderconnector\Model\ResourceModel\QuoteId"
        );
    }
}