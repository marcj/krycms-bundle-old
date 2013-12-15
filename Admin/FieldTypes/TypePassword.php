<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;

class TypePassword extends AbstractType
{
    protected $name = 'Password';

    protected $phpDataType = 'string';

    protected $sqlDataType = 'VARCHAR(255)';
}