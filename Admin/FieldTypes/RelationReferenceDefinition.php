<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;

class RelationReferenceDefinition implements RelationReferenceDefinitionInterface
{

    /**
     * @var ColumnDefinitionInterface
     */
    protected $localColumn;

    /**
     * @var ColumnDefinitionInterface
     */
    protected $foreignColumn;

    /**
     * @param \Kryn\CmsBundle\Admin\FieldTypes\ColumnDefinitionInterface $localColumn
     */
    public function setLocalColumn($localColumn)
    {
        $this->localColumn = $localColumn;
    }

    /**
     * @return \Kryn\CmsBundle\Admin\FieldTypes\ColumnDefinitionInterface
     */
    public function getLocalColumn()
    {
        return $this->localColumn;
    }

    /**
     * @param \Kryn\CmsBundle\Admin\FieldTypes\ColumnDefinitionInterface $foreignColumn
     */
    public function setForeignColumn($foreignColumn)
    {
        $this->foreignColumn = $foreignColumn;
    }

    /**
     * @return \Kryn\CmsBundle\Admin\FieldTypes\ColumnDefinitionInterface
     */
    public function getForeignColumn()
    {
        return $this->foreignColumn;
    }

} 