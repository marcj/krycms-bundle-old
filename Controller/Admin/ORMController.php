<?php

namespace Kryn\CmsBundle\Controller\Admin;

use Kryn\CmsBundle\Controller;
use Kryn\CmsBundle\Propel\PropelHelper;
use FOS\RestBundle\Controller\Annotations as Rest;

class ORMController extends Controller
{
    public function getPropelHelper()
    {
        $propelHelper = new PropelHelper($this->getKrynCore());
        return $propelHelper;
    }

    /**
     * Checks if all table/entity definitions are correct.
     *
     * @Rest\View()
     *
     * @Rest\Get("admin/system/orm/check")
     * @return bool
     */
    public function checkAction()
    {
        //todo, make it ORM agnostic
        // $this->getEventDispatcher()->dispatch('kryncms.orm-check');
        return $this->getPropelHelper()->callGen('environment');
    }

    /**
     * Writes all necessary model files.
     *
     * @Rest\View()
     * @Rest\Post("admin/system/orm/models")
     *
     * @return bool
     */
    public function writeModels()
    {
        //todo, make it ORM agnostic
        return $this->getPropelHelper()->generateClasses();
    }

    /**
     * Updates database's schema.
     *
     * @Rest\View()
     * @Rest\Post("admin/system/orm/schema")
     *
     * @return bool
     */
    public function updateScheme()
    {
        //todo, make it ORM agnostic
        return $this->getPropelHelper()->updateSchema();
    }

}
