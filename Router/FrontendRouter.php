<?php

namespace Kryn\CmsBundle\Router;

use Kryn\CmsBundle\Core;
use Kryn\CmsBundle\Model\Base\Node;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

use Kryn\CmsBundle\Model\Base\DomainQuery;
use Kryn\CmsBundle\Model\Content;
use Kryn\CmsBundle\Model\ContentQuery;
use Symfony\Component\Routing\Route as SyRoute;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouteCollection;

class FrontendRouter
{

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Core
     */
    protected $krynCore;

    /**
     * @var RouteCollection
     */
    protected $routes;

    protected $foundPageUrl;

    function __construct(Core $krynCore, Request $request)
    {
        $this->request = $request;
        $this->krynCore = $krynCore;
    }

    /**
     * @param \Symfony\Component\Routing\RouteCollection $routes
     */
    public function setRoutes($routes)
    {
        $this->routes = $routes;
    }

    /**
     * @return \Symfony\Component\Routing\RouteCollection
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * @param Core $krynCore
     */
    public function setKrynCore($krynCore)
    {
        $this->krynCore = $krynCore;
    }

    /**
     * @return Core
     */
    public function getKrynCore()
    {
        return $this->krynCore;
    }

    /**
     * @param Request $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    public function loadRoutes(RouteCollection $routes)
    {
        $uri = $this->getRequest()->getPathInfo();

        if ($this->getKrynCore()->isAdmin()) {
            return null;
        }

        if ($this->searchDomain() && $this->searchPage($uri)) {
            if ($response = $this->checkPageAccess()) {
                //return $response;
            }
            $this->routes = $routes;
            $this->registerMainPage();
            $this->registerPluginRoutes();
        }
    }

    public function checkPageAccess()
    {
        $page = $this->getKrynCore()->getCurrentPage();

        $oriPage = $page;

        if ($page->getAccessFrom() > 0 && ($page->getAccessFrom() > time())) {
            $page = false;
        }

        if ($page->getAccessTo() > 0 && ($page->getAccessTo() < time())) {
            $page = false;
        }

        if ($page->getAccessFromGroups() != '') {

            $access = false;
            $groups = ',' . $page->getAccessFromGroups() . ","; //eg ,2,4,5,

            $cgroups = null;
            if ($page['access_need_via'] == 0) {
//                $cgroups =& $this->getKrynCore()->getClient()->getUser()->getGroups();
            } else {
//                $htuser = $this->getKrynCore()->getClient()->login($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
//
//                if ($htuser['id'] > 0) {
//                    $cgroups =& $htuser['groups'];
//                }
            }

            if ($cgroups) {
                foreach ($cgroups as $group) {
                    if (strpos($groups, "," . $group['group_id'] . ",") !== false) {
                        $access = true;
                    }
                }
            }

            if (!$access) {
                //maybe we have access through the backend auth?
                if ($this->getKrynCore()->getAdminClient()->hasSession()) {
//                    foreach ($this->getKrynCore()->getAdminClient()->getUser()->getGroups() as $group) {
//                        if (strpos($groups, "," . $group . ",") !== false) {
//                            $access = true;
//                            break;
//                        }
//                    }
                }
            }

            if (!$access) {
                $page = false;
            }
        }

        if (!$page && $to = $oriPage->getAccessRedirectto()) {
            if (intval($to) > 0) {
                $to = $this->getKrynCore()->getNodeUrl($to);
            }
            return new RedirectResponse($to);
        }

        if (!$page && $oriPage->getAccessNeedVia() == 1) {
            $response = new Response();
            return $response;
            //create
//            header(
//                'WWW-Authenticate: Basic realm="' .
//                ('Access denied. Maybe you are not logged in or have no access.') . '"'
//            );
//            header('HTTP/1.0 401 Unauthorized');

        }

//        return $page;
    }

    public function registerMainPage()
    {
        $page = $this->getKrynCore()->getCurrentPage();
        $domain = $this->getKrynCore()->getCurrentDomain();

        $clazz = 'Kryn\\CmsBundle\\Controller\\PageController';
        $domainUrl = (!$domain->getMaster()) ? '/' . $domain->getLang() : '';

        $urls =& $this->getKrynCore()->getUtils()->getCachedPageToUrl($domain->getId());
        $id = $page->getId();
        $url = $domainUrl . isset($urls[$id]) ? $urls[$id] : '';

        $controller = $clazz . '::handleAction';

        if ('' !== $url && '/' !== $url && $domain && $domain->getStartnodeId() == $page->getId()) {
            //This is the start page, so add a redirect controller
            $this->routes->add(
                'kryn_page_redirect_to_startpage',
                new SyRoute(
                    $url,
                    array('_controller' => $clazz . '::redirectToStartPageAction')
                )
            );

            $url = $domainUrl;
        }

        $this->routes->add(
            'kryn_page_' . $page->getId().'-'.preg_replace('/\W/', '_', $page->getUrn()),
            new SyRoute(
                $url,
                array('_controller' => $controller)
            )
        );
    }

    /**
     */
    public function registerPluginRoutes()
    {
        $this->getKrynCore()->getStopwatch()->start('Register Plugin Routes');
        //add all router to current router and fire sub-request
        $cacheKey = 'core/node/plugins-' . $this->getKrynCore()->getCurrentPage()->getId();
        $plugins = $this->getKrynCore()->getDistributedCache($cacheKey);

        if (null === $plugins) {
            $plugins = ContentQuery::create()
                ->filterByNodeId($this->getKrynCore()->getCurrentPage()->getId())
                ->filterByType('plugin')
                ->find();

            $this->getKrynCore()->setDistributedCache($cacheKey, serialize($plugins));
        } else {
            $plugins = unserialize($plugins);
        }

        /** @var $plugins Content[] */
        foreach ($plugins as $plugin) {
            if (!$plugin->getContent()) {
                continue;
            }
            $data = json_decode($plugin->getContent(), true);

            if (!$data) {
                continue;
            }

            if (!$data) {
                $this->getKrynCore()->getLogger()->addAlert(
                    sprintf(
                        'On page `%s` [%d] is a invalid plugin `%d`.',
                        $this->getKrynCore()->getCurrentPage()->getTitle(),
                        $this->getKrynCore()->getCurrentPage()->getId(),
                        $plugin->getId()
                    )
                );
                continue;
            }

            $bundleName = isset($data['module']) ? $data['module'] : @$data['bundle'];

            $config = $this->getKrynCore()->getConfig($bundleName);
            if (!$config) {
                $this->getKrynCore()->getLogger()->alert(
                    sprintf(
                        'Bundle `%s` for plugin `%s` on page `%s` [%d] does not not exist.',
                        $bundleName,
                        @$data['plugin'],
                        $this->getKrynCore()->getCurrentPage()->getTitle(),
                        $this->getKrynCore()->getCurrentPage()->getId()
                    )
                );
                continue;
            }

            $pluginDefinition = $config->getPlugin(@$data['plugin']);

            if (!$pluginDefinition) {
                $this->getKrynCore()->getLogger()->addAlert(
                    sprintf(
                        'In bundle `%s` the plugin `%s` on page `%s` [%d] does not not exist.',
                        $bundleName,
                        @$data['plugin'],
                        $this->getKrynCore()->getCurrentPage()->getTitle(),
                        $this->getKrynCore()->getCurrentPage()->getId()
                    )
                );
                continue;
            }

            if ($pluginRoutes = $pluginDefinition->getRoutes()) {
                foreach ($pluginRoutes as $idx => $route) {

                    $clazz = $pluginDefinition->getClass();
                    if (false !== strpos($clazz, '\\')) {
                        $controller = $clazz . '::' . $pluginDefinition->getMethod();
                    } else {
                        $controller = $clazz . '\\' . $pluginDefinition->getClass() . '::' . $pluginDefinition->getMethod();
                    }

                    $defaults = array(
                        '_controller' => $controller,
                        '_content' => $plugin,
                        'options' => @$data['options']
                    );

                    if ($route->getDefaults()) {
                        $defaults = array_merge($defaults, $route->getArrayDefaults());
                    }

                    $this->routes->add(
                        'kryn_plugin_' . ($route->getId() ? : $plugin->getId()).'_'.$idx,
                        new SyRoute(
                            $this->foundPageUrl . '/' . $route->getPattern(),
                            $defaults,
                            $route->getArrayRequirements() ? : array()
                        )
                    );
                }
            }
        }
        $this->getKrynCore()->getStopwatch()->stop('Register Plugin Routes');
    }

