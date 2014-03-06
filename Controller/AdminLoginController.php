<?php

namespace Kryn\CmsBundle\Controller;

use Kryn\CmsBundle\Admin\AdminAssets;
use Kryn\CmsBundle\PluginController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;

class AdminLoginController extends PluginController
{
    /**
     * @ApiDoc(
     *  section="Administration",
     *  description="Show the login page of the administration"
     * )
     *
     * @Rest\Get("%kryn_admin_prefix%")
     * @param Request $request
     *
     * @return \Kryn\CmsBundle\PageResponse
     */
    public function showLoginAction(Request $request)
    {
        if ($this->container->has('profiler')) {
            $this->container->get('profiler')->disable();
        }

        $adminAssets = new AdminAssets($this->getKrynCore());
        $adminAssets->addMainResources();
        $adminAssets->addLanguageResources();
        $adminAssets->addSessionScripts();

        $response = $this->getKrynCore()->getPageResponse();
        $response->addJs(
            "
        tinymce.baseURL =  _path+'bundles/kryncms/tinymce',
        window.addEvent('domready', function(){
            ka.adminInterface = new ka.AdminInterface();
        });
"
        );

        $response->setResourceCompression(false);
        $response->setDomainHandling(false);

        $response->setTitle($this->getKrynCore()->getSystemConfig()->getSystemTitle() . ' | Kryn.cms Administration');
        $response->prepare($request);
        return $response;
    }

}
