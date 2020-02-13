<?php
namespace Wuunder\Wuunderconnector\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * install tables
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface $setup
     * @param \Magento\Framework\Setup\ModuleContextInterface $context
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        if (!$installer->tableExists('wuunder_shipment')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('wuunder_shipment')
            )
                ->addColumn(
                    'shipment_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'nullable' => false,
                        'primary'  => true,
                        'unsigned' => true,
                    ],
                    'Shipment ID'
                )
                ->addColumn(
                    'order_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['nullable => false'],
                    'Shipment Name'
                )
                ->addColumn(
                    'label_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['nullable => true'],
                    'Shipment label id'
                )
                ->addColumn(
                    'label_url',
                    Table::TYPE_TEXT,
                    '64k',
                    ['nullable => true'],
                    'Shipment label url'
                )
                ->addColumn(
                    'tt_url',
                    Table::TYPE_TEXT,
                    '64k',
                    ['nullable => true'],
                    'Shipment T&T url'
                )
                ->addColumn(
                    'booking_url',
                    Table::TYPE_TEXT,
                    '64k',
                    ['nullable => true'],
                    'Shipment booking url'
                )
                ->addColumn(
                    'booking_token',
                    Table::TYPE_TEXT,
                    '64k',
                    ['nullable => false'],
                    'Shipment booking token'
                )
                ->setComment('Wuunder shipment table');
            $installer->getConnection()->createTable($table);
        }

        if (!$installer->tableExists('wuunder_quote_id')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('wuunder_quote_id')
            )
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'nullable' => false,
                        'primary'  => true,
                        'unsigned' => true,
                    ],
                    'ID'
                )
                ->addColumn(
                    'quote_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['nullable => false'],
                    'Quote Info'
                )
                ->addColumn(
                    'parcelshop_id',
                    Table::TYPE_TEXT,
                    null,
                    ['nullable => true'],
                    'Parcelshop id'
                )
                ->setComment('Wuunder shipment table');
            $installer->getConnection()->createTable($table);

        }

        $orderTable = 'sales_order';

        //Order table
        $setup->getConnection()
            ->addColumn(
                $setup->getTable($orderTable),
                'wuunder_parcelshop_id',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'length' => 255,
                    'comment' =>'Custom Attribute'
                ]
            );

        $setup->endSetup();
    }
}