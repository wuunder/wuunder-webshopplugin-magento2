<?php

namespace Wuunder\Wuunderconnector\Model;

use Magento\Sales\Api\Data\OrderInterface;
use Wuunder\Wuunderconnector\Api\BookingConfigBuilderInterface;
use Wuunder\Wuunderconnector\Api\ShipmentConfigBuilderInterface;
use Wuunder\Wuunderconnector\Exception\InvalidAddressException;

class ShipmentConfigBuilder implements ShipmentConfigBuilderInterface
{
    /**
     * @var \Wuunder\Api\Config\BookingConfigFactory
     */
    private $bookingConfigFactory;

    /**
     * @var OrderInfoFactory
     */
    private $orderInfoFactory;

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    private $metadata;

    /**
     * @var \Wuunder\Wuunderconnector\Helper\Config
     */
    private $config;

    /**
     * @var ResourceModel\QuoteId
     */
    private $quoteIdResource;

    /**
     * @var QuoteIdFactory
     */
    private $quoteIdFactory;

    /**
     * @var \Wuunder\Api\Config\ShipmentConfigFactory
     */
    private $shipmentConfigFactory;

    /**
     * @var \Wuunder\Api\Config\AddressConfigFactory
     */
    private $addressConfigFactory;

    public function __construct(
        \Wuunder\Api\Config\ShipmentConfigFactory $shipmentConfigFactory,
        \Wuunder\Api\Config\AddressConfigFactory $addressConfigFactory,
        \Wuunder\Wuunderconnector\Model\OrderInfoFactory $orderInfoFactory,
        \Wuunder\Wuunderconnector\Helper\Config $config,
        \Wuunder\Wuunderconnector\Model\ResourceModel\QuoteId $quoteIdResource,
        \Wuunder\Wuunderconnector\Model\QuoteIdFactory $quoteIdFactory,
        \Magento\Framework\App\ProductMetadataInterface $metadata
    ) {
        $this->shipmentConfigFactory = $shipmentConfigFactory;
        $this->addressConfigFactory = $addressConfigFactory;
        $this->orderInfoFactory = $orderInfoFactory;
        $this->metadata = $metadata;
        $this->config = $config;
        $this->quoteIdResource = $quoteIdResource;
        $this->quoteIdFactory = $quoteIdFactory;
    }

    /**
     * @param OrderInterface $order
     * @return \Wuunder\Api\Config\BookingConfig
     * @throws InvalidAddressException
     */
    public function build(OrderInterface $order): \Wuunder\Api\Config\ShipmentConfig
    {
        /** @var \Wuunder\Wuunderconnector\Model\OrderInfo $orderinfo */
        $orderinfo = $this->orderInfoFactory->create()
                                            ->setOrder($order);

        /** @var \Wuunder\Api\Config\ShipmentConfig $shipmentConfig */
        $shipmentConfig = $this->shipmentConfigFactory->create();

        //default fields:
        $shipmentConfig->setPersonalMessage($orderinfo->getPersonalMessage());
        $shipmentConfig->setPicture(null);
        $shipmentConfig->setCustomerReference($order->getIncrementId());

        //required fields:
        $shipmentConfig->setDescription($orderinfo->getShippingDescription());
        $shipmentConfig->setValue($order->getSubtotal());
        $shipmentConfig->setKind('package'); //One of "document", "package" or "pallet"
        $shipmentConfig->setLength(0);
        $shipmentConfig->setWidth(0);
        $shipmentConfig->setHeight(0);
        $shipmentConfig->setWeight(1000);
        $shipmentConfig->setDeliveryAddress($this->getDeliveryAddress($order));
        $shipmentConfig->setPickupAddress($this->getPickupAddress());
        $shipmentConfig->setPreferredServiceLevel($orderinfo->getServiceLevel());

        return $shipmentConfig;
    }

    /**
     * @param OrderInterface $order
     * @return \Wuunder\Api\Config\AddressConfig
     * @throws InvalidAddressException
     */
    private function getDeliveryAddress(OrderInterface $order): \Wuunder\Api\Config\AddressConfig
    {
        $shippingAddress = $order->getShippingAddress();

        if ($order->getCustomerEmail() !== '') {
            $email = $order->getCustomerEmail();
        } else {
            $email = $shippingAddress->getEmail();
        }

        $deliveryAddress = $this->addressConfigFactory->create();
        $deliveryAddress->setBusiness($shippingAddress->getCompany());
        $deliveryAddress->setEmailAddress($email);
        $deliveryAddress->setFamilyName($shippingAddress->getLastname());
        $deliveryAddress->setGivenName($shippingAddress->getFirstname());
        $deliveryAddress->setLocality($shippingAddress->getCity());
        $deliveryAddress->setStreetName($shippingAddress->getStreetLine(1));
        $deliveryAddress->setHouseNumber($shippingAddress->getStreetLine(2));
        $deliveryAddress->setZipCode($shippingAddress->getPostcode());
        $deliveryAddress->setPhoneNumber($shippingAddress->getTelephone());
        $deliveryAddress->setCountry($shippingAddress->getCountryId());


        if (!$deliveryAddress->validate()) {
            throw new InvalidAddressException(__("Invalid delivery address. There are mistakes or missing fields."));
        }

        return $deliveryAddress;
    }

    /**
     * @return \Wuunder\Api\Config\AddressConfig
     * @throws InvalidAddressException
     */
    private function getPickupAddress(): \Wuunder\Api\Config\AddressConfig
    {
        $pickupAddress = $this->addressConfigFactory->create();
        $pickupAddress->setEmailAddress($this->config->getEmail());
        $pickupAddress->setFamilyName($this->config->getLastName());
        $pickupAddress->setGivenName($this->config->getFirstName());
        $pickupAddress->setLocality($this->config->getCity());
        $pickupAddress->setStreetName($this->config->getStreetName());
        $pickupAddress->setHouseNumber($this->config->getHouseNumber());
        $pickupAddress->setZipCode($this->config->getZipCode());
        $pickupAddress->setPhoneNumber($this->config->getPhoneNumber());
        $pickupAddress->setCountry($this->config->getCountry());
        $pickupAddress->setBusiness($this->config->getCompany());

        if (!$pickupAddress->validate()) {
            throw new InvalidAddressException(__("Invalid delivery address. There are mistakes or missing fields."));
        }

        return $pickupAddress;
    }

}
