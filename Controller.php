<?php

namespace Kryn\CmsBundle;
use Symfony\Bundle\FrameworkBundle\Controller\Controller as sController;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class Controller extends sController implements ContainerAwareInterface {

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