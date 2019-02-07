<?php

namespace Wuunder\Wuunderconnector\Controller\Index;

use \Wuunder\Wuunderconnector\Helper\Data;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as checkoutSession;
use \Wuunder\Wuunderconnector\Model\QuoteIdFactory;
use Magento\Framework\Controller\ResultFactory;

class Parcelshop extends \Magento\Framework\App\Action\Action
{
    protected $logger;

    protected $scopeConfig;

    protected $HelperBackend;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    protected $_QuoteId;

    public function __construct(
        \Magento\Backend\Helper\Data $HelperBackend,
        \Psr\Log\LoggerInterface $logger, 
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Data $helper,
        \Magento\Framework\Controller\ResultFactory $result,       
        \Wuunder\Wuunderconnector\Model\QuoteIdFactory $QuoteId,
        Context $context
    ) {
        $this->HelperBackend = $HelperBackend;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->resultRedirect = $result;
        $this->_QuoteId = $QuoteId;
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
        if (null !== $this->getRequest()->getParam('setParcelshopId')) {
            $parcelshopId = $post['parcelshopId'];
            $quoteId = $post['quoteId'];
            $this->_saveParcelshopId($parcelshopId, $quoteId);
            $this->setParcelshopId($parcelshopId);
        }

    }

    private function setParcelshopId($id)
    {
        if ($id) {
            $address = $this->getParcelshopAddress($id);
            $encodedAddress = json_encode($address);
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
                $this->helper->log("ParcelshopsConfig not complete");
                die(null);
            }
            $this->helper->log("parcelshop return");
            echo json_encode($parcelshop);
        }
    
        exit;
    }

    private function _saveParcelshopId($parcelshopId, $quoteId) 
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $tableName = $resource->getTableName('wuunder_quote_id');

        //Check if current quote is already in database
        $sql = "SELECT * FROM " . $tableName ." WHERE quote_id =" . $quoteId;
        if ($result = $connection->fetchAll($sql)) {
            $sql = "UPDATE ". $tableName . " SET parcelshop_id = '" . $parcelshopId . "' WHERE quote_id = " . $quoteId;
            $connection->query($sql);
        } else {
            $model = $this->_QuoteId->create();
            $model->addData(
                [
                "quote_id" => $quoteId,
                "parcelshop_id" => $parcelshopId,
                ]
            );
            $saveData = $model->save();    
        }
    }
}