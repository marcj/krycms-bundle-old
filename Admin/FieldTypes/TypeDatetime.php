<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;

class TypeDatetime extends AbstractType
{
    protected $name = 'Datetime';

    protected $phpDataType = 'integer';

    protected $sqlDataType = 'bigint';
}