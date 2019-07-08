<?php

namespace Vendor\ModuleName\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup,
                            ModuleContextInterface $context)
    {
        $setup->startSetup();
//        if (version_compare($context->getVersion(), '2.1.5') < 0) {

            $orderTable = 'sales_order';

            //Order table
            $setup->getConnection()
                ->addColumn(
                    $setup->getTable($orderTable),
                    'wuunder_parcelshop_id',
                    [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                        'length' => 255,
                        'comment' => 'Custom Attribute'
                    ]
                );
//        }

        $setup->endSetup();
    }
}