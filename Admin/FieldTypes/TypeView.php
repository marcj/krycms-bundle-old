<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;

class TypeView extends AbstractType
{
    protected $name = 'View';

    protected $phpDataType = 'string';

    protected $sqlDataType = 'LONGVARCHAR';

}