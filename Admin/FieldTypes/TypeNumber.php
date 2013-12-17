<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;

class TypeNumber extends AbstractSingleColumnType
{
    protected $name = 'Number';

    protected $phpDataType = 'integer';

    protected $sqlDataType = 'integer';
}