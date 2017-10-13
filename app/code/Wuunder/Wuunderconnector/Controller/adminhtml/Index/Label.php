<?php

namespace Wuunder\Wuunderconnector\Controller\adminhtml\Index;

use Magento\Framework\App\Action\Context;

class Label extends \Magento\Framework\App\Action\Action
{
    protected $_resultPageFactory;

    public function __construct(Context $context, \Magento\Framework\View\Result\PageFactory $resultPageFactory)
    {
        $this->_resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        echo "hoi";
//        $resultPage = $this->_resultPageFactory->create();
//        return $resultPage;
    }

    private function processOrderInfo($infoArray)
    {
        // Fetch order
        $order = Mage::getModel('sales/order')->load($infoArray['order_id']);
        $storeId = $order->getStoreId();

        // Get configuration
        $test_mode = Mage::getStoreConfig('wuunderconnector/connect/testmode', $storeId);
        $booking_token = uniqid();
        $infoArray['booking_token'] = $booking_token;
        $redirect_url = urlencode(Mage::getUrl('adminhtml') . 'sales_order?label_order=' . $infoArray['order_id']);
        $webhook_url = urlencode(Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) . 'wuunderconnector/webhook/call/order_id/' . $infoArray['order_id'] . "/token/" . $booking_token);

        if ($test_mode == 1) {
            $apiUrl = 'https://api-staging.wuunder.co/api/bookings?redirect_url=' . $redirect_url . '&webhook_url=' . $webhook_url;
            $apiKey = Mage::getStoreConfig('wuunderconnector/connect/api_key_test', $storeId);
        } else {
            $apiUrl = 'https://api.wuunder.co/api/bookings?redirect_url=' . $redirect_url . '&webhook_url=' . $webhook_url;
            $apiKey = Mage::getStoreConfig('wuunderconnector/connect/api_key_live', $storeId);
        }

        // Combine wuunder info and order data
        $wuunderData = $this->buildWuunderData($infoArray, $order);

        // Encode variables
        $json = json_encode($wuunderData);
        // Setup API connection
        $cc = curl_init($apiUrl);
        $this->log('API connection established');

        curl_setopt($cc, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $apiKey, 'Content-type: application/json'));
        curl_setopt($cc, CURLOPT_POST, 1);
        curl_setopt($cc, CURLOPT_POSTFIELDS, $json);
        curl_setopt($cc, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cc, CURLOPT_VERBOSE, 1);
        curl_setopt($cc, CURLOPT_HEADER, 1);

        // Don't log base64 image string
        $wuunderData['picture'] = 'base64 string removed for logging';

        // Execute the cURL, fetch the XML
        $result = curl_exec($cc);
        $header_size = curl_getinfo($cc, CURLINFO_HEADER_SIZE);
        $header = substr($result, 0, $header_size);
        preg_match("!\r\n(?:Location|URI): *(.*?) *\r\n!i", $header, $matches);
        $url = $matches[1];

        // Close connection
        curl_close($cc);

        $infoArray['booking_url'] = $url;
        // Create or update wuunder_shipment
        if (!$this->saveWuunderShipment($infoArray)) {
            return array('error' => true, 'message' => 'Unable to create / update wuunder_shipment for order ' . $infoArray['order_id']);
        }

        Mage::helper('wuunderconnector')->log('API response string: ' . $result);

        // Decode API result
        $result = json_decode($result);

        // Check for API errors
        if (isset($result->error)) {
            return $this->showWuunderAPIError($result->error);
        }
        if (isset($result->errors)) {
            return $this->showWuunderAPIError($result->errors);
        }


        if (empty($url) || is_null($url)) {
            return array(
                'error' => true,
                'message' => 'Er ging iets fout bij het updaten van tabel wuunder_shipments',
                'booking_url' => $url);
        } else {
            return array(
                'error' => false,
                'booking_url' => $url);
        }
    }
}