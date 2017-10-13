<?php
namespace Wuunder\Wuunderconnector\Model\ResourceModel\Order\Grid;

use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as OriginalCollection;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface as Logger;

class Collection extends OriginalCollection
{
    protected $_authSession;
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        \Magento\Backend\Model\Auth\Session $authSession
    )
    {
        $this->_authSession = $authSession;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager);
    }

    protected function _renderFiltersBefore() {
        $joinTable = $this->getTable('wuunder_shipment');
        $this->getSelect()->joinLeft($joinTable, 'wuunder_shipment.order_id = main_table.entity_id', ['label_id', 'label_url', 'tt_url', 'booking_url']);
        parent::_renderFiltersBefore();
    }
}
?>