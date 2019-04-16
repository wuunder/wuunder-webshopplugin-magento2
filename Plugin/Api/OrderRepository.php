<?php

namespace Wuunder\Wuunderconnector\Plugin\Api;

use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;


class OrderRepository
{

const PARCELSHOP_ID = 'wuunder_parcelshop_id';

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
$deliveryType = $order->getData(self::PARCELSHOP_ID);
$extensionAttributes = $order->getExtensionAttributes();
$extensionAttributes = $extensionAttributes ? $extensionAttributes : $this->extensionFactory->create();
$extensionAttributes->setWuunderParcelshopId($deliveryType);
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
$deliveryType = $order->getData(self::PARCELSHOP_ID);
$deliveryType = "abc123";
$extensionAttributes = $order->getExtensionAttributes();
$extensionAttributes = $extensionAttributes ? $extensionAttributes : $this->extensionFactory->create();
$extensionAttributes->setWuunderParcelshopId($deliveryType);
$order->setExtensionAttributes($extensionAttributes);
}

return $searchResult;
}
}