<?php

namespace Marello\Bundle\CoreBundle\Serializer;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Component\EntitySerializer\ConfigNormalizer;
use Oro\Component\EntitySerializer\DataAccessorInterface as BaseDataAccessorInterface;
use Oro\Component\EntitySerializer\DataNormalizer;
use Oro\Component\EntitySerializer\DataTransformerInterface as BaseDataTransformerInterface;
use Oro\Component\EntitySerializer\DoctrineHelper;
use Oro\Component\EntitySerializer\EntitySerializer as BaseEntitySerializer;
use Oro\Component\EntitySerializer\FieldAccessor;
use Oro\Component\EntitySerializer\Filter\EntityAwareFilterInterface;
use Oro\Component\EntitySerializer\EntityConfig;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Serializer\ExtendEntityFieldFilter;
use Oro\Bundle\SoapBundle\Serializer\AclProtectedQueryFactory;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

class EntitySerializer extends BaseEntitySerializer
{
    const WORKFLOW_ITEM_FIELD   = 'workflowItems';
    const WORKFLOW_ITEM_FQCN    = 'Oro\Bundle\WorkflowBundle\Entity\WorkflowItem';

    /** @var WorkflowManager $workflowManager */
    protected $workflowManager;

    /**
     * @param ManagerRegistry              $doctrine
     * @param ConfigManager                $configManager
     * @param BaseDataAccessorInterface    $dataAccessor
     * @param BaseDataTransformerInterface $dataTransformer
     * @param AclProtectedQueryFactory     $queryFactory
     */
    public function __construct(
        ManagerRegistry $doctrine,
        ConfigManager $configManager,
        BaseDataAccessorInterface $dataAccessor,
        BaseDataTransformerInterface $dataTransformer,
        AclProtectedQueryFactory $queryFactory,
        WorkflowManager $workflowManager

    ) {
        $doctrineHelper = new DoctrineHelper($doctrine);
        $fieldAccessor  = new FieldAccessor(
            $doctrineHelper,
            $dataAccessor,
            new ExtendEntityFieldFilter($configManager)
        );
        parent::__construct(
            $doctrineHelper,
            $dataAccessor,
            $dataTransformer,
            $queryFactory,
            $fieldAccessor,
            new ConfigNormalizer(),
            new DataNormalizer()
        );

        $this->workflowManager = $workflowManager;
    }

    /**
     * @param mixed        $entity
     * @param string       $entityClass
     * @param EntityConfig $config
     * @param array        $context
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function serializeItem($entity, $entityClass, EntityConfig $config, array $context)
    {
        if (!$entity) {
            return [];
        }

        $result         = [];
        $entityMetadata = $this->doctrineHelper->getEntityMetadata($entityClass);
        $resultFields   = $this->fieldAccessor->getFieldsToSerialize($entityClass, $config);

        foreach ($resultFields as $field) {
            $isFieldAllowed = $this->fieldFilter ?
                $this->fieldFilter->checkField($entity, $entityClass, $field) :
                EntityAwareFilterInterface::FILTER_NOTHING;

            if (EntityAwareFilterInterface::FILTER_ALL === $isFieldAllowed) {
                continue;
            }

            if (EntityAwareFilterInterface::FILTER_NOTHING !== $isFieldAllowed) {
                // return field but without value
                $result[$field] = null;
                continue;
            }

            $fieldConfig = $config->getField($field);
            $value = null;
            if ($this->dataAccessor->tryGetValue($entity, $field, $value)) {
                if ($this->isAssociation($field, $entityMetadata, $fieldConfig)) {
                    if (is_object($value)) {
                        $targetConfig = $this->getTargetEntity($config, $field);
                        if (null !== $targetConfig && !$targetConfig->isEmpty()) {
                            $targetEntityClass = $this->getAssociationTargetClass($field, $entityMetadata, $value);
                            $targetEntityId    = $this->dataAccessor->getValue(
                                $value,
                                $this->doctrineHelper->getEntityIdFieldName($targetEntityClass)
                            );

                            $value = $this->serializeItem($value, $targetEntityClass, $targetConfig, $context);
                            $items = [$value];
                            $this->loadRelatedData(
                                $items,
                                $targetEntityClass,
                                [$targetEntityId],
                                $targetConfig,
                                $context
                            );
                            $value = reset($items);

                            $postSerializeHandler = $targetConfig->getPostSerializeHandler();
                            if (null !== $postSerializeHandler) {
                                $value = $this->postSerialize($value, $postSerializeHandler, $context);
                            }
                        } else {
                            $value = $this->transformValue($entityClass, $field, $value, $context, $fieldConfig);
                        }
                    }
                } else {
                    $value = $this->transformValue($entityClass, $field, $value, $context, $fieldConfig);
                }
                $result[$field] = $value;
            } elseif (null !== $fieldConfig) {
                $propertyPath = $fieldConfig->getPropertyPath($field);

                if ($this->fieldAccessor->isMetadataProperty($propertyPath)) {
                    $result[$field] = $this->fieldAccessor->getMetadataProperty(
                        $entity,
                        $propertyPath,
                        $entityMetadata
                    );
                }

                if ($this->hasWorkflowAssociation($entity) && $this->hasWorkflowItemField($config)) {
                    $workflowItems = $this->workflowManager->getWorkflowItemsByEntity($entity);
                    $targetConfig = $this->getTargetEntity($config, $field);
                    $value = $this->serializeEntities($workflowItems, self::WORKFLOW_ITEM_FQCN, $targetConfig, $context);
                    $result[$field] = $this->transformValue($entityClass, $field, $value, $context, $fieldConfig);
                }
            }
        }

        return $result;
    }

    /**
     * Check if the field exists in the Entity config
     * @param EntityConfig $config
     * @return bool
     */
    protected function hasWorkflowItemField(EntityConfig $config)
    {
        return $config->hasField(self::WORKFLOW_ITEM_FIELD);
    }

    /**
     * Check if there are in fact workflows on the given entity
     * @param $entity
     * @return bool
     */
    protected function hasWorkflowAssociation($entity)
    {
        return $this->workflowManager->hasWorkflowItemsByEntity($entity);
    }

}
