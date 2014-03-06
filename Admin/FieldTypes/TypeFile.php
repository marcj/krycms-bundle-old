<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;

use Kryn\CmsBundle\Tools;

class TypeFile extends AbstractSingleColumnType
{
    protected $name = 'File';

    protected $phpDataType = 'string';

    protected $sqlDataType = 'LONGVARCHAR';

    public function setValue($value)
    {
        parent::setValue(Tools::urlDecode($value));
    }

}