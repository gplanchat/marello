<?php

namespace Marello\Bundle\PurchaseOrderBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class MarelloPurchaseOrderBundleInstaller implements
    Installation,
    ActivityExtensionAwareInterface
{
    /** @var ActivityExtension */
    protected $activityExtension;

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_2_3';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createMarelloPurchaseOrderTable($schema);
        $this->createMarelloPurchaseOrderItemTable($schema);

        /** Foreign keys generation **/
        $this->addMarelloPurchaseOrderForeignKeys($schema);
        $this->addMarelloPurchaseOrderItemForeignKeys($schema);
    }

    /**
     * Create marello_purchase_order table
     *
     * @param Schema $schema
     */
    protected function createMarelloPurchaseOrderTable(Schema $schema)
    {
        $table = $schema->createTable('marello_purchase_order');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('supplier_id', 'integer', ['notnull' => true]);
        $table->addColumn('organization_id', 'integer', []);
        $table->addColumn('purchase_order_number', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('due_date', 'datetime', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime', ['notnull' => false]);
        $table->setPrimaryKey(['id']);

        $this->activityExtension->addActivityAssociation($schema, 'oro_note', $table->getName());
        $this->activityExtension->addActivityAssociation($schema, 'marello_notification', $table->getName());
    }

    /**
     * Create marello_purchase_order_item table
     *
     * @param Schema $schema
     */
    protected function createMarelloPurchaseOrderItemTable(Schema $schema)
    {
        $table = $schema->createTable('marello_purchase_order_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('order_id', 'integer', ['notnull' => false]);
        $table->addColumn('product_sku', 'string', ['length' => 255]);
        $table->addColumn('product_name', 'string', ['length' => 255]);
        $table->addColumn('supplier', 'string', ['length' => 255]);
        $table->addColumn('ordered_amount', 'integer', []);
        $table->addColumn('received_amount', 'integer', []);
        $table->addColumn('data', 'json_array', ['notnull' => false, 'comment' => '(DC2Type:json_array)']);
        $table->addColumn('status', 'string', ['length' => 255]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add marello_purchase_order foreign keys.
     *
     * @param Schema $schema
     */
    protected function addMarelloPurchaseOrderForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('marello_purchase_order');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('marello_supplier_supplier'),
            ['supplier_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add marello_purchase_order_item foreign keys.
     *
     * @param Schema $schema
     */
    protected function addMarelloPurchaseOrderItemForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('marello_purchase_order_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('marello_product_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('marello_purchase_order'),
            ['order_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Sets the ActivityExtension
     *
     * @param ActivityExtension $activityExtension
     */
    public function setActivityExtension(ActivityExtension $activityExtension)
    {
        $this->activityExtension = $activityExtension;
    }
}
