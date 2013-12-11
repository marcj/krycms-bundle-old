<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;

class TypeNumber extends AbstractType
{
    protected $name = 'Number';

    protected $phpDataType = 'integer';

    protected $sqlDataType = 'integer';
}