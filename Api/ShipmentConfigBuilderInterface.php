<?php

namespace Wuunder\Wuunderconnector\Api;

interface ShipmentConfigBuilderInterface
{

    public function build(\Magento\Sales\Api\Data\OrderInterface $order): \Wuunder\Api\Config\ShipmentConfig;

}
