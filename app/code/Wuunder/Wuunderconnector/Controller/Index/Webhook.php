<?php

namespace Wuunder\Wuunderconnector\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Model\Order;

class Webhook extends \Magento\Framework\App\Action\Action
{

    protected $scopeConfig;

    public function __construct(Context $context, \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    public function execute()
    {

        if (!is_null($this->getRequest()->getParam('order_id')) && !empty($this->getRequest()->getParam('order_id'))) {
            $this->helper->log("Received the webhook.");
            $result = json_decode(file_get_contents('php://input'), true);
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
            $connection = $resource->getConnection();
            $tableName = $resource->getTableName('wuunder_shipment');
            $result = $result['shipment'];
//            $sql = "UPDATE " . $tableName . " SET label_id = ?, label_url = ?, tt_url = ? WHERE order_id = ?";
            $sql = "UPDATE " . $tableName . " SET label_id = '".$result['id']."', label_url = '".$result['label_url']."', tt_url = '".$result['track_and_trace_url']."' WHERE order_id = ".$this->getRequest()->getParam('order_id');
//            $connection->query($sql, array($result['id'], $result['label_url'], $result['track_and_trace_url'], $this->getRequest()->getParam('order_id')));
            $connection->query($sql);
            $this->ship($this->getRequest()->getParam('order_id'));
        }

    }

    private function ship($order_id) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('\Magento\Sales\Model\Order') ->load($order_id);
        $orderState = $this->scopeConfig->getValue('wuunder_wuunderconnector/advanced/post_booking_status');
        $order->setState($orderState)->setStatus($this->scopeConfig->getValue('wuunder_wuunderconnector/advanced/post_booking_status'));
        $order->save();
    }
}
