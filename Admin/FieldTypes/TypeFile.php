<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;

class TypeFile extends AbstractSingleColumnType
{
    protected $name = 'File';

    protected $phpDataType = 'string';

    protected $sqlDataType = 'LONGVARCHAR';

}