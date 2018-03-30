<?php

namespace Wuunder\Wuunderconnector\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Model\Order;
use \Wuunder\Wuunderconnector\Helper\Data;

class Webhook extends \Magento\Framework\App\Action\Action
{

    protected $scopeConfig;

    public function __construct(Context $context, \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, Data $helper, \Magento\Framework\DB\Transaction $transactionFactory)
    {
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->transactionFactory = $transactionFactory;
        parent::__construct($context);
    }

    public function execute()
    {

        if (!is_null($this->getRequest()->getParam('order_id')) && !empty($this->getRequest()->getParam('order_id'))) {
            // $this->helper->log("Received the webhook.");
            $result = json_decode(file_get_contents('php://input'), true);
            if ($result['action'] === "shipment_booked") {
                $this->helper->log("Webhook - Shipment for order: " . $this->getRequest()->getParam('order_id'));
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
                $connection = $resource->getConnection();
                $tableName = $resource->getTableName('wuunder_shipment');
                $result = $result['shipment'];
    //            $sql = "UPDATE " . $tableName . " SET label_id = ?, label_url = ?, tt_url = ? WHERE order_id = ?";
                $sql = "UPDATE " . $tableName . " SET label_id = '".$result['id']."', label_url = '".$result['label_url']."', tt_url = '".$result['track_and_trace_url']."' WHERE order_id = ".$this->getRequest()->getParam('order_id');
    //            $connection->query($sql, array($result['id'], $result['label_url'], $result['track_and_trace_url'], $this->getRequest()->getParam('order_id')));
                $connection->query($sql);
            } else if ($result['action'] === "track_and_trace_updated"){
                $this->helper->log("Webhook - Track and trace for order: " . $this->getRequest()->getParam('order_id'));
                $this->ship($this->getRequest()->getParam('order_id'), $result['carrier_code'], $result['track_and_trace_code']);
            }
        }

    }

    private function ship($order_id, $carrier, $label_id) {

        $email = true;
        $includeComment = false;
        $comment = "Order Completed And Shipped";

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('\Magento\Sales\Model\Order') ->load($order_id);

        if ($order->canShip()) {
            $this->helper->log("Can ship");
            $convertOrder = $objectManager->create('\Magento\Sales\Model\Convert\Order');
            $shipment = $convertOrder->toShipment($order);
            foreach ($order->getAllItems() as $orderItem) {
              if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                  continue;
              }
              $qtyShipped = $orderItem->getQtyToShip();
              $shipmentItem = $convertOrder->itemToShipmentItem($orderItem)->setQty($qtyShipped);
              $shipment->addItem($shipmentItem);
            }
$this->helper->log("Adding track and trace");
            $track = $objectManager->create('\Magento\Sales\Model\Order\Shipment\TrackFactory')->create();
            $track->setNumber($label_id);
            $track->setCarrierCode('wuunder');
            $track->setTitle($carrier);
            $shipment->addTrack($track);

$this->helper->log("Registering Shipment");
            $shipment->register();
            $shipment->addComment($comment);
            $shipment->setEmailSent(true);
            $shipment->getOrder()->setIsInProcess(true);
$this->helper->log("Shipment set: in process");

            try {
                $saveTransaction = $this->transactionFactory->create();
                $saveTransaction->addObject($shipment)
                                ->addObject($shipment->getOrder())
                                ->save();
                $this->helper->log("transaction saved");
            } catch (\Exception $e) {
                $this->helper->log($e->getMessage());
            }

            // $shipment->sendEmail($email, ($includeComment ? $comment : ''));

            $orderState = $this->scopeConfig->getValue('wuunder_wuunderconnector/advanced/post_booking_status');
            $order->setState($orderState)->setStatus($this->scopeConfig->getValue('wuunder_wuunderconnector/advanced/post_booking_status'));
            $order->save();
$this->helper->log("Order status updated");

            $shipment->save();
            $this->helper->log("Shipment saved");
        } else {
            $this->helper->log("Error: Cannot ship");
        }
    }
}
