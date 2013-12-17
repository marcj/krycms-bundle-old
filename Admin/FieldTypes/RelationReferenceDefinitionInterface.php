<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;


interface RelationReferenceDefinitionInterface
{
    /**
     * @return ColumnDefinitionInterface
     */
    public function getLocalColumn();

    /**
     * @return ColumnDefinitionInterface
     */
    public function getForeignColumn();
} 