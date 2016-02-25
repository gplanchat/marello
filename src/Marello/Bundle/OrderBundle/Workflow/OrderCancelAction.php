<?php

namespace Marello\Bundle\OrderBundle\Workflow;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Marello\Bundle\InventoryBundle\Entity\InventoryItem;
use Marello\Bundle\InventoryBundle\Logging\InventoryLogger;
use Marello\Bundle\OrderBundle\Entity\Order;
use Marello\Bundle\OrderBundle\Entity\OrderItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;

class OrderCancelAction extends OrderTransitionAction
{
    /** @var Registry */
    protected $doctrine;

    /** @var InventoryLogger */
    protected $logger;

    /** @var InventoryItem[] */
    protected $changedInventory;

    /**
     * OrderShipAction constructor.
     *
     * @param ContextAccessor $contextAccessor
     * @param Registry        $doctrine
     * @param InventoryLogger $logger
     */
    public function __construct(ContextAccessor $contextAccessor, Registry $doctrine, InventoryLogger $logger)
    {
        parent::__construct($contextAccessor);

        $this->doctrine = $doctrine;
        $this->logger   = $logger;
    }

    /**
     * @param WorkflowItem|mixed $context
     */
    protected function executeAction($context)
    {
        /** @var Order $order */
        $order = $context->getEntity();

        $this->changedInventory = [];

        $order->getItems()->map(function (OrderItem $orderItem) {
            $this->cancelOrderItem($orderItem);
        });

        /*
         * Log all changed inventory.
         */
        $this->logger->log($this->changedInventory, 'order_workflow.cancelled');

        $this->doctrine->getManager()->flush();
    }

    /**
     * Deallocates all inventory allocated towards item (items have not been shipped and allocation was released).
     *
     * @param OrderItem $orderItem
     */
    protected function cancelOrderItem(OrderItem $orderItem)
    {
        $allocations = $orderItem->getInventoryAllocations();

        foreach ($allocations as $allocation) {
            $this->changedInventory[] = $inventoryItem = $allocation->getInventoryItem();

            /*
             * When allocation is removed, the allocated amount on inventory amount will be automatically decreased.
             */
            $this->doctrine->getManager()->remove($allocation);
            $this->doctrine->getManager()->persist($inventoryItem);
        }
    }
}