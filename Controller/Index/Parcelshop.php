<?php

namespace Wuunder\Wuunderconnector\Controller\Index;

use \Wuunder\Wuunderconnector\Helper\Data;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as checkoutSession;
use \Wuunder\Wuunderconnector\Model\QuoteIdFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Quote\Model\QuoteIdMaskFactory;

class Parcelshop extends \Magento\Framework\App\Action\Action
{
    protected $logger;

    protected $scopeConfig;

    protected $HelperBackend;

    protected $checkoutSession;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $QuoteId;

    protected $quoteIdMaskFactory;

    public function __construct(
        \Magento\Backend\Helper\Data $HelperBackend,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Customer\Model\Session $checkoutSession,
        Data $helper,
        \Wuunder\Wuunderconnector\Model\QuoteIdFactory $QuoteId,
        Context $context,
        QuoteIdMaskFactory $quoteIdMaskFactory
    )
    {
        $this->HelperBackend = $HelperBackend;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->checkoutSession = $checkoutSession;
        $this->helper = $helper;
        $this->resultRedirect = $context->getResultFactory();
        $this->QuoteId = $QuoteId;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
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
        $quoteId = $post['quoteId'];
        if (!is_numeric($quoteId)) {
            $quoteIdMask = $this->quoteIdMaskFactory->create()->load($quoteId, 'masked_id');
            $quoteId = $quoteIdMask->getQuoteId();
        }
        if (null !== $this->getRequest()->getParam('setParcelshopId')) {
            $parcelshopId = $post['parcelshopId'];
            $this->checkIfQuoteExists($parcelshopId, $quoteId);
            $this->setParcelshopId($parcelshopId);
        }
        if (null !== $this->getRequest()->getParam('refreshParcelshopAddress')) {
            $this->getParcelshopAddressForQuote($quoteId);
        }
    }

    private function setParcelshopId($id)
    {
        if ($id) {
            $address = $this->getParcelshopAddress($id);

            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(\Zend_Json::encode($address));
            $this->getResponse()->sendResponse();
        } else {
            $this->getResponse()->setHeader('Content-type', 'application/json');
            $this->getResponse()->setBody(\Zend_Json::encode(null));
            $this->getResponse()->sendResponse();
        }
    }

    private function getParcelshopAddress($id)
    {
        if (empty($id)) {
            return null;
        } else {
            $test_mode = $this->scopeConfig->getValue(
                'wuunder_wuunderconnector/general/testmode'
            );

            if ($test_mode == 1) {
                $apiKey = $this->scopeConfig->getValue(
                    'wuunder_wuunderconnector/general/api_key_test'
                );
            } else {
                $apiKey = $this->scopeConfig->getValue(
                    'wuunder_wuunderconnector/general/api_key_live'
                );
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
                return null;
            }
            return $parcelshop;
        }
    }

    private function initQuoteIdObject()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $tableName = $resource->getTableName('wuunder_quote_id');
        $initVariables = array(
            'connection' => $connection,
            'tableName' => $tableName
        );
        return $initVariables;
    }

    private function checkIfQuoteExists($parcelshopId, $quoteId)
    {
        $initVariables = $this->initQuoteIdObject();
        //Check if current quote is already in database
        $sql = $initVariables['connection']->select()->from($initVariables['tableName'])->where("quote_id = ?", $quoteId);
        if ($result = $initVariables['connection']->fetchAll($sql)) {
            $this->updateParcelshopId($parcelshopId, $quoteId);
        } else {
            $this->saveParcelshopId($parcelshopId, $quoteId);
        }
    }

    private function saveParcelshopId($parcelshopId, $quoteId)
    {
        $model = $this->QuoteId->create();
        $model->addData(
            [
                "quote_id" => $quoteId,
                "parcelshop_id" => $parcelshopId,
            ]
        );
        $model->save();
    }

    private function updateParcelshopId($parcelshopId, $quoteId)
    {
        $initVariables = $this->initQuoteIdObject();
        $sql = $initVariables['connection']->update($initVariables['tableName'], ['parcelshop_id' => $parcelshopId], ['quote_id = ?' => $quoteId]);
        $initVariables['connection']->query($sql);
    }

    private function getParcelshopAddressForQuote($quoteId)
    {
        $address = null;
        $initVariables = $this->initQuoteIdObject();
        $sql = $initVariables['connection']->select('parcelshop_id')->from($initVariables['tableName'])->where('quote_id = ?', $quoteId);
        if ($result = $initVariables['connection']->fetchAll($sql)) {
            $address = $this->getParcelshopAddress($result[0]["parcelshop_id"]);
        }

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(\Zend_Json::encode($address));
        $this->getResponse()->sendResponse();
    }
}