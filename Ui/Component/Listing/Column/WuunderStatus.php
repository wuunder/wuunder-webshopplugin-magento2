<?php

namespace Wuunder\Wuunderconnector\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\MassAction\Columns\Column;
use Wuunder\Wuunderconnector\Api\WuunderShipmentRepositoryInterface;

class WuunderStatus extends Column
{
    /**
     * @var WuunderShipmentRepositoryInterface
     */
    protected $wuunderShipmentRepository;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        WuunderShipmentRepositoryInterface $wuunderShipmentRepository,
        \Magento\Framework\UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->wuunderShipmentRepository = $wuunderShipmentRepository;
        $this->urlBuilder = $urlBuilder;
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {

                $orderId = (int)$item[\Magento\Sales\Model\Order::ENTITY_ID];

                if ($error = $this->wuunderShipmentRepository->getErrorByOrderId($orderId)) {
                    $item[$this->getName()] = $error;
                }
            }
        }
        return $dataSource;
    }
}
