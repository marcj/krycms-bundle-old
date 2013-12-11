<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;

class TypeTextarea extends AbstractType
{
    protected $name = 'Textarea';

    protected $phpDataType = 'string';

    protected $sqlDataType = 'LONGVARCHAR';

}