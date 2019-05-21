<?php

namespace Wuunder\Wuunderconnector\Model;

use Magento\Sales\Api\Data\OrderInterface;
use Wuunder\Wuunderconnector\Api\BookingConfigBuilderInterface;
use Wuunder\Wuunderconnector\Exception\InvalidAddressException;

class BookingConfigBuilder implements BookingConfigBuilderInterface
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

    public function __construct(
        \Wuunder\Api\Config\BookingConfigFactory $bookingConfigFactory,
        \Wuunder\Api\Config\AddressConfigFactory $addressConfigFactory,
        \Wuunder\Wuunderconnector\Model\OrderInfoFactory $orderInfoFactory,
        \Wuunder\Wuunderconnector\Helper\Config $config,
        \Wuunder\Wuunderconnector\Model\ResourceModel\QuoteId $quoteIdResource,
        \Wuunder\Wuunderconnector\Model\QuoteIdFactory $quoteIdFactory,
        \Magento\Framework\App\ProductMetadataInterface $metadata
    ) {
        $this->bookingConfigFactory = $bookingConfigFactory;
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
    public function build(OrderInterface $order): \Wuunder\Api\Config\BookingConfig
    {
        /** @var \Wuunder\Wuunderconnector\Model\OrderInfo $orderinfo */
        $orderinfo = $this->orderInfoFactory->create()
                                            ->setOrder($order);

        $bookingConfig = $this->bookingConfigFactory->create();
        $bookingConfig->setDescription($orderinfo->getShippingDescription());
        $bookingConfig->setPersonalMessage($orderinfo->getPersonalMessage());
        $bookingConfig->setPicture(null);
        $bookingConfig->setCustomerReference($order->getIncrementId());
        $bookingConfig->setPreferredServiceLevel($orderinfo->getServiceLevel());
        $bookingConfig->setSource($this->getSourceInfo());
        $bookingConfig->setDeliveryAddress($this->getDeliveryAddress($order));
        $bookingConfig->setPickupAddress($this->getPickupAddress());
        //add parcelshopid to bookingconfig
        $parcelShopId = $this->getParcelShopIdFromQuote($order->getQuoteId());
        if ($parcelShopId !== null) {
            $bookingConfig->setParcelshopId($parcelShopId);
        }
        $bookingConfig->setWebhookUrl($this->config->getWebhookUrl($order->getId()));
        $bookingConfig->setRedirectUrl('');
        return $bookingConfig;
    }

    /**
     * @param int $quoteId
     * @return int|null
     */
    private function getParcelShopIdFromQuote(int $quoteId): ?int
    {
        $parcelshopId = $this->quoteIdFactory->create();
        $this->quoteIdResource->load(
            $parcelshopId,
            $quoteId,
            'quote_id'
        );
        return $parcelshopId->getParcelshopId();
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

    /**
     * @return array
     */
    private function getSourceInfo(): array
    {
        return [
            "product" => "Magento 2 extension",
            "version" => [
                "build" => "2.1.1",
                "plugin" => "2.1"
            ],
            "platform" => [
                "name" => "Magento",
                "build" => $this->metadata->getVersion()
            ]
        ];
    }

}
