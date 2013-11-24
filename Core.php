<?php

namespace Kryn\CmsBundle;

use Flysystem\Filesystem;
use Kryn\CmsBundle\Client\ClientAbstract;
use Kryn\CmsBundle\Configuration\Client;
use Kryn\CmsBundle\Configuration\SystemConfig;
use Kryn\CmsBundle\Exceptions\BundleNotFoundException;
use Kryn\CmsBundle\Model\Node;
use Kryn\CmsBundle\Propel\PropelHelper;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Request;

class Core
{

    /**
     * @var Configuration\Configs|Configuration\Bundle[]
     */
    protected $configs;

    /**
     * @var \Symfony\Component\DependencyInjection\Container
     */
    protected $container;

    /**
     * @var PageResponse
     */
    protected $pageResponse;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var PropelHelper
     */
    protected $propelHelper;

    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * Client instance in administration area.
     *
     * @var ClientAbstract
     */
    protected $adminClient;

    /**
     * Frontend client instance.
     *
     * @var ClientAbstract
     */
    protected $client;

    /**
     * @var Utils
     */
    protected $utils;

    /**
     * @var Configuration\Cache
     */
    protected $cache;

    /**
     * @var Model\Domain
     */
    protected $currentDomain;

    /**
     * @var Model\Node
     */
    protected $currentPage;

    /**
     * @param $container
     */
    function __construct($container)
    {
        $this->container = $container;

        /*
         * Propel orm initialisation.
         */
        $this->propelHelper = new PropelHelper($this);

        if (!$this->propelHelper->loadConfig()) {
            $this->propelHelper->init();
            $this->propelHelper->loadConfig();
        }

//        if ($domainClientConfig->isAutoStart()) {
//            $this->getClient()->start();
//        }

    }

    /**
     * @return \Flysystem\FilesystemInterface
     */
    public function getFileSystem()
    {
        return $this->container->get('kryn.filesystem.local');
    }

    /**
     * @return \Flysystem\FilesystemInterface
     */
    public function getCacheFileSystem()
    {
        return $this->container->get('kryn.filesystem.cache');
    }

    /**
     * @return \Kryn\CmsBundle\Filesystem\WebFilesystem
     */
    public function getWebFileSystem()
    {
        return $this->container->get('kryn.filesystem.web');
    }

    /**
     * @return Objects
     */
    public function getObjects()
    {
        return $this->container->get('kryn.objects');
    }

    /**
     * @return \Symfony\Component\HttpKernel\Log\LoggerInterface
     */
    public function getLogger()
    {
        return $this->container->get('logger');
    }

    public function isAdmin()
    {
        return false !== strpos($this->getRequest()->attributes->get('_controller'), 'Controller\Admin');
    }

    /**
     * Marks a code as invalidate beginning at $time.
     * This is the distributed cache controller. Use it if you want
     * to invalidate caches on a distributed backend (use by `setCache()`
     * and `setDistributedCache()`.
     *
     * You don't have to define the full key, instead you can pass only the starting part of the key.
     * This means, if you have following caches defined:
     *
     *   - news/list/2
     *   - news/list/3
     *   - news/list/4
     *   - news/comments/134
     *   - news/comments/51
     *
     * you can mark all listing caches as invalid by calling
     *   - invalidateCache('news/list');
     *
     * or mark all caches as invalid which starts with `news/` you can call:
     *   - invalidateCache('news');
     *
     *
     * The invalidation mechanism explodes the key by / and checks all levels whether they're marked
     * as invalid (through a microsecond timestamp) or not.
     *
     * Default is $time is `mark all caches as invalid which are older than CURRENT`.
     *
     * @param  string  $key
     * @param  integer $time Unix timestamp. Default is microtime(true). Uses float for ms.
     *
     * @return boolean
     */
    public function invalidateCache($key, $time = null)
    {
        if ($this->isDebugMode()) {
            $time = $time ?: microtime(true);
            $micro = sprintf("%06d",($time - floor($time)) * 1000000);
            $this->getLogger()->addDebug(
                sprintf('Invalidate `%s` (from %s)', $key, date('F j, Y, H:i:s.'.$micro, $time)));
        }

        return $this->getCache()->invalidate($key, $time ?: microtime(true));
    }

    /**
     * @return bool
     */
    public function isDebugMode()
    {
        return $this->getSystemConfig()->getDebug();
    }

