<?php

namespace Wuunder\Wuunderconnector\Plugin\Api;

use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;


class OrderRepository
{

    const PARCELSHOP_ID = 'parcelshop_id';

    /**
     * Order Extension Attributes Factory
     *
     * @var OrderExtensionFactory
     */
    protected $extensionFactory;

    /**
     * OrderRepositoryPlugin constructor
     *
     * @param OrderExtensionFactory $extensionFactory
     */
    public function __construct(OrderExtensionFactory $extensionFactory)
    {
        $this->extensionFactory = $extensionFactory;
    }

    /**
     * Add "delivery_type" extension attribute to order data object to make it accessible in API data
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $order
     *
     * @return OrderInterface
     */
    public function afterGet(OrderRepositoryInterface $subject, OrderInterface $order)
    {
        $quoteId = $order->getQuoteId();
        $parcelShopId = $this->getParcelshopIdByQuoteId($quoteId);
        $extensionAttributes = $order->getExtensionAttributes();
        $extensionAttributes = $extensionAttributes ? $extensionAttributes : $this->extensionFactory->create();
        $extensionAttributes->setWuunderParcelshopId($parcelShopId);
        $order->setExtensionAttributes($extensionAttributes);

        return $order;
    }

    /**
     * Add "delivery_type" extension attribute to order data object to make it accessible in API data
     *
     * @param OrderRepositoryInterface $subject
     * @param OrderSearchResultInterface $searchResult
     *
     * @return OrderSearchResultInterface
     */
    public function afterGetList(OrderRepositoryInterface $subject, OrderSearchResultInterface $searchResult)
    {
        $orders = $searchResult->getItems();

        foreach ($orders as &$order) {
            $quoteId = $order->getQuoteId();
            $parcelShopId = $this->getParcelshopIdByQuoteId($quoteId);
            $extensionAttributes = $order->getExtensionAttributes();
            $extensionAttributes = $extensionAttributes ? $extensionAttributes : $this->extensionFactory->create();
            $extensionAttributes->setWuunderParcelshopId($parcelShopId);
            $order->setExtensionAttributes($extensionAttributes);
        }

        return $searchResult;
    }

    private function getParcelshopIdByQuoteId($quoteId) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $tableName = $resource->getTableName('wuunder_quote_id');

        $sql = "SELECT parcelshop_id 
                    FROM " . $tableName
            ." WHERE quote_id =" . $quoteId;
        if ($result = $connection->fetchAll($sql)) {
            return $result[0]["parcelshop_id"];
        }

        return null;
    }
}