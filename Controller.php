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
        $adminPrefix = $this->getKernel()->getContainer()->getParameter('kryn_admin_prefix');
        if ('/' === substr($adminPrefix, -1)) {
            $adminPrefix = substr($adminPrefix, 0, -1);
        }

        return (0 === strpos($this->getRequest()->getPathInfo(), $adminPrefix.'/'));
    }

    public function isEditMode($nodeId = null)
    {
        if ($nodeId) {
            return $this->getRequest() && 1 == $this->getRequest()->get('_kryn_editor')
            && $this->getACL()->checkUpdate(
                'KrynCmsBundle:Node',
                $nodeId
            );
        }

        return $this->getRequest() && 1 == $this->getRequest()->get('_kryn_editor')
        && $this->getKrynCore()->getCurrentPage()
        && $this->getACL()->checkUpdate(
            'KrynCmsBundle:Node',
            $this->getKrynCore()->getCurrentPage()->getId()
        );
    }
}