    /**
     * Returns a distributed cache value.
     *
     * @see setDistributedCache() for more information
     *
     * @param string $key
     *
     * @return mixed Null if not found
     */
    public function getDistributedCache($key)
    {
        $fastCache = $this->getFastCache();
        $distributedCache = $this->getCache();

        $invalidationKey = $key . '/!invalidationCheck';
        $timestamp = $distributedCache->get($invalidationKey);
        $cache = null;

        if ($timestamp !== null) {
            $cache = $fastCache->get($key);
            if ($cache['timestamp'] == $timestamp) {
                return $cache['data'];
            }
        }

        return null;
    }

    /**
     * Sets a distributed cache.
     *
     * This stores a ms timestamp on the distributed cache (Kryn::setCache())
     * and the actual data on the high-speed cache driver (Kryn::setFastCache()).
     * This mechanism makes sure, you gain the maximum performance by using the
     * fast cache driver to store the actual data and using the distributed cache driver
     * to store a ms timestamp where we can check (over several kryn.cms installations)
     * whether the cache is still valid or not.
     *
     * Use Kryn::invalidateCache($key) to invalidate this cache.
     * You don't have to define the full key, instead you can pass only a part of the key.
     *
     * @see invalidateCache for more information.
     *
     * Don't mix the usage of getDistributedCache() and getCache() since this method
     * stores extra values at the value, which makes getCache() returning something invalid.
     *
     * @param string $key
     * @param mixed  $value    Only simple data types. Serialize your value if you have objects/arrays.
     * @param int    $lifeTime
     *
     * @return boolean
     * @static
     */
    public function setDistributedCache($key, $value, $lifeTime = null)
    {
        $fastCache = $this->getFastCache();
        $distributedCache = $this->getCache();

        $invalidationKey = $key . '/!invalidationCheck';
        $timestamp = microtime();

        $cache['data'] = $value;
        $cache['timestamp'] = $timestamp;

        return $fastCache->set($key, $cache, $lifeTime) && $distributedCache->set(
            $invalidationKey,
            $timestamp,
            $lifeTime
        );
    }

    /**
     * @return ClientAbstract
     */
    public function getAdminClient()
    {
        $systemClientConfig = $this->getSystemConfig()->getClient(true);
        $defaultClientClass = $systemClientConfig->getClass();

        if ($this->isAdmin()) {
            $this->client = $this->adminClient = new $defaultClientClass($this, $systemClientConfig);
            $this->adminClient->start();
        }

        return $this->adminClient;
    }

    /**
     * @return ClientAbstract
     *
     */
    public function getClient()
    {
        $systemClientConfig = $this->getSystemConfig()->getClient(true);
        $defaultClientClass = $systemClientConfig->getClass();

        $domainClientConfigXml = $this->getCurrentDomain() ? $this->getCurrentDomain()->getSessionProperties() : '';
        $domainClientConfig = $systemClientConfig;

        if ($domainClientConfigXml) {
            $domainClientConfig = new Client($domainClientConfigXml);
        }

        $domainClientClass = $domainClientConfig->getClass() ?: $defaultClientClass;

        return $this->client = new $domainClientClass($this, $domainClientConfig);
    }

    /**
     * Load SystemConfig to $this->systemConfig
     */
    protected function loadSy22323stemConfig()
    {
        $fastestCacheClass = Cache\AbstractCache::getFastestCacheClass();

        $configArray = [];
        $this->systemConfig = new SystemConfig($configArray);

        return;
//        $configFile = PATH . 'app/config/config.xml';
//
//        if (file_exists($configFile)) {
//            if ('\Core\Cache\Files' === $fastestCacheClass->getClass()) {
//                $systemConfigCached = @file_get_contents('app/config/config.cache.php');
//            } else {
//                $systemConfigCached = static::getFastCache('core/config');
//            }
//            $systemConfigHash = md5($fastestCacheClass->getClass() . filemtime($configFile));
//
//            if ($systemConfigCached) {
//                $systemConfigCached = unserialize($systemConfigCached);
//                if (is_array($systemConfigCached) && $systemConfigCached['md5'] == $systemConfigHash) {
//                    self::$config = $systemConfigCached['data'];
//                }
//            }
//
//            if (!self::$config) {
//                static::$config = new SystemConfig(file_get_contents($configFile));
//                $cached = serialize(
//                    [
//                        'md5' => $systemConfigHash,
//                        'data' => self::$config
//                    ]
//                );
//                if ('\Core\Cache\Files' === $fastestCacheClass->getClass()) {
//                    @file_put_contents('app/config/config.cache.php', $cached);
//                } else {
//                    self::setFastCache('core/config', $cached);
//                }
//            }
//        } else {
//            static::$config = new SystemConfig();
//        }
    }

