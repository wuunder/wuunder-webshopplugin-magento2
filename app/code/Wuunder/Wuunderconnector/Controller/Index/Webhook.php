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

    public function execute()
    {

        if (!is_null($this->getRequest()->getParam('order_id')) && !empty($this->getRequest()->getParam('order_id'))) {
            $this->helper->log("Webhook executed");

            // Met order_id load($this->getRequest->getParam()) en met die lijst (json_decode) call naar automated.

            $result = json_decode(file_get_contents('php://input'), true);
            if ($result['action'] === "shipment_booked") {
                $this->helper->log("Webhook - Shipment for order: " . $this->getRequest()->getParam('order_id'));
                $result = $result['shipment'];

                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $wuunderShipment = $objectManager->create('Wuunder\Wuunderconnector\Model\WuunderShipment');
                $wuunderShipment->load($this->getRequest()->getParam('order_id') , 'order_id');

                // Only if the result kind is package will multiple boxes be sent
                if ($result['kind'] === 'package')
                {
                  $this->helper->log("Kind is package, preparing to send multiple boxes", '/var/log/ecobliss.log');
                  // Fetch number of boxes from DB
                  $numBoxes = $wuunderShipment->getBoxesOrder();

                  // Fetch API-key and Url
                  $test_mode = $this->scopeConfig->getValue('wuunder_wuunderconnector/general/testmode');
                  $this->helper->log("Test mode is: " . $test_mode, '/var/log/ecobliss.log');
                  if ($test_mode == 1) {
                      $apiUrl = 'https://api-staging.wearewuunder.com/api/shipments';
                      $apiKey = $this->scopeConfig->getValue('wuunder_wuunderconnector/general/api_key_test');
                  } else {
                      $apiUrl = 'https://api.wearewuunder.com/api/shipments';
                      $apiKey = $this->scopeConfig->getValue('wuunder_wuunderconnector/general/api_key_live');
                  }
                  $this->helper->log("Api-Url: " . $apiUrl, '/var/log/ecobliss.log');
                  $this->helper->log("Api-Key: " . $apiKey, '/var/log/ecobliss.log');

                  $this->helper->log("Total boxes: " . (string)$numBoxes, '/var/log/ecobliss.log');
                  for ($i=0; $i < $numBoxes-1; $i++) {
                      $this->helper->log("Sending shipment number: " . $i, '/var/log/ecobliss.log');
                      // Call to the automated API
                      //Results nog omzetten in Data dan is het goed.

                      /* TO DO */                      
                      /* Do correct formating of the return data to actual data */
                      $header = $this->helper->curlRequest($data, $apiUrl, $apiKey, true);
                      $this->helper->log($header, '/var/log/ecobliss.log');
                      // Use results towards function in helper?
                  }
                }

                $this->helper->log("Setting the total number of boxes to NULL", '/var/log/ecobliss.log');
                $wuunderShipment->setBoxesOrder(null);

                $wuunderShipment->setLabelId($result['id']);
                $wuunderShipment->setLabelUrl($result['label_url']);
                $wuunderShipment->setTtUrl($result['track_and_trace_url']);
                $wuunderShipment->save();
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
