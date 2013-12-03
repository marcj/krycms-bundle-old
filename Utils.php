<?php

namespace Kryn\CmsBundle;

use Kryn\CmsBundle\Model\AppLockQuery;
use Kryn\CmsBundle\Model\Base\NodeQuery;
use Symfony\Component\HttpFoundation\Response;

class Utils
{
    private $inErrorHandler = false;

    public $latency = array();

    protected $cachedPageToUrl = [];

    /**
     * @var Core
     */
    protected $krynCore;

    function __construct(Core $krynCore)
    {
        $this->krynCore = $krynCore;
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

    public function getComposerArray($bundleClass)
    {
        $path = $this->getKrynCore()->getBundleDir($bundleClass);
        $fs = $this->getKrynCore()->getFileSystem();
        if ($fs->has($file = $path . '/composer.json')) {
            return json_decode($fs->read($file), true);
        }
    }

    /**
     * Creates a temp folder and returns its path.
     * Please use TempFile::createFolder() class instead.
     *
     * @static
     * @internal
     *
     * @param  string $prefix
     * @param  bool   $fullPath Returns the full path on true and the relative to the current TempFolder on false.
     *
     * @return string Path with trailing slash
     */
    public function createTempFolder($prefix = '', $fullPath = true)
    {
        $tmp = $this->getKrynCore()->getKernel()->getCacheDir();

        do {
            $path = $tmp . $prefix . dechex(time() / mt_rand(100, 500));
        } while (is_dir($path));

        mkdir($path);

        if ('/' !== substr($path, -1)) {
            $path .= '/';
        }

        return $fullPath ? $path : substr($path, strlen($tmp));
    }

    /**
     * @param string $text
     */
    public function showFullDebug($text = null)
    {
        $exception = new \InternalErrorException();
        $exception->setMessage($text ? : 'Debug stop.');

        static::exceptionHandler($exception);
    }

    /**
     * Returns Domain object
     *
     * @param int $domainId If not defined, it returns the current domain.
     *
     * @return \Kryn\CmsBundle\Model\Domain
     * @static
     */
    public function getDomain($domainId = null)
    {
        if (!$domainId) {
            return self::$domain;
        }

        if ($domainSerialized = $this->getKrynCore()->getDistributedCache('core/object-domain/' . $domainId)) {
            return unserialize($domainSerialized);
        }

        $domain = Model\DomainQuery::create()->findPk($domainId);

        if (!$domain) {
            return false;
        }

        $this->getKrynCore()->setDistributedCache('core/object-domain/' . $domainId, serialize($domain));

        return $domain;
    }

    /**
     * Returns a super fast cached Page object.
     *
     * @param  int $pageId If not defined, it returns the current page.
     *
     * @return \Page
     * @static
     */
    public function getPage($pageId = null)
    {
        if (!$pageId) {
            return $this->getKrynCore()->getCurrentPage();
        }

        $data = $this->getKrynCore()->getDistributedCache('core/object/node/' . $pageId);

        if (!$data) {
            $page = NodeQuery::create()->findPk($pageId);
            $this->getKrynCore()->setDistributedCache('core/object/node/' . $pageId, serialize($page));
        } else {
            $page = unserialize($data);
        }

        return $page ? : false;
    }

    /**
     * Returns the domain of the given $id page.
     *
     * @static
     *
     * @param  integer $id
     *
     * @return integer|null
     */
    public function getDomainOfPage($id)
    {
        $id2 = null;

        $page2Domain = $this->getKrynCore()->getDistributedCache('core/node/toDomains');

        if (!is_array($page2Domain)) {
            $page2Domain = $this->updatePage2DomainCache();
        }

        $id = ',' . $id . ',';
        foreach ($page2Domain as $domain_id => &$pages) {
            $pages = ',' . $pages . ',';
            if (strpos($pages, $id) !== false) {
                $id2 = $domain_id;
            }
        }

        return $id2;
    }

    public function updatePage2DomainCache()
    {
        $r2d = array();
        $items = NodeQuery::create()
            ->select(['Id', 'DomainId'])
            ->find();

        foreach ($items as $item) {
            $r2d[$item['DomainId']] = (isset($r2d[$item['DomainId']]) ? $r2d[$item['DomainId']] : '') . $item['Id'] . ',';
        }

        $this->getKrynCore()->setDistributedCache('core/node/toDomains', $r2d);

        return $r2d;
    }

    /**
     * @param  integer $domainId
     *
     * @return array
     */
    public function &getCachedPageToUrl($domainId)
    {
        if (isset($cachedPageToUrl[$domainId])) {
            return $cachedPageToUrl[$domainId];
        }

        $cachedPageToUrl[$domainId] = array_flip($this->getCachedUrlToPage($domainId));

        return $cachedPageToUrl[$domainId];
    }

    public function &getCachedUrlToPage($domainId)
    {
        $cacheKey = 'core/urls/' . $domainId;
        $urls = $this->getKrynCore()->getDistributedCache($cacheKey);

        if (!$urls) {

            $nodes = NodeQuery::create()
                ->select(array('id', 'urn', 'lvl', 'type'))
                ->filterByDomainId($domainId)
                ->orderByBranch()
                ->find();

            //build urls array
            $urls = array();
            $level = array();

            foreach ($nodes as $node) {
                if ($node['lvl'] == 0) {
                    continue;
                } //root
                if ($node['type'] == 3) {
                    continue;
                } //deposit

                if ($node['type'] == 2 || $node['urn'] == '') {
                    //folder or empty url
                    $level[$node['lvl'] + 0] = isset($level[$node['lvl'] - 1]) ? $level[$node['lvl'] - 1] : '';
                    continue;
                }

                $url = isset($level[$node['lvl'] - 1]) ? $level[$node['lvl'] - 1] : '';
                $url .= '/' . $node['urn'];

                $level[$node['lvl'] + 0] = $url;

                $urls[$url] = $node['id'];
            }

            $this->getKrynCore()->setDistributedCache($cacheKey, $urls);
        }

        return $urls;
    }

    /**
     * Returns debug information.
     *
     * @return string
     */
    public function getDebug()
    {
        $routes = [];
        /** @var \Symfony\Component\Routing\Route[] $setupRoutes */
        $setupRoutes = iterator_to_array($this->getKrynCore()->$routes->getIterator());
        foreach ($setupRoutes as $route) {
            $routes[] = [
                'path' => $route->getPath(),
                'defaults' => $route->getDefaults(),
                'options' => $route->getOptions(),
            ];
        }

        $data['routes'] = $routes;

        $html = '';

        return $html;
    }

    public function exceptionHandler(\Exception $exception)
    {
        $output = '';
        for ($i = ob_get_level(); $i >= 0; $i--) {
            $output .= ob_get_clean();
        }

        if (!$this->getKrynCore()->getSystemConfig()->getErrors()->getDisplay()) {
            $this->getKrynCore()->internalError(
                'Internal Server Error',
                tf(
                    'The server encountered an internal error and was unable to complete your request. Please contact the administrator. %s',
                    $this->getKrynCore()->getSystemConfig()->getEmail() ? : '[No E-Mail]'
                )
            );
        }

        if ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') ||
            php_sapi_name() == 'cli'
        ) {
            $response = array(
                'status' => 500,
                'error' => get_class($exception),
                'message' => $exception->getMessage() . "[$output]",
                'previous' => $exception->getPrevious()
            );

            if ($this->getKrynCore()->getSystemConfig()->getErrors()->getDisplayRest()) {
                $response['file'] = $exception->getFile();
                $response['line'] = $exception->getLine();
                $response['backstrace'] = $exception->getTrace();
            }

            die(json_encode($response));
        }

        if ($this->inErrorHandler === true) {
            print "Critical-Error in Error-View Controller: \n";
            print get_class($exception) . ', ' . $exception->getMessage() . ' in ' .
                $exception->getFile() . ' +' . $exception->getLine();
            if (function_exists('xdebug_print_function_stack')) {
                xdebug_print_function_stack();
            } else {
                debug_print_backtrace();
            }
            exit;
        }

        $this->inErrorHandler = true;

        $data = array(
            'output' => $output
        );
        if ($this->getKrynCore()->getSystemConfig()->getErrors()->getStackTrace()) {
            $exceptions = array();
            self::extractException($exception, $exceptions);
            $data['exceptions'] = $exceptions;
        } else {
            $data['exceptions'] = array(
                array(
                    'title' => get_class($exception),
                    'message' => $exception->getMessage()
                )
            );
        }

        $this->getKrynCore()->getLogger()->error(
            sprintf('Exception %s: %s', $data['exceptions'][0]['title'], $data['exceptions'][0]['message'])
        );
        $exceptionView = $this->getKrynCore()->getInstance()->renderView(
            '@CoreBundle/internal-error.html.smarty',
            $data,
            false
        );
        $this->getKrynCore()->getLogRequest()->setExceptions($exceptionView);
        $response = new Response($exceptionView, 500);
        $response->send();
        $this->getKrynCore()->getLogRequest()->setHeaders($response, $this->getKrynCore()->getRequest());
        $this->getKrynCore()->getLogRequest()->save();
        exit;
    }

