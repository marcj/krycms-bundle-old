<?php

namespace Kryn\CmsBundle\Router;

use Kryn\CmsBundle\Core;
use Kryn\CmsBundle\Model\Base\Node;
use Symfony\Component\HttpFoundation\Request;

use Kryn\CmsBundle\PluginController;
use Kryn\CmsBundle\Model\Base\DomainQuery;
use Kryn\CmsBundle\Model\Content;
use Kryn\CmsBundle\Model\ContentQuery;
use Kryn\CmsBundle\Model\NodeQuery;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Route as SyRoute;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouteCollection;

class FrontendRouter {

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

    public function handle()
    {

    }

    public function loadRoutes(RouteCollection $routes)
    {
        $uri = $this->getRequest()->getPathInfo();

        if ($this->getKrynCore()->isAdmin()) {
            return;
        }

        if ($this->searchDomain() && $this->searchPage($uri)) {
            $this->routes = $routes;
            $this->registerMainPage();
            $this->registerPluginRoutes();
        }
    }
    
    public function checkPageAccess(Node $page, $withRedirect = true)
    {
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
                $htuser = $this->getKrynCore()->getClient()->login($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);

                if ($htuser['id'] > 0) {
                    $cgroups =& $htuser['groups'];
                }
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

        if (!$page && $withRedirect && $oriPage->getAccessNeedVia() == 0) {

            if ($oriPage->getAccessRedirectto() + 0 > 0) {
                die('todo redirect');
//                $this->getKrynCore()->redirectToPage($oriPage->getAccessRedirectto());
            }
        }

        if (!$page && $withRedirect && $oriPage->getAccessNeedVia() == 1) {
            header(
                'WWW-Authenticate: Basic realm="' .
                ('Access denied. Maybe you are not logged in or have no access.') . '"'
            );
            header('HTTP/1.0 401 Unauthorized');

            exit;
        }

        return $page;
    }

    public function registerMainPage()
    {
        $page = $this->getKrynCore()->getCurrentPage();
        $domain = $this->getKrynCore()->getCurrentDomain();
//        $page = self::checkPageAccess($page);

        $clazz = 'Kryn\\CmsBundle\\Controller\\PageController';
        $domainUrl = (!$domain->getMaster()) ? '/' . $domain->getLang() : '';

        $urls =& $this->getKrynCore()->getUtils()->getCachedPageToUrl($domain->getId());
        $id = $page->getId();
        $url = $domainUrl . isset($urls[$id]) ? $urls[$id] : '';

        $controller = $clazz . '::handle';

        if ('' !== $url && '/' !== $url && $domain && $domain->getStartnodeId() == $page->getId()) {
            //This is the start page, so add a redirect controller
            $this->routes->add(
                $this->routes->count() + 1,
                new SyRoute(
                    $url,
                    array('_controller' => $clazz . '::redirectToStartPage')
                )
            );

            $url = $domainUrl;
        }

        $this->routes->add(
            $this->routes->count() + 1,
            new SyRoute(
                $url,
                array('_controller' => $controller)
            )
        );
    }

    protected function isEditMode()
    {
        return false;
    }

//    public function firePluginRequest()
//    {
//        if (null !== $this->pluginPath) {
//            //add all router to current router and fire sub-request
//            $cacheKey = 'core/node/plugins-' . $this->getKrynCore()->getCurrentPage()->getId();
//            $plugins = $this->getKrynCore()->getDistributedCache($cacheKey);
//
//            if (null === $plugins) {
//                $plugins = ContentQuery::create()
//                    ->filterByNodeId($this->getKrynCore()->getCurrentPage()->getId())
//                    ->filterByType('plugin')
//                    ->find();
//
//                $this->getKrynCore()->setDistributedCache($cacheKey, serialize($plugins));
//            } else {
//                $plugins = unserialize($plugins);
//            }
//
//            $this->registerPluginRoutes($plugins);
//
//            //remove FrontendController of the current routeCollection to prevent endless-loop
//            $router = $this->getKrynCore()->getRouter();
//            $routes = $router->getRouteCollection();
//
//            $cFrontendController = get_class($this) . '::frontendAction';
//            $frontendController = 'Kryn\CmsBundle\Controller\FrontendController::frontendAction';
//            foreach ($routes as $idx => $route) {
//                /** @var \Symfony\Component\Routing\Route $route */
//                if ($frontendController == $route->getDefault('_controller') ||
//                    $cFrontendController == $route->getDefault('_controller')
//                ) {
//                    $routes->remove($idx);
//                }
//            }
//
//            $request = clone $this->getKrynCore()->getRequest();
//            $request->attributes = new ParameterBag();
//            $response = $this->getKrynCore()->getKernel()->handle($request, HttpKernelInterface::SUB_REQUEST);
//            var_dump($response);
//        }
//    }

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

            $bundleName = isset($data['module']) ? $data['module'] : $data['bundle'];

            $config = $this->getKrynCore()->getConfig($bundleName);
            if (!$config) {
                $this->getKrynCore()->getLogger()->addAlert(
                    sprintf(
                        'Bundle `%s` for plugin `%s` on page `%s` [%d] does not not exist.',
                        $bundleName,
                        $data['plugin'],
                        $this->getKrynCore()->getCurrentPage()->getTitle(),
                        $this->getKrynCore()->getCurrentPage()->getId()
                    )
                );
                continue;
            }

