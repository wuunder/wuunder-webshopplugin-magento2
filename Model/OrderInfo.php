<?php

namespace Wuunder\Wuunderconnector\Model;

class OrderInfo
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @var \Magento\Sales\Api\Data\OrderInterface
     */
    private $order;

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return $this
     */
    public function setOrder(\Magento\Sales\Api\Data\OrderInterface $order): self
    {
        $this->order = $order;
        return $this;
    }

    /**
     * @return array
     */
    public function getOrderInfo(): array
    {
        return [
            'reference' => $this->order->getId(),
            'description' => $this->getShippingDescription(),
            'personal_message' => '',
            'phone_number' => $this->order->getShippingAddress()->getTelephone(),
        ];
    }

    /**
     * @return string
     */
    public function getShippingDescription(): string
    {
        $result = '';
        foreach ($this->order->getItems() as $item) {
            $result .= $item->getName() . ' ';
        }
        return $result;
    }

    /**
     * @return string
     */
    public function getPersonalMessage(): string
    {
        return '';
    }

    /**
     * @return string|null
     */
    public function getServiceLevel(): ?string
    {
        $preferredServiceLevel = null;
        $usedShippingMethod = $this->order->getShippingMethod();
        for ($i = 1; $i < 5; $i++) {
            if ($this->scopeConfig->getValue('wuunder_wuunderconnector/advanced/filtermapping_' . $i . '_carrier') === $usedShippingMethod) {
                return $this->scopeConfig->getValue(
                    'wuunder_wuunderconnector/advanced/filtermapping_' . $i . '_filter'
                );
            }
        }
        return $preferredServiceLevel;
    }

}