    public function extractException(\Exception $exception, array &$exception2s)
    {
        $exception2 = array(
            'title' => get_class($exception),
            'message' => $exception->getMessage(),
            'line' => $exception->getLine(),
            'file' => $exception->getFile()
        );

        $backtrace = [$exception2] + $exception->getTrace();

        $traces = array();
        $count = count($backtrace);
        foreach ($backtrace as $trace) {
            $trace['file'] = substr($trace['file'], strlen(PATH));
            $trace['id'] = $count--;
            if ($trace['file'] == 'src/Core/global/internal.global.php' && $trace['line'] == 40) {
                continue;
            }

            $code = self::getFileContent($trace['file'], $trace['line']);
            $trace['countLines'] = substr_count($code, "\n");

            $inserted = false;
            if (false === strpos($trace['code'], '<?php')) {
                $code = "<?php\n" . $code;
                $inserted = true;
            }
            $code = highlight_string($code, true);

            if ($inserted) {
                $code = preg_replace('/&lt;\?php<br \/>/', '', $code, 1);
            }

            $trace['startLine'] = 10 > $trace['line'] ? 1 : ($trace['line'] - 10);
            if (1 >= $trace['startLine']) {
                $trace['startLine'] = 1;
            }


            $trace['code'] = $code;
            $traces[] = $trace;
        }

        $exception2['backtrace'] = $traces;
        $exception2['file'] = substr($exception->getFile(), strlen(PATH));
        $exception2s[] = $exception2;

        if ($exception->getPrevious()) {
            self::extractException($exception->getPrevious(), $exception2s);
        }
    }

