<?php

namespace Darsh\Banner\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;
/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();
        if (!$installer->tableExists('banner')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('banner')
            )->addColumn(
                    'banner_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['identity' => true, 'nullable' => false, 'primary' => true],
                    'banner ID'
                )->addColumn(
                    'title',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => false],
                    'Title'
                )->addColumn(
                    'image',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => false],
                    'Image'
                )->addColumn(
                    'store_view',
                    Table::TYPE_VARCHAR,
                    255,
                    [],
                    'Store View'
                )->addColumn(
                    'effect',
                    Table::TYPE_VARCHAR,
                    500,
                    [],
                    'Banner Effect'
                )->addColumn(
                    'is_active',
                    Table::TYPE_SMALLINT,
                    null,
                    [],
                    'Active Status'
                )->addColumn(
                    'order',
                    Table::TYPE_SMALLINT,
                    null,
                    [],
                    'Sorting Order'
                )->addColumn(
                    'url',
                    Table::TYPE_VARCHAR,
                    255,
                    [],
                    'Website Url'
                )->addColumn(
                    'created_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    [],
                    'Creation Time'
                )->addColumn(
                    'update_time',
                    Table::TYPE_TIMESTAMP,
                    null,
                    [],
                    'Modification Time'
                )->setComment(
                    'Banner Table'
                );
            $installer->getConnection()->createTable($table);

        }
        $installer->endSetup();

    }
}
