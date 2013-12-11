<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;

class TypeFile extends AbstractType
{
    protected $name = 'File';

    protected $phpDataType = 'string';

    protected $sqlDataType = 'LONGVARCHAR';

}