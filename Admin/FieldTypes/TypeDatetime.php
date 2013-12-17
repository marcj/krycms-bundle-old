<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;

class TypeDatetime extends AbstractSingleColumnType
{
    protected $name = 'Datetime';

    protected $phpDataType = 'integer';

    protected $sqlDataType = 'bigint';
}