    /**
     * Reads the requested URL and try to extract the requested language.
     *
     * @return string Empty string if nothing found.
     * @internal
     */
    public function getPossibleLanguage()
    {
        $uri = $this->getRequest()->getRequestUri();

        if (strpos($uri, '/') > 0) {
            $first = substr($uri, 0, strpos($uri, '/'));
        } else {
            $first = $uri;
        }

        if ($this->isValidLanguage($first)) {
            return $first;
        }

        return '';
    }

    /**
     * Check whether specified pLang is a valid language
     *
     * @param string $lang
     *
     * @return bool
     * @internal
     */
    public function isValidLanguage($lang)
    {
        return false;
        //todo
//        if (!isset($this->getKrynCore()->$config['languages']) && $lang == 'en') {
//            return true;
//        } //default
//
//        if ($this->getKrynCore()->$config['languages']) {
//            return array_search($lang, $this->getKrynCore()->$config['languages']) !== true;
//        } else {
//            return $lang == 'en';
//        }
    }

    public function searchDomain($noRefreshCache = false)
    {
        $request = $this->getRequest();
        $dispatcher = $this->getKrynCore()->getEventDispatcher();

        if ($this->getKrynCore()->isEditMode() && $domainId = $request->get('_kryn_editor_domain')) {
            $hostname = DomainQuery::create()->select('domain')->findPk($domainId);
        } else {
            $hostname = $request->get('_kryn_domain') ? : $request->getHost();
        }

        $stopwatch = $this->getKrynCore()->getStopwatch();

        $title = sprintf('Searching Domain [%s]', $hostname);
        $stopwatch->start($title);

        /** @var \Kryn\CmsBundle\Model\Domain $domain */
        $domain = null;
        $possibleLanguage = $this->getPossibleLanguage();
        $hostnameWithLanguage = $hostname . '/' . $possibleLanguage;

        $cachedDomains = $this->getKrynCore()->getDistributedCache('core/domains');

        if ($cachedDomains) {
            $cachedDomains = @unserialize($cachedDomains);
        }

        if (!is_array($cachedDomains)) {
            $cachedDomains = array();

            $domains = DomainQuery::create()->find();
            foreach ($domains as $domain) {
                $key = $domain->getDomain();
                $langKey = '';

                if (!$domain->getMaster()) {
                    $langKey = '/' . $domain->getLanguage();
                }

                $cachedDomains[$key . $langKey] = $domain;

                if ($domain->getRedirect()) {
                    $redirects = $domain->getRedirect();
                    $redirects = explode(',', str_replace(' ', '', $redirects));
                    foreach ($redirects as $redirectDomain) {
                        $cachedDomains['!redirects'][$redirectDomain . $langKey] = $key . $langKey;
                    }
                }

                if ($domain->getAlias()) {
                    $aliases = $domain->getAlias();
                    $aliases = explode(',', str_replace(' ', '', $aliases));
                    foreach ($aliases as $aliasDomain) {
                        $cachedDomains['!aliases'][$aliasDomain . $langKey] = $key . $langKey;
                    }
                }
            }

            $this->getKrynCore()->setDistributedCache('core/domains', serialize($cachedDomains));
        }

        //search redirect
        if (isset($cachedDomains['!redirects'])
            && (isset($cachedDomains['!redirects'][$hostnameWithLanguage]) && $redirectToDomain = $cachedDomains['!redirects'][$hostnameWithLanguage])
            || (isset($cachedDomains['!redirects'][$hostname]) && $redirectToDomain = $cachedDomains['!redirects'][$hostname])
        ) {
            $domain = $cachedDomains[$redirectToDomain];
            $dispatcher->dispatch('core/domain-redirect', new GenericEvent($domain));

            return null;
        }

        //search alias
        if (isset($cachedDomains['!aliases']) &&
            (($aliasHostname = $cachedDomains['!aliases'][$hostnameWithLanguage]) ||
                ($aliasHostname = $cachedDomains['!aliases'][$hostname]))
        ) {
            $domain = $cachedDomains[$aliasHostname];
            $hostname = $aliasHostname;
        } else {
            if (isset($cachedDomains[$hostname])) {
                $domain = $cachedDomains[$hostname];
            }
        }

        if (!$domain) {
            $dispatcher->dispatch('core/domain-not-found', new GenericEvent($hostname));

            return;
        }

        $this->getKrynCore()->setCurrentDomain($domain);
        $this->getKrynCore()->getPageResponse()->setResourceCompression($domain->getResourceCompression());
        $domain->setRealDomain($hostname);

        $stopwatch->stop($title);

        return $domain;
    }

