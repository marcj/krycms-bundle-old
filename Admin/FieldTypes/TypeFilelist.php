<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;

class TypeFilelist extends AbstractType
{
    protected $name = 'File list';

    protected $phpDataType = 'string';

    protected $sqlDataType = 'LONGVARCHAR';

}