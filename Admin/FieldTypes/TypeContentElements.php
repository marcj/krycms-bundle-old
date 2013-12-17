<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;

class TypeContentElements extends AbstractSingleColumnType
{
    protected $name = 'Contents Elements';

    /**
     * @return array
     */
    public function getSelection()
    {
        return [$this->getFieldDefinition()->getId().'.*'];
    }
}