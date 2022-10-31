<?php

namespace Wuunder\Wuunderconnector\Model;

use Magento\Framework\UrlInterface;

class CheckoutConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    private $scopeConfig;

    private $storeManager;

    public function __construct(
        UrlInterface $urlBuilder,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    public function getConfig()
    {
        $test_mode = (int)$this->scopeConfig->getValue(
            'wuunder_wuunderconnector/general/testmode'
        );

        $tmpEnvironment = new \Wuunder\Api\Environment(
            $test_mode === 1 ? 'staging' : 'production'
        );
        $baseApiUrl = substr($tmpEnvironment->getStageBaseUrl(), 0, -3);
        $output['api_base_url'] = $baseApiUrl;
        $output['backend_base_url'] = $this->storeManager->getStore()->getBaseUrl();
        if(empty($this->scopeConfig->getValue('carriers/parcelshopPicker/enabledcarriers'))){
            $output['available_carriers'] = null;
        } else {
            $output['available_carriers'] = str_replace(
                ' ', '', $this->scopeConfig->getValue('carriers/parcelshopPicker/enabledcarriers')
            );
        }
        return $output;
    }
}
