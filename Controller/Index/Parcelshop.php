<?php

namespace Wuunder\Wuunderconnector\Controller\Index;

use \Wuunder\Wuunderconnector\Helper\Data;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as checkoutSession;

class Parcelshop extends \Magento\Framework\App\Action\Action
{
    protected $logger;

    protected $scopeConfig;

    protected $HelperBackend;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    public function __construct(
        \Magento\Backend\Helper\Data $HelperBackend,
        \Psr\Log\LoggerInterface $logger, 
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Data $helper,
        CheckoutSession $checkoutSession,
        Context $context
    ) {
        $this->HelperBackend = $HelperBackend;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->_checkoutSession = $checkoutSession;
        parent::__construct($context);
    }
    /**
     * Parcelshop action
     *
     * @return void
     */
    public function execute()
    {
        $post = $this->getRequest()->getPostValue();
        if (null !== $this->getRequest()->getParam('getAddress')) {
            $this->getCheckoutAddress();
        }

        if (null !== $this->getRequest()->getParam('setParcelshopId')) {
            $this->setParcelshopId($post);
        }

    }

    private function getCheckoutAddress()
    {
        //dit werkt niet... Lege return.
        $checkoutAddress = $this->_checkoutSession->getQuote()->getShippingAddress();
        return(($checkoutAddress));
    }

    private function setParcelshopId($post)
    {
        if ($post['parcelshopId']) {
            $parcelshopId = $post['parcelshopId'];
            $address = $this->getParcelshopAddress($parcelshopId);
            $encodedAddress = json_encode($address);
            $quoteId = $this->_checkoutSession->getQuote()->getQuoteId();
            _saveParcelshopId($parcelshopId, $quoteId);
            die($encodedAddress);
        }
        return null;
    }

    private function getParcelshopAddress($id)
    {
        if (empty($id)) {
            echo null;
        } else {
            $test_mode = $this->scopeConfig->getValue('wuunder_wuunderconnector/general/testmode');

            if ($test_mode == 1) {
                $apiKey = $this->scopeConfig->getValue('wuunder_wuunderconnector/general/api_key_test');
            } else {
                $apiKey = $this->scopeConfig->getValue('wuunder_wuunderconnector/general/api_key_live');
            }

            $connector = new \Wuunder\Connector($apiKey);
            $connector->setLanguage("NL");
            $parcelshopRequest = $connector->getParcelshopById();
            $parcelshopConfig = new \Wuunder\Api\Config\ParcelshopConfig();
    
            $parcelshopConfig->setId($id);
    
            if ($parcelshopConfig->validate()) {
                $parcelshopRequest->setConfig($parcelshopConfig);
                if ($parcelshopRequest->fire()) {
                    $parcelshop = $parcelshopRequest->getParcelshopResponse()->getParcelshopData();
                } else {
                    var_dump($parcelshopRequest->getParcelshopResponse()->getError());
                }
            } else {
                $parcelshop = "ParcelshopsConfig not complete";
            }
            echo json_encode($parcelshop);
        }
    
        exit;
    }

    private function _saveParcelshopId($parcelshopId, $quoteId) 
    {
        
    }
}