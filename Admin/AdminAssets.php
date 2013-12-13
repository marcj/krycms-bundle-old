<?php

namespace Kryn\CmsBundle\Admin;


use Kryn\CmsBundle\Core;

class AdminAssets
{

    /**
     * @var Core
     */
    protected $krynCore;

    function __construct(Core $krynCore)
    {
        $this->krynCore = $krynCore;
    }

    /**
     * @param \Kryn\CmsBundle\Core $krynCore
     */
    public function setKrynCore($krynCore)
    {
        $this->krynCore = $krynCore;
    }

    /**
     * @return \Kryn\CmsBundle\Core
     */
    public function getKrynCore()
    {
        return $this->krynCore;
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
        $session['access'] = $this->getKrynCore()->getACL()->check('KrynCmsBundle:EntryPoint', '/admin');
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
        $prefix = substr($this->getKrynCore()->getAdminPrefix(), 1);

        $response->addJsFile($prefix . '/admin/ui/languages?noCache=978699877');
        $response->addJsFile($prefix . '/admin/ui/language?lang=en&javascript=1');
        $response->addJsFile($prefix . '/admin/ui/language-plural?lang=en');
    }

    public function addMainResources($options = array())
    {
        $response = $this->getKrynCore()->getPageResponse();
        $request = $this->getKrynCore()->getRequest();
        $options['noJs'] = isset($options['noJs']) ? $options['noJs'] : false;

        $prefix = substr($this->getKrynCore()->getAdminPrefix(), 1);

        $response->addJs(
            '
        window._path = window._baseUrl = ' . json_encode($request->getBasePath() . '/') . '
        window._pathAdmin = ' . json_encode($request->getBaseUrl() . '/' . $prefix . '/')
        );

        if ($this->getKrynCore()->getKernel()->isDebug()) {
            foreach ($this->getKrynCore()->getConfigs() as $bundleConfig) {
                if (!$options['noJs']) {
                    foreach ($bundleConfig->getAdminAssetsPaths(false, '.*\.js', true) as $assetPath) {
                        $response->addJsFile($assetPath);
                    }
                }
                foreach ($bundleConfig->getAdminAssetsPaths(false, '.*\.css', true)
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
                    foreach ($bundleConfig->getAdminAssetsPaths(false, '.*\.js', true) as $assetPath) {
                        $path = $this->getKrynCore()->resolvePath($assetPath, 'Resources/public');
                        if (!file_exists($path)) {
                            $response->addJsFile($assetPath);
                        }
                    }

                    foreach ($bundleConfig->getAdminAssetsPaths(false, '.*\.js', true, false)as $assetPath) {
                        $response->addJsFile($assetPath);
                    }
                }

                foreach ($bundleConfig->getAdminAssetsPaths(false, '.*\.css', true) as $assetPath) {
                    $path = $this->getKrynCore()->resolvePath($assetPath, 'Resources/public');
                    if (!file_exists($path)) {
                        $response->addCssFile($assetPath);
                    }
                }

                foreach ($bundleConfig->getAdminAssetsPaths(false, '.*\.css', true, false)
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

        $response = $this->getKrynCore()->getPageResponse();
        $response->addJsFile('@KrynCmsBundle/admin/mootools-core-1.4.5-fixed-memory-leak.js');
        $response->addJsFile('@KrynCmsBundle/admin/mootools-more.js');

        //$response->addJs('ka = parent.ka;');

        $response->setResourceCompression(false);
        $response->setDomainHandling(false);

        $request = $this->getKrynCore()->getRequest();

        $nodeArray['id'] = $page->getId();
        $nodeArray['title'] = $page->getTitle();
        $nodeArray['domainId'] = $page->getDomainId();

        $options = [
            'id' => $request->query->get('_kryn_editor_id'),
            'node' => $nodeArray
        ];

        if (is_array($extraOptions = $request->query->get('_kryn_editor_options'))) {
            $options = array_merge($options, $extraOptions);
            $options['standalone'] = filter_var($options['standalone'], FILTER_VALIDATE_BOOLEAN);
        }

        $response->addJs(
            'window.editor = new parent.ka.Editor(' . json_encode($options) . ', document.documentElement);',
            'bottom'
        );
    }

} 