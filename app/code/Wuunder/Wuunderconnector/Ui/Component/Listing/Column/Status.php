<?php

namespace Wuunder\Wuunderconnector\Ui\Component\Listing\Column;

use \Magento\Sales\Api\OrderRepositoryInterface;
use \Magento\Framework\View\Element\UiComponent\ContextInterface;
use \Magento\Framework\View\Element\UiComponentFactory;
use \Magento\Ui\Component\Listing\Columns\Column;
use \Magento\Framework\Api\SearchCriteriaBuilder;

class Status extends Column
{
    protected $_orderRepository;
    protected $_searchCriteria;

    public function __construct(ContextInterface $context, UiComponentFactory $uiComponentFactory, OrderRepositoryInterface $orderRepository, SearchCriteriaBuilder $criteria, array $components = [], array $data = [])
    {
        $this->_orderRepository = $orderRepository;
        $this->_searchCriteria = $criteria;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {

//                $order = $this->_orderRepository->get($item["entity_id"]);
//                $status = $order->getData("export_status");
//
//                switch ($status) {
//                    case "0":
//                        $export_status = "No";
//                        break;
//                    case "1";
//                        $export_status = "Yes";
//                        break;
//                    default:
//                        $export_status = "Failed";
//                        break;
//
//                }

// $this->getData('name') returns the name of the column so in this case it would return export_status
                $item[$this->getData('name')] = $this->renderIcons($item);
            }
        }

        return $dataSource;
    }

    private function renderIcons($item)
    {
        $orderId    = $item->getData('entity_id');
        $order = Mage::getModel('sales/order')->load($orderId);
        $shipping_method = $order->getShippingMethod();
        $icons = '';
        if (in_array($shipping_method, explode(",", Mage::getStoreConfig('wuunderconnector/connect/wuunder_enabled_shipping_methods'))) ||
            in_array("wuunder_default_all_selected", explode(",", Mage::getStoreConfig('wuunderconnector/connect/wuunder_enabled_shipping_methods')))) {
            if (!empty($item->getData('label_id'))) {
                $icons = '<li class="wuunder-label-download"><a href="' . $item->getData('label_url') . '"  target="_blank" title="Print verzendlabel"></a></li>';
            } else if (!empty($item->getData('booking_url'))) {
                if (strpos($item->getData('booking_url'), 'http:') === 0 || strpos($item->getData('booking_url'), 'https:') === 0) {
                    $booking_url = $item->getData('booking_url');
                } else {
                    $storeId = $order->getStoreId();
                    $testMode = Mage::getStoreConfig('wuunderconnector/connect/testmode', $storeId);
                    if ($testMode == 1) {
                        $booking_url = 'https://api-staging.wuunder.co' . $item->getData('booking_url');
                    } else {
                        $booking_url = 'https://api.wuunder.co' . $item->getData('booking_url');
                    }
                }
                $icons = '<li class="wuunder-label-create"><a href="' . $booking_url . '" title="Verzendlabel aanmaken"></a></li>';
            } else {
                $icons = '<li class="wuunder-label-create"><a href="' . $this->getUrl('adminhtml/wuunder/processLabel', array('id' => $orderId)) . '" title="Verzendlabel aanmaken"></a></li>';
            }
        }

        if ($icons != '') {
            $icons = '<div class="wuunder-icons"><ul>' . $icons . '</ul></div>';
        }

        return $icons;
    }
}