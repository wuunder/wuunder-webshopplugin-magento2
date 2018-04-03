<?php

namespace Wuunder\Wuunderconnector\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Model\Order;
use \Wuunder\Wuunderconnector\Helper\Data;

class Webhook extends \Magento\Framework\App\Action\Action
{

    protected $scopeConfig;

    public function __construct(Context $context, \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, Data $helper)
    {
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        parent::__construct($context);
    }

    public function setShipment()
    {
      // Model kan waarschijnlijk in constructor aangemaakt worden. Hoeft maar één keer.
        $model = $this->_objectManager->create('Wuunder\Wuunderconnector\Model\WuunderShipment');
        $return = $model->setData();

        return $return;
    }

    public function execute()
    {

        if (!is_null($this->getRequest()->getParam('order_id')) && !empty($this->getRequest()->getParam('order_id'))) {
            $this->helper->log("Webhook executed");
            $result = json_decode(file_get_contents('php://input'), true);
            if ($result['action'] === "shipment_booked") {
                $this->helper->log("Webhook - Shipment for order: " . $this->getRequest()->getParam('order_id'));
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                // $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
                // $connection = $resource->getConnection();
                // $tableName = $resource->getTableName('wuunder_shipment');
                $result = $result['shipment'];

                // $WSFactory->setData('label_id',$result['id']);
                $contact = $objectManager->create('Wuunder\Wuunderconnector\Model\WuunderShipment');
                $contact->load($this->getRequest()->getParam('order_id') , 'order_id');
                $contact->setLabelId(00); // Add variables here, something was not working yet
                $contact->setLabelUrl('Dit is een test voor label_url');
                $contact->setTtUrl('Dit is een test voor tt_url');
                $contact->save();
    // //            $sql = "UPDATE " . $tableName . " SET label_id = ?, label_url = ?, tt_url = ? WHERE order_id = ?";
    //             $sql = "UPDATE " . $tableName . " SET label_id = '".$result['id']."', label_url = '".$result['label_url']."', tt_url = '".$result['track_and_trace_url']."' WHERE order_id = ".$this->getRequest()->getParam('order_id');
    // //            $connection->query($sql, array($result['id'], $result['label_url'], $result['track_and_trace_url'], $this->getRequest()->getParam('order_id')));
                // $connection->query($sql);
            } else if ($result['action'] === "track_and_trace_updated"){
                $this->helper->log("Webhook - Track and trace for order: " . $this->getRequest()->getParam('order_id'));
                $this->ship($this->getRequest()->getParam('order_id'), $result['carrier_code'], $result['track_and_trace_code']);
            }
        } else {
          $this->helper->log("Invalid order_id for the webhook");
        }

    }

    private function ship($order_id, $carrier, $label_id) {

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('\Magento\Sales\Model\Order') ->load($order_id);

        if ($order->canShip()) {
            $this->helper->log("Is able to ship");
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

            $track = $objectManager->create('\Magento\Sales\Model\Order\Shipment\TrackFactory')->create();
            $track->setNumber($label_id);
            $track->setCarrierCode('wuunder');
            $track->setTitle($carrier);
            $shipment->addTrack($track);

            $shipment->register();
            $shipment->addComment("Order Completed And Shipped");
            $shipment->setEmailSent(true);
            $shipment->getOrder()->setIsInProcess(true);

            try {
                $shipment->save();
                $shipment->getOrder()->save();

                $orderState = $this->scopeConfig->getValue('wuunder_wuunderconnector/advanced/post_booking_status');
                $order->setState($orderState)->setStatus($this->scopeConfig->getValue('wuunder_wuunderconnector/advanced/post_booking_status'));
                $order->save();

                $shipment->save();
                $this->helper->log("Order shipped and shipment saved");
            } catch (\Exception $e) {
                $this->helper->log($e->getMessage());
            }
        } else {
            $this->helper->log("Error: Cannot ship");
        }
    }
}