    protected function searchPage()
    {
        $url = self::getRequest()->getPathInfo();
        $stopwatch = $this->getKrynCore()->getStopwatch();

        $title = sprintf('Searching Page [%s]', $url);
        $stopwatch->start($title);

        $domain = $this->getKrynCore()->getCurrentDomain();
        $urls = $this->getKrynCore()->getUtils()->getCachedUrlToPage($domain->getId());

        $possibleUrl = $url;
        $id = false;

        while (1) {

            if (isset($urls[$possibleUrl])) {
                $id = $urls[$possibleUrl];
                break;
            }

            if (false !== $pos = strrpos($possibleUrl, '/')) {
                $possibleUrl = substr($possibleUrl, 0, $pos);
            } else {
                break;
            }
        }

        if (!$id) {
            //set to startpage
            $id = $domain->getStartnodeId();
            $possibleUrl = '/';
        }

        $this->foundPageUrl = $possibleUrl;

        $url = $possibleUrl;

        if ($url == '/') {
            $pageId = $this->getKrynCore()->getCurrentDomain()->getStartnodeId();

            if (!$pageId > 0) {
                $this->getKrynCore()->getEventDispatcher()->dispatch('core/domain-no-start-page');
            }

        } else {
            $pageId = $id;
        }

        $page = $this->getKrynCore()->getUtils()->getPage($pageId);

        $this->getKrynCore()->setCurrentPage($page);

        $stopwatch->stop($title);

        return $page ? $pageId : null;
    }

}