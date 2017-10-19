<?php

namespace Wuunder\Wuunderconnector\Ui\Component\Listing\Column;

use \Magento\Sales\Api\OrderRepositoryInterface;
use \Magento\Framework\View\Element\UiComponent\ContextInterface;
use \Magento\Framework\View\Element\UiComponentFactory;
use \Magento\Ui\Component\Listing\Columns\Column;
use \Magento\Framework\Api\SearchCriteriaBuilder;
use \Magento\Framework\UrlInterface;

class Status extends Column
{
    protected $_orderRepository;
    protected $_searchCriteria;
    protected $_urlBuilder;

    public function __construct(UrlInterface $urlBuilder, ContextInterface $context, UiComponentFactory $uiComponentFactory, OrderRepositoryInterface $orderRepository, SearchCriteriaBuilder $criteria, array $components = [], array $data = [])
    {
        $this->_orderRepository = $orderRepository;
        $this->_searchCriteria = $criteria;
        $this->_urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $item[$this->getData('name')] = $this->renderIcons($item);
            }
        }

        return $dataSource;
    }

    private function renderIcons($item)
    {
        //<a class="action-menu-item" data-bind="attr: {href: $action().href}, text: $action().label, click: $col.getActionHandler($action())" data-repeat-index="0" href="http://188.226.134.167/magento2/admin/sales/order/view/order_id/1/key/61064304d062385ce5fd0c8804cdd722e4aba3c67585b0535388fa09da162ccf/">View</a>


        $orderId = $item['entity_id'];
//        $order = Mage::getModel('sales/order')->load($orderId);
//        $shipping_method = $order->getShippingMethod();
        $icons = '';
//        if (in_array($shipping_method, explode(",", Mage::getStoreConfig('wuunderconnector/connect/wuunder_enabled_shipping_methods'))) ||
//            in_array("wuunder_default_all_selected", explode(",", Mage::getStoreConfig('wuunderconnector/connect/wuunder_enabled_shipping_methods')))) {
        if (!is_null($item['label_id'])) {
            $icons = '<li class="wuunder-label-download"><a href="' . $item['label_url'] . '"  target="_blank" title="Print verzendlabel"></a></li>';
            $icons .= '<li class="wuunder-label-tracktrace"><a href="' . $item['tt_url'] . '"  target="_blank" title="Bekijk Track&Trace info"></a></li>';
        } else if (!empty($item['booking_url'])) {
            //http://188.226.134.167/magento2/admin/sales/order/index/key/f6ac9a2ab01eabf2ff352450b72bc4dbdd02ff1fc776ca4cf7a78218bf43b5a2/
            if (strpos($item['booking_url'], 'http:') === 0 || strpos($item['booking_url'], 'https:') === 0) {
                $booking_url = $item['booking_url'];
            } else {
//                $storeId = $order->getStoreId();
//                $testMode = Mage::getStoreConfig('wuunderconnector/connect/testmode', $storeId);
                $testMode = 1;
                if ($testMode == 1) {
                    $booking_url = 'https://api-staging.wuunder.co' . $item['booking_url'];
                } else {
                    $booking_url = 'https://api.wuunder.co' . $item['booking_url'];
                }
            }
            $icons = '<li class="wuunder-label-create"><a href="' . $booking_url . '" title="Verzendlabel aanmaken"></a></li>';
        } else {
            $icons = '<li class="wuunder-label-create"><a href="' . $this->_urlBuilder->getUrl('wuunder/index/label', ["orderId" => $orderId]) . '" title="Verzendlabel aanmaken"></a></li>';
        }
//        }

        if ($icons != '') {
            $icons = '<div class="wuunder-icons"><ul>' . $icons . '</ul></div>';
        }

        return $icons;
    }
}