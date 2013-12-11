<?php

namespace Kryn\CmsBundle\Controller;

use Kryn\CmsBundle\Model\NodeQuery;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Kryn\CmsBundle\PluginController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

        $this->addMainResources();
        $this->addLanguageResources();
        $this->addSessionScripts();

        $response = $this->getKrynCore()->getPageResponse();
        $response->addJs(
            "
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

    public function addSessionScripts()
    {
        $response = $this->getKrynCore()->getPageResponse();

        $client = $this->getKrynCore()->getAdminClient();
        if (!$client) {
            $client = $this->getKrynCore()->getClient();
        }

        $session = array();
        $session['userId'] = $client->getUserId();
        $session['sessionid'] = $client->getToken();
        $session['tokenid'] = $client->getTokenId();
        $session['lang'] = $client->getSession()->getLanguage();
        $session['access'] = $this->get('kryn_cms.acl')->check('KrynCmsBundle:EntryPoint', '/admin');
        if ($client->getUserId()) {
            $session['username'] = $client->getUser()->getUsername();
            $session['lastLogin'] = $client->getUser()->getLastlogin();
            $session['firstName'] = $client->getUser()->getFirstName();
            $session['lastName'] = $client->getUser()->getLastName();
        }

        $css = 'window._session = ' . json_encode($session) . ';';
        $response->addJs($css);
    }

    public function addLanguageResources()
    {
        $response = $this->getKrynCore()->getPageResponse();
        $prefix = substr($this->getKernel()->getContainer()->getParameter('kryn_admin_prefix'), 1);

        $response->addJsFile($prefix . '/admin/ui/languages?noCache=978699877');
        $response->addJsFile($prefix . '/admin/ui/language?lang=en&javascript=1');
        $response->addJsFile($prefix . '/admin/ui/language-plural?lang=en');
    }

    public function addMainResources($options = array())
    {
        $response = $this->getKrynCore()->getPageResponse();
        $options['noJs'] = isset($options['noJs']) ? $options['noJs'] : false;

        $prefix = substr($this->getKernel()->getContainer()->getParameter('kryn_admin_prefix'), 1);
//        $prefix = $this->getKernel()->getContainer()->getParameter('kryn_admin_prefix');

        $response->addJs(
            '
        window._path = window._baseUrl = ' . json_encode($this->getRequest()->getBasePath() . '/') . '
        window._pathAdmin = ' . json_encode($this->getRequest()->getBaseUrl() .'/' . $prefix . '/')
        );

        if ($this->getKrynCore()->getKernel()->isDebug()) {
            foreach ($this->getKrynCore()->getConfigs() as $bundleConfig) {
                if (!$options['noJs']) {
                    foreach ($bundleConfig->getAdminAssetsPaths(false, '.*\.js', $regex = true) as $assetPath) {
                        $response->addJsFile($assetPath);
                    }
                }
                foreach ($bundleConfig->getAdminAssetsPaths(false, '.*\.css', $regex = true)
                         as $assetPath) {
                    $response->addCssFile($assetPath);
                }
            }
        } else {
            $response->addCssFile($prefix . '/admin/backend/css');
            if (!$options['noJs']) {
                $response->addJsFile($prefix . '/admin/backend/script');
            }

            foreach ($this->getKrynCore()->getConfigs() as $bundleConfig) {
                if (!$options['noJs']) {
                    foreach ($bundleConfig->getAdminAssetsPaths(false, '.*\.js', $regex = true) as $assetPath) {
                        $path = $this->getKrynCore()->resolvePath($assetPath, 'Resources/public');
                        if (!file_exists($path)) {
                            $response->addJsFile($assetPath);
                        }
                    }

                    foreach ($bundleConfig->getAdminAssetsPaths(false, '.*\.js', $regex = true, $compression = false)
                             as $assetPath) {
                        $response->addJsFile($assetPath);
                    }
                }

                foreach ($bundleConfig->getAdminAssetsPaths(false, '.*\.css', $regex = true) as $assetPath) {
                    $path = $this->getKrynCore()->resolvePath($assetPath, 'Resources/public');
                    if (!file_exists($path)) {
                        $response->addCssFile($assetPath);
                    }
                }

                foreach ($bundleConfig->getAdminAssetsPaths(false, '.*\.css', $regex = true, $compression = false)
                         as $assetPath) {
                    $response->addCssFile($assetPath);
                }
            }
        }

        $response->addHeader('<meta name="viewport" content="initial-scale=1.0" >');
        $response->addHeader('<meta name="apple-mobile-web-app-capable" content="yes">');

        $response->setResourceCompression(false);
    }


    public function handleKEditor()
    {
        $this->addMainResources(['noJs' => true]);
        $this->addSessionScripts();
        $page = $this->getKrynCore()->getCurrentPage();

        $response = $this->getPageResponse();
        $response->addJsFile('@KrynCmsBundle/admin/mootools-core-1.4.5-fixed-memory-leak.js');
        $response->addJsFile('@KrynCmsBundle/admin/mootools-more.js');

        //$response->addJs('ka = parent.ka;');

        $response->setResourceCompression(false);
        $response->setDomainHandling(false);

        $nodeArray['id'] = $page->getId();
        $nodeArray['title'] = $page->getTitle();
        $nodeArray['domainId'] = $page->getDomainId();

        $options = [
            'id' => @$_GET['_kryn_editor_id'],
            'node' => $nodeArray
        ];

        if (is_array(@$_GET['_kryn_editor_options'])) {
            $options = array_merge($options, $_GET['_kryn_editor_options']);
            $options['standalone'] = filter_var($options['standalone'], FILTER_VALIDATE_BOOLEAN);
        }
        $response->addJs(
            'window.editor = new parent.ka.Editor(' . json_encode($options) . ', document.documentElement);',
            'bottom'
        );
    }

}