    /**
     * @return SystemConfig
     */
    public function getSystemConfig()
    {
        if (null === $this->systemConfig) {
            //$fastestCacheClass = Cache\AbstractCache::getFastestCacheClass();

            $configArray = [];
            $this->systemConfig = new SystemConfig($configArray, $this);
            $database = $this->container->get('kryn.configuration.database');
            $this->systemConfig->setDatabase($database);
        }

        return $this->systemConfig;
        //return $this->container->get('kryn.configuration');
    }

    /**
     * @return Model\Node
     */
    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    /**
     * @return Model\Domain
     */
    public function getCurrentDomain()
    {
        return $this->currentDomain;
    }

    /**
     * @param Model\Node $currentPage
     */
    public function setCurrentPage($currentPage)
    {
        $this->currentPage = $currentPage;
    }

    /**
     * @param Model\Domain $currentDomain
     */
    public function setCurrentDomain($currentDomain)
    {
        $this->currentDomain = $currentDomain;
    }

    /**
     * @return PageResponse
     */
    public function getPageResponse()
    {
        return $this->container->get('kryn.page.response');
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        if (null === $this->request) {
            $this->request = $this->container->get('request'); //Request::createFromGlobals();
        }

        return $this->request;
    }

    /**
     * @return \AppKernel
     */
    public function getKernel()
    {
        return $this->container->get('kernel');
    }

    /**
     * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    public function getEventDispatcher()
    {
        return $this->container->get('event_dispatcher');
    }

    /**
     * @return \Kryn\CmsBundle\Cache\CacheInterface
     */
    public function getFastCache()
    {
        return $this->container->get('kryn.cache.fast');
    }

    /**
     * @return Navigation
     */
    public function getNavigation()
    {
        return $this->container->get('kryn.navigation');
    }

    /**
     * @return \Kryn\CmsBundle\Cache\AbstractCache
     */
    public function getCache()
    {
        if (null === $this->cache) {
            $cache = $this->getSystemConfig()->getCache(true);
            $class = $cache->getClass();
            $this->cache = new $class($cache);
        }

        return $this->cache;
    }

    /**
     * @return \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface
     */
    public function getTemplating()
    {
        $this->container->get('twig')->addGlobal('baseUrl', $this->getRequest()->getBaseUrl() . '/');
        return $this->container->get('templating');
    }

    /**
     * @return \Symfony\Bundle\FrameworkBundle\Routing\Router
     */
    public function getRouter()
    {
        return $this->container->get('router');
    }

    /**
     * @return ContentRender
     */
    public function getContentRender()
    {
        return $this->container->get('kryn.content.render');
    }

    /**
     * @return ContentTypes\ContentTypes
     */
    public function getContentTypes()
    {
        return $this->container->get('kryn.content.types');
    }

    /**
     * @return string
     */
    public function getWebCacheDir()
    {
        return 'web/cache/';
    }

    /**
     * @return Utils
     */
    public function getUtils()
    {
        if (null === $this->utils) {
            $this->utils = new Utils($this);
        }

        return $this->utils;
    }

    /**
     * @param $bundleName
     *
     * @return Configuration\Bundle
     */
    public function getConfig($bundleName)
    {
        return $this->getConfigs()->getConfig($bundleName);
    }

    /**
     * @return Configuration\Configs|Configuration\Bundle[]
     */
    public function getConfigs()
    {
        if (null === $this->configs) {
            $this->loadBundleConfigs();
        }

        return $this->configs;
    }

