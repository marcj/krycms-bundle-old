<?php

namespace Kryn\CmsBundle\Controller\Admin;

use Kryn\CmsBundle\Controller;
use Kryn\CmsBundle\Propel\PropelHelper;

class ORM extends Controller
{
    public function getPropelHelper()
    {
        $propelHelper = new PropelHelper($this->getKrynCore());
        return $propelHelper;
    }

    public function buildEnvironment()
    {
        return $this->getPropelHelper()->callGen('environment');
    }

    public function writeModels()
    {
        return $this->getPropelHelper()->generateClasses();
    }

    public function updateScheme()
    {
        return $this->getPropelHelper()->updateSchema();
    }

    public function checkScheme()
    {
        //todo
        return true;
    }

}
