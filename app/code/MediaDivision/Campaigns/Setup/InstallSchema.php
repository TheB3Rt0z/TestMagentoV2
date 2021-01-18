<?php

namespace MediaDivision\Campaigns\Setup;

class InstallSchema implements \Magento\Framework\Setup\InstallSchemaInterface
{

    public function install(\Magento\Framework\Setup\SchemaSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();


        if (!$installer->tableExists('md_campaign')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('md_campaign')
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
                    'code',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    80,
                    ['nullable' => false],
                    'Code'
                )
                ->addColumn(
                    'created_at',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                    'Created At'
                )
                ->addColumn(
                    'description',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    ['nullable' => false],
                    'Description'
                )
                ->addColumn(
                    'is_active',
                    \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                    null,
                    ['identity' => false, 'nullable' => false, 'primary' => true],
                    'Active'
                );
            $installer->getConnection()->createTable($table);
        }

        $installer->getConnection()->addColumn(
            $installer->getTable('salesrule'),
            'campaign',
            [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    'nullable' => true,
                    'comment' => 'Campaign'
                ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'campaign',
            [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    'nullable' => true,
                    'comment' => 'Campaign'
                ]
        );

        $installer->endSetup();
    }
}
