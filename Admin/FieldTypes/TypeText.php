<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;

class TypeText extends AbstractType
{
    protected $name = 'Text';

    protected $phpDataType = 'string';

    protected $sqlDataType = 'VARCHAR(255)';

}