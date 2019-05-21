<?php

namespace Wuunder\Wuunderconnector\Model\Config\Source\Order;

class State implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    private $config;

    public function __construct(
        \Magento\Sales\Model\Order\Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Get options for select
     *
     * @return array
     */
    public function toOptionArray()
    {
        $array = [];
        foreach ($this->config->getStates() as $state => $label) {
            $array[] = [
                'value' => $state,
                'label' => $label
            ];
        }
        return $array;
    }
}
