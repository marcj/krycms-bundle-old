<?php

namespace Kryn\CmsBundle\Controller;

class AutomaticObjectCrudController extends ObjectCrudController
{
    public function getObject()
    {
        return $this->detectObjectKeyFromPathInfo();
    }
}