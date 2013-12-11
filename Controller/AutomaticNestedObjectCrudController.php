<?php

namespace Kryn\CmsBundle\Controller;

class AutomaticNestedObjectCrudController extends NestedObjectCrudController
{
    public function getObject()
    {
        return $this->detectObjectKeyFromPathInfo();
    }
}