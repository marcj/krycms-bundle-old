<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;

class TypeContent extends AbstractType
{
    protected $name = 'Content Elements';

    protected $phpDataType = 'string';

    protected $sqlDataType = 'LONGVARCHAR';

}