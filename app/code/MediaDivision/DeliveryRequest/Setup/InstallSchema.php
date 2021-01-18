<?php

namespace MediaDivision\DeliveryRequest\Setup;

class InstallSchema implements \Magento\Framework\Setup\InstallSchemaInterface
{

    public function install(\Magento\Framework\Setup\SchemaSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();


        if (!$installer->tableExists('md_delivery_request')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('md_delivery_request')
            )
                ->addColumn(
                    'id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [
                                'identity' => true,
                                'nullable' => false,
                                'primary' => true,
                                'unsigned' => true,
                            ],
                    'ID'
                )
                ->addColumn(
                    'name',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable' => false],
                    'Name'
                );
            $installer->getConnection()->createTable($table);
        }

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'delivery_request',
            [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    'nullable' => true,
                    'comment' => 'Delivery Request'
                ]
        );

        $installer->endSetup();
    }
}
