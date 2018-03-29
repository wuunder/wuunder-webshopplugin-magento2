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

      // if ($isError === true && !$this->isExceptionLoggingEnabled() && !$forced) {
      //     return $this;
      // } elseif ($isError !== true && !$this->isLoggingEnabled() && !$forced) {
      //     return $this;
      // }
      //
      // if (is_null($level)) {
      //     $level = Zend_Log::DEBUG;
      // }
      //
      // if (is_null($file)) {
      //     $file = static::WUUNERCONNECTOR_LOG_FILE;
      // }
      //
      // Mage::log($message, $level, $file, $forced);
      //
      // return $this;

      if($this->isLoggingEnabled()) {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/wuunder.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info($message);
      }
  }

  public function isLoggingEnabled()
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

  public function getDebugMode()
  {
      $debug = $this->scopeConfig->getValue('wuunder_wuunderconnector/debugging/debugging', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
      if ($debug !== null) {
          return $debug;
      }
  }

}
?>