    /**
     * @param array $files
     * @param string $includePath The directory where to compressed css is. with trailing slash!
     *
     * @return string
     */
    public function compressCss(array $files, $includePath = '')
    {
        $toGecko = array(
            "-moz-border-radius-topleft",
            "-moz-border-radius-topright",
            "-moz-border-radius-bottomleft",
            "-moz-border-radius-bottomright",
            "-moz-border-radius",
        );

        $toWebkit = array(
            "-webkit-border-top-left-radius",
            "-webkit-border-top-right-radius",
            "-webkit-border-bottom-left-radius",
            "-webkit-border-bottom-right-radius",
            "-webkit-border-radius",
        );
        $from = array(
            "border-top-left-radius",
            "border-top-right-radius",
            "border-bottom-left-radius",
            "border-bottom-right-radius",
            "border-radius",
        );

        $root = realpath($this->getKrynCore()->getKernel()->getRootDir() . '/../');
        $content = '';
        foreach ($files as $assetPath) {

            $cssFile = $this->getKrynCore()->resolveWebPath($assetPath); //admin/css/style.css
            $cssDir = dirname($cssFile) . '/'; //admin/css/...
            $cssDir = str_repeat('../', substr_count($includePath, '/')) . $cssDir;

            $content .= "\n\n/* file: $assetPath */\n\n";
            if (file_exists($file = $cssFile)) {
                $h = fopen($file, "r");
                if ($h) {
                    while (!feof($h) && $h) {
                        $buffer = fgets($h, 4096);

                        $buffer = preg_replace('/@import \'([^\/].*)\'/', '@import \'' . $cssDir . '$1\'', $buffer);
                        $buffer = preg_replace('/@import "([^\/].*)"/', '@import "' . $cssDir . '$1"', $buffer);
                        $buffer = preg_replace('/url\(\'([^\/][^\)]*)\'\)/', 'url(\'' . $cssDir . '$1\')', $buffer);
                        $buffer = preg_replace('/url\((?!data:image)([^\/\'].*)\)/', 'url(' . $cssDir . '$1)', $buffer);
                        $buffer = str_replace(array('  ', '    ', "\t", "\n", "\r"), '', $buffer);
                        $buffer = str_replace(': ', ':', $buffer);

                        $content .= $buffer;
                        $newLine = str_replace($from, $toWebkit, $buffer);
                        if ($newLine != $buffer) {
                            $content .= $newLine;
                        }
                        $newLine = str_replace($from, $toGecko, $buffer);
                        if ($newLine != $buffer) {
                            $content .= $newLine;
                        }
                    }
                    fclose($h);
                }
            } else {
                $content .= '/* File `' . $cssFile . '` not exist. */';
                $this->getKrynCore()->getLogger()->addError(
                    sprintf('Can not find css file `%s` [%s]', $file, $assetPath)
                );
            }
        }

        return $content;
    }

