<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;

class TypeLang extends AbstractType
{
    protected $name = 'Language';

    protected $phpDataType = 'string';

    protected $sqlDataType = 'VARCHAR(7)';
}