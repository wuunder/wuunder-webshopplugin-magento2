<?php

namespace Wuunder\Wuunderconnector\Helper;
use \Magento\Framework\App\Helper\AbstractHelper;


class Data extends AbstractHelper
{
    public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function log($message)
    {
        if ($this->_isLoggingEnabled()) {
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/wuunder.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info($message);
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
