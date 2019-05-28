<?php

namespace Wuunder\Wuunderconnector\Api;

use Magento\Framework;
use Magento\Directory;

interface WuunderShipmentRepositoryInterface
{
    /**
     * @param int $shipmentId
     * @return \Wuunder\Wuunderconnector\Model\WuunderShipment
     */
    public function get(int $shipmentId): \Wuunder\Wuunderconnector\Model\WuunderShipment;

    /**
     * @param int $orderId
     * @return \Wuunder\Wuunderconnector\Model\WuunderShipment
     */
    public function getByOrderId(int $orderId): \Wuunder\Wuunderconnector\Model\WuunderShipment;

    /**
     * @param int $orderId
     * @return bool
     */
    public function hasLabelForOrderId(int $orderId): bool;

    /**
     * @param int $orderId
     * @return string
     */
    public function getLabelUrlByOrderId(int $orderId): ?string;

    /**
     * @param int $orderId
     * @return string
     */
    public function getTrackAndTraceUrlByOrderId(int $orderId): ?string;

    /**
     * @param int $orderId
     * @return string|null
     */
    public function getErrorByOrderId(int $orderId): ?string;

    /**
     * @param \Wuunder\Wuunderconnector\Model\WuunderShipment $wuunderShipment
     *
     * @return \Wuunder\Wuunderconnector\Model\WuunderShipment
     * @throws Framework\Exception\AlreadyExistsException
     * @throws Framework\Exception\NoSuchEntityException
     */
    public function save(
        \Wuunder\Wuunderconnector\Model\WuunderShipment $wuunderShipment
    ): \Wuunder\Wuunderconnector\Model\WuunderShipment;
}
