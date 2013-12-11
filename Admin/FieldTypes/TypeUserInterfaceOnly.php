<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 11.12.13
 * Time: 03:31
 */

namespace Kryn\CmsBundle\Admin\FieldTypes;


class TypeUserInterfaceOnly extends AbstractType
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