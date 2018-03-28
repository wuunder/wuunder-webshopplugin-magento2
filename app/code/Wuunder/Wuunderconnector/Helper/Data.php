<?php

namespace Wuunder\Wuunderconnector\Helper;
use Magento\Framework\App\Helper\AbstractHelper;

/*
  ----- To use this function for logging -----
  add: `use \Wuunder\Wuunderconnector\Helper\Data;` to said file.
  add: $helper to the constructor as a parameter, in constructor: $this->helper = $helper;
  !!!!!!!!!!!!! Logging can now be achieved by adding $this->helper->log() MOET DIT NOG DOEN.!!!!!!!!!!!!!

*/

class Data extends AbstractHelper
{
  public function RandomFunc()
  {
    echo "This is the helper in Magento 2";
  }
}




?>
