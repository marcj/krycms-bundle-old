<?php

namespace Kryn\CmsBundle;

use Symfony\Bundle\FrameworkBundle\Controller\Controller as sController;

class Controller extends sController
{
    /**
     * @return Core
     */
    public function getKrynCore()
    {
        return $this->get('kryn.cms');
    }

    /**
     * @return PageResponse
     */
    public function getPageResponse()
    {
        return $this->getKrynCore()->getPageResponse();
    }

}