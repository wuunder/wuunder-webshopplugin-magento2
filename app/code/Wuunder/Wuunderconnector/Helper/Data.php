<?php

namespace Wuunder\Wuunderconnector\Helper;
use \Magento\Framework\App\Helper\AbstractHelper;


class Data extends AbstractHelper{


  function log($message)
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

      $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/wuunder.log');
      $logger = new \Zend\Log\Logger();
      $logger->addWriter($writer);
      $logger->info($message);
  }

  // public function isLoggingEnabled()
  // {
  //     if (version_compare(phpversion(), self::MIN_PHP_VERSION, '<')) {
  //         return false;
  //     }
  //
  //     $debugMode = $this->getDebugMode();
  //     if ($debugMode > 0) {
  //         return true;
  //     }
  //
  //     return false;
  // }
  //
  // public function getDebugMode()
  // {
  //     if (Mage::registry('wuunderconnector_debug_mode') !== null) {
  //         return Mage::registry('wuunderconnector_debug_mode');
  //     }
  //
  //     $debugMode = (int)Mage::getStoreConfig(self::XPATH_DEBUG_MODE, Mage_Core_Model_App::ADMIN_STORE_ID);
  //     Mage::register('wuunderconnector_debug_mode', $debugMode);
  //     return $debugMode;
  // }

}
?>
