<?php

namespace Wuunder\Wuunderconnector\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Wuunder\Wuunderconnector\Api\WuunderShipmentRepositoryInterface;

class WuunderActions extends Column
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

                if ($this->wuunderShipmentRepository->hasLabelForOrderId($orderId)) {
                    if ($labelUrl = $this->wuunderShipmentRepository->getLabelUrlByOrderId($orderId)) {
                        $item[$this->getName()]['shipping_label_print'] = [
                            'href'   => $labelUrl,
                            'target' => '_BLANK',
                            'label'  => __('Print label')
                        ];
                    }
                    if ($ttUrl = $this->wuunderShipmentRepository->getTrackAndTraceUrlByOrderId($orderId)) {
                        $item[$this->getName()]['shipping_label_track'] = [
                            'href'   => $ttUrl,
                            'target' => '_BLANK',
                            'label'  => __('View Track and Trace')
                        ];
                    }
                } elseif ($error = $this->wuunderShipmentRepository->getErrorByOrderId($orderId)) {
                    $item[$this->getName()]['shipping_label_create'] = [
                        'href' => $this->urlBuilder->getUrl(
                            'wuunder/index/label',
                            ["orderId" => $orderId]
                        ),
                        'label' => __('Manual retry')
                    ];
                } else {
                    $item[$this->getName()]['shipping_label_create'] = [
                        'href' => $this->urlBuilder->getUrl(
                            'wuunder/index/label',
                            ["orderId" => $orderId]
                        ),
                        'label' => __('Create label')
                    ];
                }

            }
        }
        return $dataSource;
    }
}
