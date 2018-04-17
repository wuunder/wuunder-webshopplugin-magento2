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

            $result = json_decode(file_get_contents('php://input'), true);
            if ($result['action'] === "shipment_booked") {
                $this->helper->log("Webhook - Shipment for order: " . $this->getRequest()->getParam('order_id'));
                $result = $result['shipment'];

                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $wuunderShipment = $objectManager->create('Wuunder\Wuunderconnector\Model\WuunderShipment');
                $wuunderShipment->load($this->getRequest()->getParam('order_id') , 'order_id');
                $wuunderShipment->setLabelId($result['id']);
                $wuunderShipment->setLabelUrl($result['label_url']);
                $wuunderShipment->setTtUrl($result['track_and_trace_url']);
                $wuunderShipment->save();

                // Fetch number of boxes from DB
                $numBoxes = $wuunderShipment->getBoxesOrder();

                // Only if the result kind is package and the number of boxes is positive will multiple boxes be sent
                // Could add a third check for NULL
                if ($result['kind'] === 'package' && $numBoxes != 0)
                {
                    $this->helper->log("Kind is package with multiple boxes, preparing to send multiple boxes", '/var/log/ecobliss.log');

                    // Fetch API-key and Url, based on the test mode
                    $test_mode = $this->scopeConfig->getValue('wuunder_wuunderconnector/general/testmode');
                    $apiData = $this->getApiData($test_mode);

                    // Parse the data into correct format for automated API
                    $data = $this->parseData($result);

                    $this->helper->log("Total boxes: " . (string)$numBoxes, '/var/log/ecobliss.log');
                    for ($i=0; $i < $numBoxes-1; $i++) {
                        $this->helper->log("Sending shipment number: " . $i, '/var/log/ecobliss.log');
                        // Call to the automated API
                        $header = $this->helper->curlRequest($data, $apiData['api_url'], $apiData['api_key']);
                        $this->helper->log($header, '/var/log/ecobliss.log');
                    }
                }

                $this->helper->log("Setting the total number of boxes to NULL", '/var/log/ecobliss.log');
                $wuunderShipment->setBoxesOrder(null);
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

    private function parseData($result)
    {
        return array (
          'description'             => $result['description'],
          'value'                   => $result['value']*100,
          'kind'                    => $result['kind'],
          'length'                  => $result["length"],
          'width'                   => $result["width"],
          'height'                  => $result["height"],
          'weight'                  => $result["weight"],
          'delivery_address'        => $result['delivery_address'],
          'pickup_address'          => $result['pickup_address'],
          'preferred_service_level' => 'dpd_cheapest',
          'personal_message'        => (isset($result['personal_message']) ? $result['personal_message'] : ""),
          'picture'                 => (isset($result['picture']) ? $result['picture'] : ""),
          'customer_reference'      => (isset($result['customer_reference']) ? $result['customer_reference'] : ""),
          'is_return'               => (isset($result['is_return']) ? $result['is_return'] : false),
          'drop_off'                => (isset($result['drop_off']) ? $result['drop_off'] : false),
          'parcelshop_id'           => (isset($result['parcelshop_id']) ? $result['parcelshop_id'] : "")
        );
    }

    private function getApiData($test_mode)
    {
      $this->helper->log("Test mode is: " . $test_mode, '/var/log/ecobliss.log');
      $this->helper->log("Fetching API url & key.", '/var/log/ecobliss.log');
      if ($test_mode == 1) {
          $apiUrl = 'https://api-staging.wearewuunder.com/api/shipments';
          $apiKey = $this->scopeConfig->getValue('wuunder_wuunderconnector/general/api_key_test');
      } else {
          $apiUrl = 'https://api.wearewuunder.com/api/shipments';
          $apiKey = $this->scopeConfig->getValue('wuunder_wuunderconnector/general/api_key_live');
      }

      return array (
        'api_url' => $apiUrl,
        'api_key' => $apiKey
      );
    }

}
