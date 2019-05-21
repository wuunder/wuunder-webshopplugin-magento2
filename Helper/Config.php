<?php

namespace Wuunder\Wuunderconnector\Helper;

class Config extends \Magento\Framework\App\Helper\AbstractHelper
{

    const XML_PATH_AUTOBOOK_STATE = 'wuunder_wuunderconnector/automaticbooking/auto_booking_state';
    const XML_PATH_AUTOBOOK_ENABLED = 'wuunder_wuunderconnector/automaticbooking/auto_booking_enabled';

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param \Magento\Framework\App\Helper\Context      $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * @return string
     */
    public function getAutobookingState(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_AUTOBOOK_STATE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()
        );
    }

    /**
     * @return bool
     */
    public function isAutobookingEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_AUTOBOOK_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()
        );
    }

    public function getWebhookUrl(int $orderId) {
        return sprintf(
            '%s%s%d',
            $this->storeManager->getStore()->getBaseUrl(),
            'wuunder/index/webhook/order_id/',
            $orderId
        );
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->scopeConfig->getValue('wuunder_wuunderconnector/general/email');
    }

    /**
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->scopeConfig->getValue('wuunder_wuunderconnector/general/lastname');
    }

    /**
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->scopeConfig->getValue('wuunder_wuunderconnector/general/firstname');
    }

    /**
     * @return string|null
     */
    public function getCity(): ?string
    {
        return $this->scopeConfig->getValue('wuunder_wuunderconnector/general/city');
    }

    /**
     * @return string|null
     */
    public function getStreetName(): ?string
    {
        return $this->scopeConfig->getValue('wuunder_wuunderconnector/general/streetname');
    }

    /**
     * @return string|null
     */
    public function getHouseNumber(): ?string
    {
        return $this->scopeConfig->getValue('wuunder_wuunderconnector/general/housenumber');
    }

    /**
     * @return string|null
     */
    public function getZipCode(): ?string
    {
        return $this->scopeConfig->getValue('wuunder_wuunderconnector/general/zipcode');
    }

    /**
     * @return string|null
     */
    public function getPhoneNumber(): ?string
    {
        return $this->scopeConfig->getValue('wuunder_wuunderconnector/general/phone');
    }

    /**
     * @return string|null
     */
    public function getCountry(): ?string
    {
        return $this->scopeConfig->getValue('wuunder_wuunderconnector/general/country');
    }

    /**
     * @return string|null
     */
    public function getCompany(): ?string
    {
        return $this->scopeConfig->getValue('wuunder_wuunderconnector/general/company');
    }
}
