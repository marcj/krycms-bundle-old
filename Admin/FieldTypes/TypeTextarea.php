<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;

class TypeTextarea extends AbstractSingleColumnType
{
    protected $name = 'Textarea';

    protected $phpDataType = 'string';

    protected $sqlDataType = 'LONGVARCHAR';

}