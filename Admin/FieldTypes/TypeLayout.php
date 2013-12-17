<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;

class TypeLayout extends AbstractSingleColumnType
{
    protected $name = 'Layout';

    protected $phpDataType = 'string';

    protected $sqlDataType = 'LONGVARCHAR';

}