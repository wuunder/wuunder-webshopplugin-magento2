<?php

namespace Wuunder\Wuunderconnector\Controller\Index;

class Parcelshop extends \Magento\Framework\App\Action\Action
{
    /**
     * Parcelshop action
     *
     * @return void
     */
    public function execute()
    {
        $post = $this->getRequest()->getPostValue();
        if (isset($_POST['getAddress'])) {
            $this->getCheckoutAddress();
        }

        if (isset($_POST['setParcelshopId'])) {
            $this->setParcelshopId();
        }
    }

    private function getCheckoutAddress()
    {
        $addressId = $_REQUEST['addressId'];
        $address = new Address((int) $addressId);
        header('Content-Type: application/json');
        die(json_encode($address));
    }

    private function setParcelshopId()
    {
        if (Tools::getValue('parcelshopId')) {
            $parcelshopId = Tools::getValue('parcelshopId');
            $this->context->cookie->parcelId = $parcelshopId;
            $address = $this->getParcelshopAddress($parcelshopId);
            $encodedAddress = json_encode($address);
            $this->context->cookie->parcelAddress = $encodedAddress;
            die($encodedAddress);
        }
        return null;
    }

    private function getParcelshopAddress($id)
    {
        if (empty($id)) {
            echo null;
        } else {
            $test_mode = $this->scopeConfig->getValue('wuunder_wuunderconnector/general/testmode');

            if ($test_mode == 1) {
                $apiKey = $this->scopeConfig->getValue('wuunder_wuunderconnector/general/api_key_test');
            } else {
                $apiKey = $this->scopeConfig->getValue('wuunder_wuunderconnector/general/api_key_live');
            }

            $connector = new Wuunder\Connector($apiKey);
            $connector->setLanguage("NL");
            $parcelshopRequest = $connector->getParcelshopById();
            $parcelshopConfig = new \Wuunder\Api\Config\ParcelshopConfig();

            $parcelshopConfig->setId($id);

            if ($parcelshopConfig->validate()) {
                $parcelshopRequest->setConfig($parcelshopConfig);
                if ($parcelshopRequest->fire()) {
                    $parcelshop = $parcelshopRequest->getParcelshopResponse()->getParcelshopData();
                } else {
                    echo 'error';
                    var_dump($parcelshopRequest->getParcelshopResponse()->getError());
                }
            } else {
                $parcelshop = "ParcelshopsConfig not complete";
            }
            return $parcelshop;
        }

        return null;
    }
}