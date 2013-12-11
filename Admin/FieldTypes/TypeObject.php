<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;

class TypeObject extends AbstractType
{
    protected $name = 'Object';

    protected $objectKey = '';

    protected $phpDataType = 'string';

    protected $sqlDataType = 'string';
}