<?php

namespace Wuunder\Wuunderconnector\Api;

interface BookingConfigBuilderInterface
{

    public function build(\Magento\Sales\Api\Data\OrderInterface $order): \Wuunder\Api\Config\BookingConfig;

}
