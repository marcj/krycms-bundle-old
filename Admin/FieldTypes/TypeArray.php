<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;

class TypeArray extends AbstractType
{
    protected $name = 'Array';

    //#todo
    protected $phpDataType = 'string';

    protected $sqlDataType = 'VARCHAR(255)';

}