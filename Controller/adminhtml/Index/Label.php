<?php

namespace Wuunder\Wuunderconnector\Controller\adminhtml\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result;
use Magento\Framework\Controller\ResultFactory;
use \Wuunder\Wuunderconnector\Helper\Data;


class Label extends \Magento\Framework\App\Action\Action
{
    protected $resultPageFactory;
    protected $orderRepository;
    protected $productloader;
    protected $scopeConfig;
    protected $storeManagerInterface;
    protected $HelperBackend;
    protected $messageManager;

    public function __construct(
        Data $helper,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Catalog\Model\ProductFactory $productloader,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Backend\Helper\Data $HelperBackend,
        Context $context
    ) {
        $this->helper = $helper;
        $this->resultPageFactory = $resultPageFactory;
        $this->orderRepository = $orderRepository;
        $this->productloader = $productloader;
        $this->scopeConfig = $scopeConfig;
        $this->storeManagerInterface = $storeManagerInterface; // Cannot be fetched from Action context, got via dep. injection
        $this->HelperBackend = $HelperBackend;
        $this->messageManager = $context->getMessageManager();
        parent::__construct($context);
    }

    public function execute()
    {
        $redirect_url = $this->processOrderInfo();
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        if (empty($redirect_url)) {
            $this->messageManager->addError(
                "Wuunderconnector: Something went wrong, please check the logging."
            );
            $resultRedirect->setUrl($this->HelperBackend->getUrl('sales/order'));
        } else {
            $resultRedirect->setUrl($redirect_url);
        }
        return $resultRedirect;
    }

    private function processOrderInfo()
    {
        $orderId = $this->getRequest()->getParam('orderId');
        if (!$this->wuunderShipmentExists($orderId)) {
            $infoArray = $this->getOrderInfo($orderId);
            // Fetch order
            $order = $this->orderRepository->get($orderId);

            // Get configuration
            $test_mode = $this->scopeConfig->getValue(
                'wuunder_wuunderconnector/general/testmode'
            );
            $booking_token = uniqid();
            $infoArray['booking_token'] = $booking_token;
            $redirect_url = $this->HelperBackend->getUrl('sales/order');
            $webhook_url = $this->storeManagerInterface->getStore()->getBaseUrl()
            . 'wuunder/index/webhook/order_id/' . $orderId;

            if ($test_mode == 1) {
                $apiKey = $this->scopeConfig->getValue(
                    'wuunder_wuunderconnector/general/api_key_test'
                );
            } else {
                $apiKey = $this->scopeConfig->getValue(
                    'wuunder_wuunderconnector/general/api_key_live'
                );
            }

            // Combine wuunder info and order data
            $bookingConfig = $this->buildWuunderData($infoArray, $order);
            $bookingConfig->setRedirectUrl($redirect_url);
            $bookingConfig->setWebhookUrl($webhook_url);

            $connector = new \Wuunder\Connector($apiKey, $test_mode == 1);
            $booking = $connector->createBooking();

            

            if ($bookingConfig->validate()) {
                $booking->setConfig($bookingConfig);
                $this->helper->log("Going to fire for bookingurl");
                if ($booking->fire()) {
                    $redirect_url = $booking->getBookingResponse()->getBookingUrl();
                    // Create or update wuunder_shipment
                    $this->saveWuunderShipment(
                        $orderId, $redirect_url,
                        "testtoken"
                    );
                    return $redirect_url;
                } else {
                    $this->helper->log($booking->getBookingResponse()->getError());
                }
            } else {
                $this->helper->log("Bookingconfig not complete");
            }


        }
        return null;
    }

