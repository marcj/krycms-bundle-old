<?php

namespace Kryn\CmsBundle;
use Symfony\Bundle\FrameworkBundle\Controller\Controller as sController;

class Controller extends sController {

    use ContainerHelperTrait;
    /**
     * @return bool
     */
    public function isAdmin()
    {
        return (0 === strpos($this->getRequest()->getPathInfo() . '/', $this->getSystemConfig()->getAdminUrl()));
    }

    public function isEditMode()
    {
        return $this->getRequest() && 1 == $this->getRequest()->get('_kryn_editor')
        && $this->getKrynCore()->getCurrentPage()
        && $this->getACL()->checkUpdate(
            'KrynCmsBundle:Node',
            $this->getKrynCore()->getCurrentPage()->getId()
        );
    }
}