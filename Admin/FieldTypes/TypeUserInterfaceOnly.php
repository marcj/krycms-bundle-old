<?php

namespace Kryn\CmsBundle\Admin\FieldTypes;


class TypeUserInterfaceOnly extends AbstractSingleColumnType
{
    public function getRequiredRegex()
    {
        return '.*';
    }

    public function validate()
    {
        return [];
    }


} 