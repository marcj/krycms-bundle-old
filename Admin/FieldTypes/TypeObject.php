<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;

class TypeObject extends AbstractType
{
    protected $name = 'Object';

    protected $objectKey = '';

    protected $phpDataType = 'string';

    protected $sqlDataType = 'string';

    /**
     * @return array
     */
    public function getSelection()
    {

        return parent::getSelection();
        /*
         @todo, for composite PK it's necessary to select more than one field
        return [
            $this->getFieldDefinition()->getId().'_' . $pk1,
            $this->getFieldDefinition()->getId().'_' . $pk2
        ];
        */
    }
}