    public function shutdownHandler()
    {
        global $_start;

        if (defined('KRYN_TESTS') || defined('KRYN_INSTALLER')) {
            return;
        }

        chdir(PATH);

        $key = 'kryn' === getArgv(1) ? 'backend' : 'frontend';
        $this->latency[$key] = microtime(true) - $_start;

        $error = error_get_last();
        if ($error['type'] == 1) {
            $exception = new \InternalErrorException();
            $exception->setCode($error['type']);
            $exception->setMessage($error['message']);
            $exception->setFile($error['file']);
            $exception->setLine($error['line']);
            self::exceptionHandler($exception);
        } else {
            self::latencySnapshot();
        }

        if ($this->getKrynCore()->hasLogRequest()) {
            $this->getKrynCore()->getLogRequest()->save();
        }
    }

    public function latencySnapshot()
    {
        $lastLatency = $this->getKrynCore()->getFastCache('core/latency');

        if ($this->latency['cache']) {
            $this->latency['cache'] = array_sum($this->latency['cache']) / count($this->latency['cache']);
        }
        if ($this->latency['session']) {
            $this->latency['session'] = array_sum($this->latency['session']) / count($this->latency['session']);
        }

        $max = 20;
        foreach (array('frontend', 'backend', 'cache', 'session') as $key) {
            if (!$this->latency[$key]) {
                continue;
            }
            $lastLatency[$key] = (array)$lastLatency[$key] ? : array();
            array_unshift($lastLatency[$key], $this->latency[$key]);
            if ($max < count($lastLatency[$key])) {
                array_splice($lastLatency[$key], $max);
            }
        }

        $this->latency = array();
        $this->getKrynCore()->setFastCache('core/latency', $lastLatency);
    }

    public function getFileContent($file, $line, $offset = 10)
    {
        if (!file_exists($file)) {
            return;
        }
        $fh = fopen($file, 'r');

        if ($fh) {
            $line2 = 1;
            $code = '';
            while (($buffer = fgets($fh, 4096)) !== false) {

                if ($line2 >= ($line - $offset) && $line2 <= ($line + $offset)) {
                    $code .= $buffer;
                }

                if ($line2 == $line) {
                    $highlightLine = $line2;
                }

                $line2++;
            }

            if ("\n" !== substr($code, 0, -1)) {
                $code .= "\n";
            }

            return $code;
        }

        return '';
    }

    /**
     * Stores all locked keys, so that we can release all,
     * on process terminating.
     *
     * @var array
     */
    public $lockedKeys = array();

    /**
     * Releases all locked aquired by this process.
     *
     * Will be called during process shutdown. (register_shutdown_function)
     */
    public function releaseLocks()
    {
        foreach ($this->lockedKeys as $key => $value) {
            self::appRelease($key);
        }
    }

