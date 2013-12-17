<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;

class TypeView extends AbstractSingleColumnType
{
    protected $name = 'View';

    protected $phpDataType = 'string';

    protected $sqlDataType = 'LONGVARCHAR';

}