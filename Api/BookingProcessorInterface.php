<?php

namespace Wuunder\Wuunderconnector\Api;

interface BookingProcessorInterface {

    public function book(\Magento\Sales\Api\Data\OrderInterface $order): void;

}