            $pluginDefinition = $config->getPlugin($data['plugin']);

            if (!$pluginDefinition) {
                $this->getKrynCore()->getLogger()->addAlert(
                    sprintf(
                        'In bundle `%s` the plugin `%s` on page `%s` [%d] does not not exist.',
                        $bundleName,
                        $data['plugin'],
                        $this->getKrynCore()->getCurrentPage()->getTitle(),
                        $this->getKrynCore()->getCurrentPage()->getId()
                    )
                );
                continue;
            }

            if ($pluginRoutes = $pluginDefinition->getRoutes()) {
                foreach ($pluginRoutes as $route) {

                    $clazz = $pluginDefinition->getClass();
                    if (false !== strpos($clazz, '\\')) {
                        $controller = $clazz . '::' . $pluginDefinition->getMethod();
                    } else {
                        $controller = $clazz . '\\' . $pluginDefinition->getClass(
                            ) . '::' . $pluginDefinition->getMethod();
                    }

                    $defaults = array(
                        '_controller' => $controller,
                        '_content' => $plugin,
                        'options' => $data['options']
                    );

                    if ($route->getDefaults()) {
                        $defaults = array_merge($defaults, $route->getArrayDefaults());
                    }

                    $this->routes->add(
                        $route->getId() ? : $this->routes->count() + 1,
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

        if ($this->isEditMode() && $domainId = $request->get('_kryn_editor_domain')) {
            $hostname = DomainQuery::create()->select('domain')->findPk($domainId);
        } else {
            $hostname = $request->get('_kryn_domain') ? : $request->getHost();
        }

        $stopwatch = $this->getKrynCore()->getStopwatch();

        $title = sprintf('Searching Domain [%s]', $hostname);
        $stopwatch->start($title);

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
        $domain->setRealDomain($hostname);

        $stopwatch->stop($title);

        return $domain;
    }

    protected function searchPage($path)
    {
        $url = self::getRequest()->getPathInfo();
        $stopwatch = $this->getKrynCore()->getStopwatch();

        $title = sprintf('Searching Page [%s]', $url);
        $stopwatch->start($title);

        $domain = $this->getKrynCore()->getCurrentDomain()->getId();
        $urls = $this->getKrynCore()->getUtils()->getCachedUrlToPage($domain);

        //extract extra url attributes
        $found = $end = false;
        $possibleUrl = $next = $url;
        $oriUrl = $possibleUrl;

        do {
            $id = isset($urls[$possibleUrl]) ? $urls[$possibleUrl] : 0;

            if ($id > 0 || $possibleUrl == '') {
                $found = true;
            } elseif (!$found) {
                $id = isset($urls['alias']) && isset($urls['alias'][$possibleUrl]) ? $urls['alias'][$possibleUrl] : 0;
                if ($id > 0) {
                    $found = true;
                    //we found a alias
                    die('redirect to ' . $id);
                    //$this->getKrynCore()->redirectToPage($id);
                } else {
                    $possibleUrl = $next;
                }
            }

            if ($next == false) {
                $end = true;
            } else {
                /*
                //maybe we found a alias in the parens with have a alias with "withsub"
                $aliasId = $this->getKrynCore()->$urls['alias'][$next . '/%'];

                if ($aliasId) {

                    //links5003/test => links5003_5/test

                    $aliasPageUrl = $this->getKrynCore()->$urls['id']['id=' . $aliasId];

                    $urlAddition = str_replace($next, $aliasPageUrl, $url);

                    $toUrl = $urlAddition;

                    //go out, and redirect the user to this url
                    $this->getKrynCore()->redirect($urlAddition);
                    $end = true;
                }
                */
            }

            $pos = strrpos($next, '/');
            if ($pos !== false) {
                $next = substr($next, 0, $pos);
            } else {
                $next = false;
            }

        } while (!$end);

        $diff = substr($url, strlen($possibleUrl), strlen($url));

        $this->foundPageUrl = $possibleUrl;
//        $this->pluginPath = '/' !== $diff ? substr($diff, 1) : null;

        if (substr($diff, 0, 1) != '/') {
            $diff = '/' . $diff;
        }

        $extras = explode("/", $diff);
        if (count($extras) > 0) {
            foreach ($extras as $nr => $extra) {
                $_REQUEST['e' . $nr] = $extra;
            }
        }
        $url = $possibleUrl;

//        $this->getKrynCore()->$isStartpage = false;

        if ($url == '') {
            $pageId = $this->getKrynCore()->getCurrentDomain()->getStartnodeId();

            if (!$pageId > 0) {
                $this->getKrynCore()->getEventDispatcher()->dispatch('core/domain-no-start-page');
            }

//            $this->getKrynCore()->$isStartpage = true;
        } else {
            $pageId = $id;
        }

        $page = $this->getKrynCore()->getUtils()->getPage($pageId);

        $this->getKrynCore()->setCurrentPage($page);

        $stopwatch->stop($title);

        return $page ? $pageId : null;
    }

}