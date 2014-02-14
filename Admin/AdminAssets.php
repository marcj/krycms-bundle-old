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

        $response->addJsFile($prefix . '/admin/ui/languages');
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
                foreach ($bundleConfig->getAdminAssetsInfo() as $assetInfo) {
                    if ($options['noJs'] && $assetInfo->isJavaScript()) {
                        continue;
                    }

                    $response->addAsset($assetInfo);
                }
            }
        } else {
            $response->addCssFile($prefix . '/admin/backend/css');
            if (!$options['noJs']) {
                $response->addJsFile($prefix . '/admin/backend/script');
            }

            foreach ($this->getKrynCore()->getConfigs() as $bundleConfig) {
                foreach ($bundleConfig->getAdminAssetsInfo() as $assetInfo) {
                    if ($options['noJs'] && $assetInfo->isJavaScript()) {
                        continue;
                    }

                    if ($assetInfo->getFile()) {
                        // load javascript files, that are not accessible (means those are points to a controller)
                        // because those can't not be compressed
                        $path = $this->getKrynCore()->resolveWebPath($assetInfo->getFile());
                        if (!file_exists($path)) {
                            $response->addAsset($assetInfo);
                            continue;
                        }
                    }

                    if ($assetInfo->getContent()) {
                        // load inline assets because we can't compress those
                        $response->addAsset($assetInfo);
                        continue;
                    }

                    if (!$assetInfo->getAllowCompression()) {
                        $response->addAsset($assetInfo);
                    }
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

        // TODO, remove mootools dependency. WE NEED MOOTOOLS PRIME FOR THAT!
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

        $response->addJsAtBottom(
            'window.editor = new parent.ka.Editor(' . json_encode($options) . ', document.documentElement);'
        );
    }

} 