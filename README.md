# Wuunder Magento 2 plugin

## Hello, we are [Wuunder](https://wearewuunder.com/) ##
We make shipping any parcel, pallet and document easy, personal and efficient. As a business owner, you can book your shipment using your mobile, tablet or computer. We select the best price and pick up your parcel wherever you want. You and the receiver can both track and trace the shipment. You can also stay in contact with each other via our Wuunder chat. Everything without a contract. Why complicate things?

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
Changelog can be found [here](https://github.com/kabisa/wuunder-webshopplugin-magento2/blob/master/CHANGELOG.md).
