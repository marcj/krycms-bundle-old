<?php

namespace Kryn\CmsBundle\Controller\Admin;

use Kryn\CmsBundle\Model\NodeQuery;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Kryn\CmsBundle\PluginController;

class LoginController extends PluginController
{
    public function showLogin()
    {
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
        $session['access'] = $this->get('kryn.acl')->check('KrynCmsBundle:EntryPoint', '/admin');
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
        $baseUrl = $this->getRequest()->getBaseUrl() . '/';
        $prefix = substr($this->getRequest()->getRequestUri(), strlen($baseUrl));

        $response->addJsFile($prefix . '/admin/ui/languages?noCache=978699877');
        $response->addJsFile($prefix . '/admin/ui/language?lang=en&javascript=1');
        $response->addJsFile($prefix . '/admin/ui/language-plural?lang=en');
    }

    public function addMainResources($options = array())
    {
        $response = $this->getKrynCore()->getPageResponse();

        $baseUrl = $this->getRequest()->getBaseUrl();
        $path = substr($this->getRequest()->getRequestUri(), strlen($baseUrl) + 1);

        $options['noJs'] = isset($options['noJs']) ? $options['noJs'] : false;


        //do we need to add app_dev.php/ or something?
        $prefix = substr(
            $this->getKrynCore()->getRequest()->getBaseUrl(),
            strlen($this->getKrynCore()->getRequest()->getBasePath())
        );
        if (false !== $prefix) {
            $path = substr($prefix, 1) . '/' . $path;
        }

        $response->addJs(
            '
        window._path = window._baseUrl = ' . json_encode(dirname($baseUrl). '/') . '
        window._pathAdmin = ' . json_encode($path . '/')
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
            $response->addCssFile($prefix . 'admin/backend/style');
            if (!$options['noJs']) {
                $response->addJsFile($prefix . 'admin/backend/script');
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

}
