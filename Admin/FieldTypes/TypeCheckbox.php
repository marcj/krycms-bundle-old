<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;

class TypeCheckbox extends AbstractType
{
    protected $name = 'Checkbox';

    protected $phpDataType = 'boolean';

    protected $sqlDataType = 'boolean';
}