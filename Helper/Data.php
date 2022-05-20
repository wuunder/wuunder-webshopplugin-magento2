<?php

namespace Wuunder\Wuunderconnector\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Wuunder\Wuunderconnector\Logger\Logger $wuunderLogger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->wuunder_logger = $wuunderLogger;
    }

    public function log($message)

    {
        if ($this->_isLoggingEnabled()) {
            $this->wuunder_logger->error($message);
        }
    }

    private function _isLoggingEnabled()
    {
        $debugMode = $this->_getDebugMode();
        if ($debugMode > 0) {
            return true;
        }

        return false;
    }

    private function _getDebugMode()
    {
        $debug = $this->scopeConfig->getValue(
            'wuunder_wuunderconnector/debugging/debugging',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if ($debug !== null) {
            return $debug;
        }
    }
}
