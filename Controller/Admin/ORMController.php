<?php

namespace Kryn\CmsBundle\Controller\Admin;

use Kryn\CmsBundle\Controller;
use Kryn\CmsBundle\Propel\PropelHelper;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class ORMController extends Controller
{
    protected function getPropelHelper()
    {
        $propelHelper = new PropelHelper($this->getKrynCore());
        return $propelHelper;
    }

    /**
     *
     * @Rest\Get("admin/system/orm/build")
     */
    public function build()
    {
        $modelBuilder = $this->getKrynCore()->getModelBuilder();
        $modelBuilder->build();
        return true;
    }

}
