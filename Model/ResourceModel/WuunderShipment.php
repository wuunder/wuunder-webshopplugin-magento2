<?php

namespace Wuunder\Wuunderconnector\Model\ResourceModel;

class WuunderShipment extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    public function _construct()
    {
        $this->_init('wuunder_shipment', 'shipment_id');
    }

    /**
     * @param int $orderId
     * @return string|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getIdByOrderId(int $orderId): ?string
    {
        $connection = $this->getConnection();

        $select = $connection->select()->from($this->getMainTable(), 'shipment_id')->where('order_id = :order_id');

        $bind = [':order_id' => (string)$orderId];

        return $connection->fetchOne($select, $bind);
    }

    /**
     * @param int $orderId
     * @return string|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getLabelIdByOrderId(int $orderId): ?string
    {
        $connection = $this->getConnection();

        $select = $connection->select()->from($this->getMainTable(), 'label_id')->where('order_id = :order_id');

        $bind = [':order_id' => (string)$orderId];

        return $connection->fetchOne($select, $bind);
    }

    /**
     * @param int $orderId
     * @return string|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getLabelUrlByOrderId(int $orderId): ?string
    {
        $connection = $this->getConnection();

        $select = $connection->select()->from($this->getMainTable(), 'label_url')->where('order_id = :order_id');

        $bind = [':order_id' => (string)$orderId];

        return $connection->fetchOne($select, $bind);
    }

    /**
     * @param int $orderId
     * @return string|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getTrackAndTraceUrlByOrderId(int $orderId): ?string
    {
        $connection = $this->getConnection();

        $select = $connection->select()->from($this->getMainTable(), 'tt_url')->where('order_id = :order_id');

        $bind = [':order_id' => (string)$orderId];

        return $connection->fetchOne($select, $bind);
    }

    /**
     * @param int $orderId
     * @return string|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getErrorByOrderId(int $orderId): ?string
    {
        $connection = $this->getConnection();

        $select = $connection->select()->from($this->getMainTable(), 'auto_booking_error')->where('order_id = :order_id');

        $bind = [':order_id' => (string)$orderId];

        return $connection->fetchOne($select, $bind);
    }

}
