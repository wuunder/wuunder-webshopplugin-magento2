# Wuunder Magento 2 plugin

## Hello, we are [Wuunder](https://wearewuunder.com/) ##
We make shipping any parcel, pallet and document easy, personal and efficient. As a business owner, you can book your shipment using your mobile, tablet or computer. We select the best price and pick up your parcel wherever you want. You and the receiver can both track and trace the shipment. You can also stay in contact with each other via our Wuunder chat. Everything without a contract. Why complicate things?


More info regarding the installation: https://wearewuunder.com/hoe-installeer-ik-de-magento-2-module/

## Before you start ##
* You need to create a free Wuunder account: https://app.wearewuunder.com and request an API-key to use the module: https://wearewuunder.com/en/contact/ 
* You can download and install the module before you sign-up.
* With this module you connect your Magento2 store to your Wuunder account.

## Before you start ##
* You need to create a free Wuunder account: https://app.wearewuunder.com and request an API-key to use the module: https://wearewuunder.com/en/contact/ 
* You can download and install the module before you sign-up.
* With this module you connect your Magento2 store to your Wuunder account.

## Install ##
* Open a terminal in the root folder of your magento2 installation
* Run the following commands:


```
composer require wuunder/magento2-connector 
php bin/magento setup:upgrade 
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
```

* Wuunder is now added to your orders page and is ready for use!

## Changelog ##
Changelog can be found [here](CHANGELOG.md).
