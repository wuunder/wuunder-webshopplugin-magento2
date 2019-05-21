<?php

namespace Wuunder\Wuunderconnector\Helper;


class Api {

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * @return string
     */
    public function getApiKey(): string {
        return $this->scopeConfig->getValue(
            'wuunder_wuunderconnector/general/' . ($this->testMode() ? 'api_key_test' : 'api_key_live'),
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()
        );
    }

    public function testMode() {
        return $this->scopeConfig->getValue(
            'wuunder_wuunderconnector/general/testmode',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()
        ) == 1;
    }

}
