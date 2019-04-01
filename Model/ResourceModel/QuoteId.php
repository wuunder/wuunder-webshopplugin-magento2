<?php 
namespace Wuunder\Wuunderconnector\Model\ResourceModel;

class QuoteId extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function _construct()
    {
        $this->_init('wuunder_quote_id', 'id');
    }
}
?>