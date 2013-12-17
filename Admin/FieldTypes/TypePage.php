<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;

class TypePage extends AbstractSingleColumnType
{
    protected $name = 'Page';

    protected $phpDataType = 'integer';

    protected $sqlDataType = 'integer';
}