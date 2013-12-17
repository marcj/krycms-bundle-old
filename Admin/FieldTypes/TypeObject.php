<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;

use Kryn\CmsBundle\Configuration\Configs;
use Kryn\CmsBundle\Configuration\Field;
use Kryn\CmsBundle\Configuration\Object;
use Kryn\CmsBundle\Exceptions\ModelBuildException;
use Kryn\CmsBundle\Exceptions\ObjectNotFoundException;
use Kryn\CmsBundle\Objects;
use Kryn\CmsBundle\ORM\ORMAbstract;
use Kryn\CmsBundle\Tools;

class TypeObject extends AbstractType
{
    protected $name = 'Object';

    /**
     * @var Objects
     */
    protected $objects;

    public function __construct(Objects $objects)
    {
        $this->objects = $objects;
    }

    public function getColumns()
    {
        if (ORMAbstract::MANY_TO_ONE == $this->getFieldDefinition()->getObjectRelation() ||
            ORMAbstract::ONE_TO_ONE == $this->getFieldDefinition()->getObjectRelation()
        ) {
            $foreignObjectDefinition = $this->objects->getDefinition($this->getFieldDefinition()->getObject());

            if (!$foreignObjectDefinition) {
                throw new ObjectNotFoundException(sprintf(
                    'ObjectKey `%s` not found in field `%s` of object `%s`',
                    $this->getFieldDefinition()->getObject(),
                    $this->getFieldDefinition()->getId(),
                    $this->getFieldDefinition()->getObjectDefinition()->getId()
                ));
            }

            /** @var $columns ColumnDefinitionInterface[] */
            $columns = [];

            foreach ($foreignObjectDefinition->getPrimaryKeys() as $pk) {
                $fieldColumns = $pk->getFieldType()->getColumns();
                $columns = array_merge($columns, $fieldColumns);
            }

            //rename columns to fieldId+column.id
            foreach ($columns as &$column) {
                $column = clone $column;
                $column->setName($this->getFieldDefinition()->getId() . ucfirst($column->getName()));
            }

            return $columns;
        }
    }

    /**
     * Returns the field names to select from the object model as array.
     *
     * @return string[]
     */
    public function getSelection()
    {
        $selection = [];
        if ($columns = $this->getColumns()) {
            foreach ($columns as $column) {
                $selection[] = $column->getName();
            }
        }

        return $selection;
    }


    public function bootBuildTime(Object $object, Configs $configs)
    {

    }

    public function bootRunTime(Object $object, Configs $configs)
    {
        //check for n-to-n relation and create crossTable
        //check for 1-to-n objectRelations and create cross object w/ relations

        $changed = false;

        if (ORMAbstract::MANY_TO_MANY == $this->getFieldDefinition()->getObjectRelation()) {
            if ($this->defineCrossTable($object, $configs)) {
                $changed = true;
            }
        }

        if (ORMAbstract::MANY_TO_ONE == $this->getFieldDefinition()->getObjectRelation() ||
            ORMAbstract::ONE_TO_ONE == $this->getFieldDefinition()->getObjectRelation()
        ) {
            if ($this->defineCrossReference($object, $configs)) {
                $changed = true;
            }
        }

        return $changed;
    }

    /**
     * @param Configs $configs
     * @return bool
     */
    protected function defineCrossTable(Object $objectDefinition, Configs $configs)
    {
        $changed = false;

        $bundle = $objectDefinition->getBundle();
        $foreignObjectDefinition = $configs->getObject($this->getFieldDefinition()->getObject());

        $possibleObjectName =
            ucfirst($objectDefinition->getId()) .
            ucfirst($foreignObjectDefinition->getId());
        $possibleObjectKey = $bundle->getName() . '/' . $possibleObjectName;


        if (!$crossObjectKey = $this->getFieldDefinition()->getObjectRelationCrossObjectKey()) {
            $crossObjectKey = $possibleObjectKey;
        }

        $crossObject = $configs->getObject($crossObjectKey);

        if (!$crossObject) {
            if (!$crossObject = $configs->getObject($possibleObjectKey)) {
                $crossObject = new Object(null, $objectDefinition->getKrynCore());
                $crossObject->setId($possibleObjectName);
                $crossObject->setTable($objectDefinition->getTable() . '_' . Tools::camelcase2Underscore($foreignObjectDefinition->getId()));
                $changed = true;
            }
        }

        if (!$crossObject->getField($objectDefinition->getId())) {
            $leftObjectField = new Field(null, $objectDefinition->getKrynCore());
            $leftObjectField->setId($objectDefinition->getId());
            $leftObjectField->setType('object');
            $leftObjectField->setObject($objectDefinition->getKey());
            $leftObjectField->setObjectRelation(ORMAbstract::ONE_TO_ONE);
            $crossObject->addField($leftObjectField);
            $changed = true;
        }

        if (!$crossObject->getField($foreignObjectDefinition->getId())) {
            $rightObjectField = new Field(null, $objectDefinition->getKrynCore());
            $rightObjectField->setId($foreignObjectDefinition->getId());
            $rightObjectField->setType('object');
            $rightObjectField->setObject($foreignObjectDefinition->getKey());
            $rightObjectField->setObjectRelation(ORMAbstract::ONE_TO_ONE);
            $crossObject->addField($rightObjectField);
            $changed = true;
        }

        if (!$crossObject->getBundle()) {
            //we created a new object
            $bundle->addObject($crossObject);
        }

        return $changed;
    }

    protected function defineCrossReference(Object $objectDefinition, Configs $configs)
    {
        $relation = $this->getRelation();
        if (!$objectDefinition->hasRelation($relation->getName())) {
            $objectDefinition->addRelation($relation);

            return true;
        }
    }

    /**
     * @return RelationDefinition
     */
    protected function getRelation()
    {
        $field = $this->getFieldDefinition();
        $columns = [];
        $foreignObjectDefinition = $this->objects->getDefinition($field->getObject());

        if (!$foreignObjectDefinition) {
            throw new ModelBuildException(sprintf(
                'ObjectKey `%s` not found for field `%s` in object `%s`',
                $field->getObject(),
                $field->getId(),
                $field->getObjectDefinition()->getId()
            ));
        }

        $relation = new RelationDefinition();
        $relation->setName($field->getId());
        $relation->setType(ORMAbstract::MANY_TO_ONE);
        $relation->setForeignObjectKey($field->getObject());

        foreach ($foreignObjectDefinition->getPrimaryKeys() as $pk) {
            $fieldColumns = $pk->getFieldType()->getColumns();
            $columns = array_merge($columns, $fieldColumns);
        }

        if (!$columns) {
            return null;
        }

        $references = [];

        foreach ($columns as $column) {
            $reference = new RelationReferenceDefinition();

            $localColumn = clone $column;
            $localColumn->setName($field->getId() . ucfirst($column->getName()));
            $reference->setLocalColumn($localColumn);

            $reference->setForeignColumn($column);
            $references[] = $reference;
        }

        $relation->setReferences($references);

        return $relation;
    }

}