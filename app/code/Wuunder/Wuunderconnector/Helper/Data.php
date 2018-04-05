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

  public function curlRequest($wuunderData, $apiUrl, $apiKey)
  {
    // Encode variables
    $json = json_encode($wuunderData);
    // Setup API connection
    $cc = curl_init($apiUrl);
    $this->log("API connection established");

    curl_setopt($cc, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $apiKey, 'Content-type: application/json'));
    curl_setopt($cc, CURLOPT_POST, 1);
    curl_setopt($cc, CURLOPT_POSTFIELDS, $json);
    curl_setopt($cc, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($cc, CURLOPT_VERBOSE, 1);
    curl_setopt($cc, CURLOPT_HEADER, 1);

    // Don't log base64 image string
    $wuunderData['picture'] = 'base64 string removed for logging';

    // Execute the cURL, fetch the XML
    // $curlReturn = array();
    $result = curl_exec($cc);
    $header_size = curl_getinfo($cc, CURLINFO_HEADER_SIZE);
    $header = substr($result, 0, $header_size);
    preg_match("!\r\n(?:Location|URI): *(.*?) *\r\n!i", $header, $matches);
    $redirect_url = $matches[1];
    $this->log('API response string: ' . $result);
    // Close connection
    curl_close($cc);

    return $redirect_url;
  }

}
?>
