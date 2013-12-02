<?php

namespace Kryn\CmsBundle\Controller\Admin;

use Core\PropelHelper;

class ORM
{
    public function buildEnvironment()
    {
        return PropelHelper::callGen('environment');
    }

    public function writeModels()
    {
        \Core\TempFile::remove('propel/');

        return PropelHelper::generateClasses();
    }

    public function updateScheme()
    {
        \Core\TempFile::remove('propel/');

        return PropelHelper::updateSchema();
    }

    public function checkScheme()
    {
        //todo
        return ($errors) ? $error : true;
    }

}
