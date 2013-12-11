<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;

class TypeSelect extends AbstractType
{
    protected $name = 'Select';

    protected $phpDataType = 'string';

    protected $sqlDataType = 'string';
}