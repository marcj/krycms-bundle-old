<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;

use Kryn\CmsBundle\Configuration\Configs;
use Kryn\CmsBundle\Configuration\Field;
use Kryn\CmsBundle\Configuration\Object;
use Kryn\CmsBundle\ORM\Builder\Builder;

/**
 * This class is used for simple FieldTypes that don't create relations, new columns etc,
 * and save their value in only one column.
 */
abstract class AbstractSingleColumnType extends AbstractType
{
    /**
     * @var string
     */
    protected $phpDataType;

    /**
     * @var string
     */
    protected $sqlDataType;

    /**
     * @return array
     */
    public function getSelection()
    {
        return [$this->getFieldDefinition()->getId()];
    }

    /**
     * @return ColumnDefinitionInterface[]
     */
    public function getColumns()
    {
        $columnDefinition = new ColumnDefinition();
        $columnDefinition->setPhpDataType($this->getPhpDataType());

        $type = $this->getSqlDataType();
        if ($maxLength = $this->getFieldDefinition()->getMaxLength()) {
            $type .= '(' . $maxLength . ')';
        }

        $columnDefinition->setSqlDataType($type);
        $columnDefinition->setName($this->getFieldDefinition()->getId());

        if ($regex = $this->getFieldDefinition()->getRequiredRegex()) {
            $columnDefinition->setRequiredRegex($regex);
        }

        return [$columnDefinition];
    }

    public function bootBuildTime(Object $object, Configs $configs)
    {
        //nothing to do per default
    }

    public function bootRunTime(Object $object, Configs $configs)
    {
        //nothing to do per default
    }

    /**
     * @param string $phpDataType
     */
    public function setPhpDataType($phpDataType)
    {
        $this->phpDataType = $phpDataType;
    }

    /**
     * @return string
     */
    public function getPhpDataType()
    {
        return $this->phpDataType;
    }

    /**
     * @param string $sqlDataType
     */
    public function setSqlDataType($sqlDataType)
    {
        $this->sqlDataType = $sqlDataType;
    }

    /**
     * @return string
     */
    public function getSqlDataType()
    {
        return $this->sqlDataType;
    }

}