    private function saveWuunderShipment($orderId, $bookingUrl, $bookingToken)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $wuunderShipment = $objectManager->create(
            'Wuunder\Wuunderconnector\Model\WuunderShipment'
        );
        $wuunderShipment->load(
            $this->getRequest()->getParam('order_id'),
            'order_id'
        );
        $wuunderShipment->setOrderId($orderId);
        $wuunderShipment->setBookingUrl($bookingUrl);
        $wuunderShipment->setBookingToken($bookingToken);
        $wuunderShipment->save();
    }

    private function wuunderShipmentExists($orderId)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $wuunderShipment = $objectManager->create(
            'Wuunder\Wuunderconnector\Model\WuunderShipment'
        );
        $wuunderShipment->load($orderId, 'order_id');
        $shipmentData = $wuunderShipment->getData();

        return (bool)$shipmentData;
    }

    private function getOrderInfo($orderId)
    {
        $messageField = 'personal_message';

        $order = $this->orderRepository->get($orderId);
        $shippingAdr = $order->getShippingAddress();

        $shipmentDescription = "";
        $weight = 0;
        foreach ($order->getAllItems() as $item) {
            $product = $this->productloader->create()->load($item->getProductId());
            $shipmentDescription .= $product->getName() . " ";
            $weight += intval($item->getWeight()) * intval($item->getQtyOrdered()) ;
        }

        $phonenumber = trim($shippingAdr->getTelephone());
        // Set default values
        if ((substr($phonenumber, 0, 1) == '0')
            && ($shippingAdr->getCountryId() == 'NL')
        ) {
            // If NL and phonenumber starting with 0, replace it with +31
            $phonenumber = '+31' . substr($phonenumber, 1);
        }

        return array(
            'reference' => $orderId,
            'description' => $shipmentDescription,
            $messageField => '',
            'phone_number' => $phonenumber,
            'weight' => $weight
        );
    }

    private function buildWuunderData($infoArray, $order)
    {
        $this->helper->log("Building data object for api.");
        $shippingAddress = $order->getShippingAddress();

        $shippingLastname = $shippingAddress->getLastname();

        $streetAddress = $shippingAddress->getStreet();
        if (count($streetAddress) > 1) {
            $streetName = $streetAddress[0];
            $houseNumber = $streetAddress[1];
        } else {
            $streetAddress = $this->addressSplitter($streetAddress[0]);
            $streetName = $streetAddress['streetName'];
            $houseNumber = $streetAddress['houseNumber']
            . $shippingAddress['houseNumberSuffix'];
        }

        // Fix wuunder parcelshop first- and lastname override fix
        $firstname = $shippingAddress->getFirstname();
        $lastname = $shippingLastname;
        $company = $shippingAddress->getCompany();
        if ($order->getCustomerEmail() !== '') {
            $email = $order->getCustomerEmail();
        } else {
            $email = $shippingAddress->getEmail();
        }

        $deliveryAddress = new \Wuunder\Api\Config\AddressConfig();
        $deliveryAddress->setBusiness(!empty($company) ? $company : null);
        $deliveryAddress->setEmailAddress($email);
        $deliveryAddress->setFamilyName($lastname);
        $deliveryAddress->setGivenName($firstname);
        $deliveryAddress->setLocality($shippingAddress->getCity());
        $deliveryAddress->setStreetName($streetName);
        $deliveryAddress->setHouseNumber($houseNumber);
        $deliveryAddress->setZipCode($shippingAddress->getPostcode());
        $deliveryAddress->setPhoneNumber($infoArray['phone_number']);
        $deliveryAddress->setCountry($shippingAddress->getCountryId());
        if (!$deliveryAddress->validate()) {
            $this->helper->log("Invalid delivery address. There are mistakes or missing fields.");
        }

        $pickupAddress = new \Wuunder\Api\Config\AddressConfig();
        $pickupAddress->setEmailAddress(
            $this->scopeConfig->getValue('wuunder_wuunderconnector/general/email')
        );
        $pickupAddress->setFamilyName(
            $this->scopeConfig->getValue('wuunder_wuunderconnector/general/lastname')
        );
        $pickupAddress->setGivenName(
            $this->scopeConfig->getValue(
                'wuunder_wuunderconnector/general/firstname'
            )
        );
        $pickupAddress->setLocality(
            $this->scopeConfig->getValue('wuunder_wuunderconnector/general/city')
        );
        $pickupAddress->setStreetName(
            $this->scopeConfig->getValue(
                'wuunder_wuunderconnector/general/streetname'
            )
        );
        $pickupAddress->setHouseNumber(
            $this->scopeConfig->getValue(
                'wuunder_wuunderconnector/general/housenumber'
            )
        );
        $pickupAddress->setZipCode(
            $this->scopeConfig->getValue('wuunder_wuunderconnector/general/zipcode')
        );
        $pickupAddress->setPhoneNumber(
            $this->scopeConfig->getValue('wuunder_wuunderconnector/general/phone')
        );
        $pickupAddress->setCountry(
            $this->scopeConfig->getValue('wuunder_wuunderconnector/general/country')
        );
        $pickupAddress->setBusiness(
            $this->scopeConfig->getValue('wuunder_wuunderconnector/general/company')
        );
        if (!$pickupAddress->validate()) {
            $this->helper->log(
                "Invalid pickup address. There are mistakes or missing fields."
            );
        }

        // Load product image for first ordered item
        $image = null;
        $orderedItems = $order->getAllVisibleItems();
        if (count($orderedItems) > 0) {
            foreach ($orderedItems AS $orderedItem) {

                $_product = $this->productloader->create()->load(
                    $orderedItem->getProductId()
                );
                $imageUrl = $this->storeManagerInterface->getStore()->getBaseUrl(
                    \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                ) . 'catalog/product' . $_product->getImage();
                try {
                    if (!empty($_product->getImage())) {
                        $data = @file_get_contents($imageUrl);
                        if ($data) {
                            $base64Image = base64_encode($data);
                        } else {
                            $base64Image = null;
                        }
                    } else {
                        $base64Image = null;
                    }
                } catch (\Exception $e) {
                    $base64Image = null;
                }
                if (!is_null($base64Image)) {
                    // Break after first image
                    $image = $base64Image;
                    break;
                }
            }
        }

        $preferredServiceLevel = null;
        $usedShippingMethod = $order->getShippingMethod();
        for ($i = 1; $i < 5; $i++) {
            if ($this->scopeConfig->getValue('wuunder_wuunderconnector/advanced/filtermapping_' . $i . '_carrier') === $usedShippingMethod) {
                $preferredServiceLevel = $this->scopeConfig->getValue(
                    'wuunder_wuunderconnector/advanced/filtermapping_' . $i . '_filter'
                );
                break;
            }
        }

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productMetadata = $objectManager->get(
            'Magento\Framework\App\ProductMetadataInterface'
        );
        $version = $productMetadata->getVersion();

        //get parcelshop id from quote id
        $parcelshopId = $this->getParcelshopIdForQuote(
            $order->getQuoteId(), $objectManager
        );

        $bookingConfig = new \Wuunder\Api\Config\BookingConfig();
        $bookingConfig->setDescription($infoArray['description']);
        $bookingConfig->setPersonalMessage($infoArray['personal_message']);
        $bookingConfig->setPicture($image);
        $bookingConfig->setCustomerReference($order->getIncrementId());
        $bookingConfig->setPreferredServiceLevel($preferredServiceLevel);
        $bookingConfig->setWeight($infoArray['weight']);
        $bookingConfig->setValue($order->getBaseGrandTotal() * 100);
        $bookingConfig->setSource(
            array(
                "product" => "Magento 2 extension",
                "version" => array(
                    "build" => "2.2.0",
                    "plugin" => "2.1"),
                    "platform" => array(
                        "name" => "Magento",
                        "build" => $version))
        );
        $bookingConfig->setDeliveryAddress($deliveryAddress);
        $bookingConfig->setPickupAddress($pickupAddress);
        //add parcelshopid to bookingconfig
        if (isset($parcelshopId)) {
            $bookingConfig->setParcelshopId($parcelshopId);
        }
        return $bookingConfig;
    }

    private function addressSplitter($address)
    {
        if (!isset($address)) {
            return false;
        }

        // Pregmatch pattern, dutch addresses
        $pattern = '#^([a-z0-9 [:punct:]\']*) ([0-9]{1,5})([a-z0-9 \-/]{0,})$#i';

        preg_match($pattern, $address, $addressParts);

        $result['streetName'] = isset($addressParts[1]) ? $addressParts[1] : $address;
        $result['houseNumber'] = isset($addressParts[2]) ? $addressParts[2] : "";
        $result['houseNumberSuffix'] = (isset($addressParts[3])) ? $addressParts[3] : '';

        return $result;
    }

    private function getParcelshopIdForQuote($quoteId, $objectManager)
    {
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $tableName = $resource->getTableName('wuunder_quote_id');
        $sql = "SELECT parcelshop_id FROM " . $tableName ." WHERE quote_id = '" . $quoteId . "'";
        try {
            $parcelshopId = $connection->fetchOne($sql);
        } catch (\Exception $e) {
            $this->helper->log('ERROR getWuunderShipment : ' . $e);
            return null;
        }

        return ($parcelshopId ? $parcelshopId : null);
    }
}
