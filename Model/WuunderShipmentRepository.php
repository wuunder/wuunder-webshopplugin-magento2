<?php

namespace Wuunder\Wuunderconnector\Model;

use Magento\Framework;
use Psr\Log;

class WuunderShipmentRepository implements \Wuunder\Wuunderconnector\Api\WuunderShipmentRepositoryInterface
{
    /**
     * @var ResourceModel\WuunderShipment
     */
    protected $resource;

    /**
     * @var WuunderShipmentFactory
     */
    protected $factory;

    /**
     * WuunderShipmentRepository constructor.
     * @param ResourceModel\WuunderShipment $resource
     * @param WuunderShipmentFactory        $factory
     */
    public function __construct(
        \Wuunder\Wuunderconnector\Model\ResourceModel\WuunderShipment $resource,
        \Wuunder\Wuunderconnector\Model\WuunderShipmentFactory $factory
    ) {
        $this->resource = $resource;
        $this->factory = $factory;
    }

    /**
     * @param int $shipmentId
     * @return WuunderShipment
     */
    public function get(int $shipmentId): \Wuunder\Wuunderconnector\Model\WuunderShipment
    {
        $object = $this->factory->create();
        $this->resource->load(
            $object,
            $shipmentId,
            'shipment_id'
        );
        return $object;
    }

    /**
     * @param int $orderId
     * @return WuunderShipment
     */
    public function getByOrderId(int $orderId): \Wuunder\Wuunderconnector\Model\WuunderShipment
    {
        $object = $this->factory->create();
        $this->resource->load(
            $object,
            $orderId,
            'order_id'
        );
        return $object;
    }

    /**
     * @param int $orderId
     * @return bool
     */
    public function hasShipmentForOrderId(int $orderId): bool
    {
        return $this->resource->getIdByOrderId($orderId) ? true : false;
    }

    /**
     * @param int $orderId
     * @return bool
     */
    public function hasLabelForOrderId(int $orderId): bool
    {
        return $this->resource->getLabelIdByOrderId($orderId) ? true : false;
    }

    public function getLabelUrlByOrderId(int $orderId): ?string
    {
        return $this->resource->getLabelUrlByOrderId($orderId);
    }

    public function getTrackAndTraceUrlByOrderId(int $orderId): ?string
    {
        return $this->resource->getTrackAndTraceUrlByOrderId($orderId);
    }

    public function getErrorByOrderId(int $orderId): ?string
    {
        return $this->resource->getErrorByOrderId($orderId);
    }

    /**
     * @param WuunderShipment $wuunderShipment
     * @return WuunderShipment
     * @throws Framework\Exception\AlreadyExistsException
     */
    public function save(\Wuunder\Wuunderconnector\Model\WuunderShipment $wuunderShipment): \Wuunder\Wuunderconnector\Model\WuunderShipment
    {
        $this->resource->save($wuunderShipment);

        return $wuunderShipment;
    }

}
