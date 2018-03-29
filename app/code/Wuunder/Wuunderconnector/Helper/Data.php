<?php

namespace Wuunder\Wuunderconnector\Helper;
use \Magento\Framework\App\Helper\AbstractHelper;


class Data extends AbstractHelper{

  public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig)
  {
    $this->scopeConfig = $scopeConfig;
  }

  public function log($message)
  {
      if($this->isLoggingEnabled()) {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/wuunder.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info($message);
      }
  }

  private function isLoggingEnabled()
  {
      // if (version_compare(phpversion(), self::MIN_PHP_VERSION, '<')) {
      //     return false;
      // }

      $debugMode = $this->getDebugMode();
      if ($debugMode > 0)
      {
          return true;
      }

      return false;
  }

  private function getDebugMode()
  {
      $debug = $this->scopeConfig->getValue('wuunder_wuunderconnector/debugging/debugging', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
      if ($debug !== null) {
          return $debug;
      }
  }

}
?>
