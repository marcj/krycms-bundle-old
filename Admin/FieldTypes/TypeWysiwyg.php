<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;

class TypeWysiwyg extends AbstractType
{
    protected $name = 'Wysiwyg';

    protected $phpDataType = 'string';

    protected $sqlDataType = 'LONGVARCHAR';

}