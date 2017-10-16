<?php

namespace Wuunder\Wuunderconnector\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class Webhook extends \Magento\Framework\App\Action\Action
{
    public function __construct(Context $context)
    {
        parent::__construct($context);
    }

    public function execute()
    {

        if (!is_null($this->getRequest()->getParam('order_id')) && !empty($this->getRequest()->getParam('order_id'))) {
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
//            $processDataSuccess = Mage::helper('wuunderconnector')->processDataFromApi($result['shipment'], "no_retour", $this->getRequest()->getParam('order_id'), $this->getRequest()->getParam('token'));
//            if (!$processDataSuccess) {
//                Mage::helper('wuunderconnector')->log("Cannot update wuunder_shipment data");
//            } else {
//                $this->ship($this->getRequest()->getParam('order_id'), $result['shipment']['id']);
//            }
        } else {
//            Mage::helper('wuunderconnector')->log("Invalid order_id for webhook");
        }

    }
}