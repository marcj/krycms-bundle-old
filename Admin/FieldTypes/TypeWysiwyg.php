<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;

class TypeWysiwyg extends AbstractSingleColumnType
{
    protected $name = 'Wysiwyg';

    protected $phpDataType = 'string';

    protected $sqlDataType = 'LONGVARCHAR';

}