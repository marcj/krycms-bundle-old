<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;

class TypeSelect extends AbstractSingleColumnType
{
    protected $name = 'Select';

    protected $phpDataType = 'string';

    protected $sqlDataType = 'string';
}