    /**
     * Locks the process until the lock of $id has been acquired for this process.
     * If no lock has been acquired for this id, it returns without waiting true.
     *
     * Waits max 15seconds.
     *
     * @param  string $id
     * @param  integer $timeout Milliseconds
     *
     * @return boolean
     */
    public function appLock($id, $timeout = 15)
    {

        //when we'll be caleed, then we register our releaseLocks
        //to make sure all locks are released.
        register_shutdown_function([$this, 'releaseLocks']);

        if (self::appTryLock($id, $timeout)) {
            return true;
        } else {
            for ($i = 0; $i < 1000; $i++) {
                usleep(15 * 1000); //15ms
                if (self::appTryLock($id, $timeout)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Tries to lock given id. If the id is already locked,
     * the function returns without waiting.
     *
     * @see appLock()
     *
     * @param  string $id
     * @param  int $timeout Default is 30sec
     *
     * @return bool
     */
    public function appTryLock($id, $timeout = 30)
    {
        //already aquired by this process?
        if ($this->lockedKeys[$id] === true) {
            return true;
        }

        $now = ceil(microtime(true) * 1000);
        $timeout2 = $now + $timeout;

        dbDelete('system_app_lock', 'timeout <= ' . $now);

        try {
            dbInsert('system_app_lock', array('id' => $id, 'timeout' => $timeout2));
            $this->lockedKeys[$id] = true;

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Releases a lock.
     * If you're not the owner of the lock with $id, then you'll kill it anyway.
     *
     * @param string $id
     */
    public function appRelease($id)
    {
        unset($this->lockedKeys[$id]);

        try {
            AppLockQuery::create()->filterById($id)->delete();
            dbDelete('system_app_lock', array('id' => $id));
        } catch (\Exception $e) {
        }
    }

    /**
     * Returns cached propel object.
     *
     * @param  int   $objectClassName If not defined, it returns the current page.
     * @param  mixed $objectPk        Propel PK for $objectClassName int, string or array
     *
     * @return mixed Propel object
     * @static
     */
    public function getPropelCacheObject($objectClassName, $objectPk)
    {
        if (is_array($objectPk)) {
            $npk = '';
            foreach ($objectPk as $k) {
                $npk .= urlencode($k) . '_';
            }
        } else {
            $pk = urlencode($objectPk);
        }

        $cacheKey = 'core/object-caching.' . strtolower(preg_replace('/[^\w]/', '.', $objectClassName)) . '/' . $pk;
        if ($serialized = $this->getKrynCore()->getDistributedCache($cacheKey)) {
            return unserialize($serialized);
        }

        return $this->setPropelCacheObject($objectClassName, $objectPk);
    }

    /**
     * Returns propel object and cache it.
     *
     * @param int   $objectClassName If not defined, it returns the current page.
     * @param mixed $objectPk        Propel PK for $objectClassName int, string or array
     * @param mixed $object          Pass the object, if you did already fetch it.
     *
     * @return mixed Propel object
     */
    public function setPropelCacheObject($object2ClassName, $object2Pk, $object = false)
    {
        $pk = $object2Pk;
        if ($pk === null && $object) {
            $pk = $object->getPrimaryKey();
        }

        if (is_array($pk)) {
            $npk = '';
            foreach ($pk as $k) {
                $npk .= urlencode($k) . '_';
            }
        } else {
            $pk = urlencode($pk);
        }

        $cacheKey = 'core/object-caching.' . strtolower(preg_replace('/[^\w]/', '.', $object2ClassName)) . '/' . $pk;

        $clazz = $object2ClassName . 'Query';
        $object2 = $object;
        if (!$object2) {
            $object2 = $clazz::create()->findPk($object2Pk);
        }

        if (!$object2) {
            return false;
        }

        $this->getKrynCore()->setDistributedCache($cacheKey, serialize($object2));

        return $object2;

    }

    /**
     * Removes a object from the cache.
     *
     * @param int   $objectClassName If not defined, it returns the current page.
     * @param mixed $objectPk        Propel PK for $objectClassName int, string or array
     */
    public function removePropelCacheObject($objectClassName, $objectPk = null)
    {
        $pk = $objectPk;
        if ($pk !== null) {
            if (is_array($pk)) {
                $npk = '';
                foreach ($pk as $k) {
                    $npk .= urlencode($k) . '_';
                }
            } else {
                $pk = urlencode($pk);
            }
        }
        $cacheKey = 'core/object-caching.' . strtolower(preg_replace('/[^\w]/', '.', $objectClassName));

        if ($objectPk) {
            $this->getKrynCore()->deleteDistributedCache($cacheKey . '/' . $pk);
        } else {
            $this->getKrynCore()->invalidateCache($cacheKey);
        }
    }

}
