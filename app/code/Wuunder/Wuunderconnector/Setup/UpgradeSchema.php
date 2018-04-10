<?php
namespace Wuunder\Wuunderconnector\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
class UpgradeSchema implements  UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context){

        $setup->startSetup();
        if (version_compare($context->getVersion(), '1.1.0') < 0) {

            // Get module table
            $tableName = $setup->getTable('wuunder_shipment');

            // Check if the table already exists
            if ($setup->getConnection()->isTableExists($tableName) == true) {
                // Declare data
                $name = 'boxes_order';
                $definition = array('type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                                   'nullable' => true,
                                   'comment' => 'All the boxes ordered');

                $connection = $setup->getConnection();
                $connection->addColumn($tableName, $name, $definition);

            }
        }

        $setup->endSetup();
    }
}

 ?>
