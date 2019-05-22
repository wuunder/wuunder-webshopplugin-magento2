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
    protected $scopeConfig;

    public function __construct(UrlInterface $urlBuilder, ContextInterface $context, UiComponentFactory $uiComponentFactory, \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, OrderRepositoryInterface $orderRepository, SearchCriteriaBuilder $criteria, array $components = [], array $data = [])
    {
        $this->_orderRepository = $orderRepository;
        $this->_searchCriteria = $criteria;
        $this->_urlBuilder = $urlBuilder;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if ($this->scopeConfig->getValue('wuunder_wuunderconnector/general/enable') == 1 
            && isset($dataSource['data']['items'])
        ) {
            foreach ($dataSource['data']['items'] as & $item) {
                $item[$this->getData('name')] = $this->renderIcons($item);
            }
        }

        return $dataSource;
    }

    private function renderIcons($item)
    {
        $orderId = $item['entity_id'];
        $icons = '';
        if (isset($item['label_id']) && !is_null($item['label_id'])) {
            $icons = '<li class="wuunder-label-download"><a href="' . $item['label_url'] . 
                '"  target="_blank" title="Print verzendlabel"></a></li>';
            $icons .= '<li class="wuunder-label-tracktrace"><a href="' . $item['tt_url'] .
                '"  target="_blank" title="Bekijk Track&Trace info"></a></li>';
        } else if (!empty($item['booking_url'])) {
            if (strpos($item['booking_url'], 'http:') === 0 
                || strpos($item['booking_url'], 'https:') === 0
            ) {
                $booking_url = $item['booking_url'];
            } else {
                $testMode = 1;
                if ($testMode == 1) {
                    $booking_url = 'https://api-staging.wearewuunder.com' . $item['booking_url'];
                } else {
                    $booking_url = 'https://api.wearewuunder.com' . $item['booking_url'];
                }
            }
            $icons = '<li class="wuunder-label-create"><a href="' . $booking_url .
                '" title="Verzendlabel aanmaken"></a></li>';
        } else {
            $icons = '<li class="wuunder-label-create"><a href="' .
            $this->_urlBuilder->getUrl(
                'wuunder/index/label', ["orderId" => $orderId]
            ) . '" title="Verzendlabel aanmaken"></a></li>';
        }

        if ($icons != '') {
            $icons = '<div class="wuunder-icons"><ul>' . $icons . '</ul></div>';
        }

        return $icons;
    }
}