    public function getNodeUrl($nodeOrId, $fullUrl = false)
    {
        $id = $nodeOrId;
        if ($nodeOrId instanceof Node) {
            $id = $nodeOrId->getId();
        }

        if (!$nodeOrId) {
            $nodeOrId = $this->getCurrentPage();
        }

        $domainId = $nodeOrId instanceof Node ? $nodeOrId->getDomainId() : $this->getUtils()->getDomainOfPage($nodeOrId + 0);
        $currentDomain = $this->getCurrentDomain();

        $urls =& $this->getUtils()->getCachedPageToUrl($domainId);
        $url = isset($urls[$id]) ? $urls[$id] : '';

        //do we need to add app_dev.php/ or something?
        $prefix = substr($this->getRequest()->getBaseUrl(), strlen($this->getRequest()->getBasePath()));
        if (false !== $prefix) {
            $url = substr($prefix, 1) . $url;
        }

        if ($fullUrl || !$currentDomain || $domainId != $currentDomain->getId()) {
            $domain = $currentDomain ? : $this->getUtils()->getDomain($domainId);

            $domainName = $domain->getRealDomain();
            if ($domain->getMaster() != 1) {
                $url = $domainName . $domain->getPath() . $domain->getLang() . '/' . $url;
            } else {
                $url = $domainName . $domain->getPath() . $url;
            }

            $url = 'http' . ($this->getRequest()->isSecure() ? 's' : '') . '://' . $url;
        }

        //crop last /
        if (substr($url, -1) == '/') {
            $url = substr($url, 0, -1);
        }

        //crop first /
        if (substr($url, 0, 1) == '/') {
            $url = substr($url, 1);
        }

        if ($url == '/') {
            $url = '.';
        }

        return $url;
    }

    /**
     * @param string $path
     * @param string $suffix
     * @return string
     * @throws Exceptions\BundleNotFoundException
     */
    public function resolvePath($path, $suffix = '')
    {
        $path = preg_replace('/:+/', '/', $path);
        preg_match('/\@?([a-zA-Z0-9\-_\.\\\\]+)/', $path, $matches);
        if ($matches && isset($matches[1])) {
            try {
                $bundle = $this->getKernel()->getBundle($matches[1]);
            } catch (\InvalidArgumentException $e) {
                throw new BundleNotFoundException(sprintf(
                    'Bundle for `%s` (%s) not found.',
                    $matches[1],
                    $path
                ), 0, $e);
            }
            if ($suffix && '/' !== $suffix[0]) {
                $suffix = '/' . $suffix;
            }

            $path = substr($path, strlen($matches[1]));

            if ((!$suffix || '/' === substr($suffix, -1)) && '/' === $path[0]) {
                $path = substr($path, 1);
            }

            $path = $bundle->getPath() . $suffix . $path;
        }

        return $path;
    }

    /**
     * @param string $path
     * @return mixed
     */
    public function resolvePublicPath($path)
    {
        return $this->resolvePath($path, 'Resources/public');
    }

    /**
     * @param string $path
     * @return string
     * @throws Exceptions\BundleNotFoundException
     */
    public function resolveWebPath($path)
    {
        if (strpos($path, '@') !== false) {
            preg_match('/\@([a-zA-Z0-9\-_\.\\\\]+)/', $path, $matches);
            if ($matches && isset($matches[1])) {
                try {
                    $bundle = $this->getKernel()->getBundle($matches[1]);
                } catch (\InvalidArgumentException $e) {
                    throw new BundleNotFoundException(sprintf(
                        'Bundle for `%s` (%s) not found.',
                        $matches[1],
                        $path
                    ), 0, $e);
                }
                $targetDir = 'bundles/' . preg_replace('/bundle$/', '', strtolower($bundle->getName()));

                return str_replace($matches[0], $targetDir, $path);
            }
        }

        return 'bundles/' . $path;
    }

    /**
     * @param bool $forceNoCache
     */
    public function loadBundleConfigs($forceNoCache = false)
    {
        $cached = $this->getFastCache()->get('core/configs');
        $bundles = array_keys($this->container->getParameter('kernel.bundles'));

        $configs = new Configuration\Configs($this);

        $hashes = [];
        foreach ($bundles as $bundleName) {
            $hashes[] = $configs->getConfigHash($bundleName);
        }
        $hash = md5(implode('.', $hashes));

        if ($cached) {
            $cached = unserialize($cached);
            if (is_array($cached) && $cached['md5'] == $hash) {
                $this->configs = $cached['data'];
            }
        }

        if (!$this->configs) {
            $this->configs = new Configuration\Configs($this, $bundles);
            $this->configs->setup();
            $cached = serialize(
                [
                    'md5' => $hash,
                    'data' => $this->configs
                ]
            );

            $this->getFastCache()->set('core/configs', $cached);
        }

        foreach ($this->configs as $bundleConfig) {
            if ($events = $bundleConfig->getListeners()) {
                foreach ($events as $event) {

                    $fn = function (GenericEvent $genericEvent) use ($event) {
                        if ($event->isCallable($genericEvent)) {
                            $event->call($genericEvent);
                        }
                    };
                    $this->getEventDispatcher()->addListener($event->getKey(), $fn);
                }
            }
        }
    }

}