<?php

/*
 * This file is part of Kryn.cms.
 *
 * (c) Kryn.labs, MArc Schmidt <marc@kryn.org>
 *
 * To get the full copyright and license informations, please view the
 * LICENSE file, that was distributed with this source code.
 *
 */

namespace Kryn\CmsBundle\Controller\Admin;

use Kryn\CmsBundle\Admin\ObjectCrud;
use Kryn\CmsBundle\Configuration\EntryPoint;
use Kryn\CmsBundle\Core;
use Kryn\CmsBundle\Exceptions\AccessDeniedException;
use Kryn\CmsBundle\Exceptions\ObjectNotFoundException;
use Kryn\CmsBundle\Model\NodeQuery;
use Propel\Runtime\Map\TableMap;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerAware;

class AdminController extends Controller
{
    protected $utils;

    /**
     * Checks the access to the administration URLs and redirect to administration login if no access.
     *
     * @internal
     * @static
     */
    public function checkAccess($url)
    {
        $whitelist = [
            '/',
            '/admin/backend/style',
            '/admin/backend/script',
            '/admin/ui/languages',
            '/admin/ui/language',
            '/admin/ui/language-plural',
            '/admin/login',
            '/admin/logged-in'
        ];

        if (in_array($url, $whitelist)) {
            return;
        }

        if (!$this->getKrynCore()->getAdminClient()->getUser()) {
            throw new AccessDeniedException('Access denied.');
        }

        #$access = Permission::check('core:EntryPoint', $url);
        if (!true) {
            #throw new AccessDeniedException(tf('Access denied.'));
        }
    }

    public function exceptionHandler($exception)
    {
        if (get_class($exception) != 'AccessDeniedException') {
            throw $exception;
        }
    }

    /**
     * @return Core
     */
    public function getKrynCore()
    {
        return $this->get('kryn.cms');
    }

    public function getUtils()
    {
        if (null === $this->utils) {
            $this->utils = new \Kryn\CmsBundle\Admin\Utils($this->getKrynCore());
        }

        return $this->utils;
    }

    /**
     * @Route("/{url}", requirements={"url" = ".+"})
     */
    public function mainAction($url)
    {
        @header('Expires:');

        $exceptionHandler = null;
        $debugMode = false;
        if ($this->getKrynCore()->getSystemConfig()->getErrors()->getDisplayRest()) {
            $exceptionHandler = array($this, 'exceptionHandler');
            $debugMode = true;
        }

        if ('/' !== substr($url, 0, 1)) {
            $url = '/' . $url;
        }

        if ('/' !== substr($url, -1)) {
            $url .= '/'; //substr($url, 0, -1);
        }

        $url = substr($url, strlen($this->getKrynCore()->getSystemConfig()->getAdminUrl()) - 1);

        $getArgv = function ($id) use ($url) {
            $exploded = explode('/', $url);

            return isset($exploded[$id]) ? $exploded[$id] : null;
        };

        if ('/' === $url) {
            $loginController = new LoginController();
            $loginController->setContainer($this->container);

            return $loginController->showLogin();
        }

        //checkAccess
        $this->checkAccess($url);
        $entryPoint = $this->getUtils()->getEntryPoint($url);

        if ($entryPoint) {
            //is window entry point?
            $objectWindowTypes = array('list', 'edit', 'add', 'combine');

            if (in_array($entryPoint->getType(), $objectWindowTypes)) {
                $epc = new ObjectCrudController('/' . $getArgv(1) . '/' . $entryPoint->getFullPath());
                $epc->setKrynCore($this->getKrynCore());
                $epc->setRequest($this->getKrynCore()->getRequest());
                $epc->getClient()->setUrl($url);
                $epc->setExceptionHandler($exceptionHandler);
                $epc->setDebugMode($debugMode);
                $epc->setEntryPoint($entryPoint);
                die($epc->run());
            } elseif ($entryPoint->getType() == 'store') {

                $clazz = $entryPoint['class'];
                if (!$clazz) {
                    throw new \ClassNotFoundException(sprintf(
                        'The property `class` is not defined in entry point `%s`',
                        $entryPoint['_url']
                    ));
                }
                if (!class_exists($clazz)) {
                    throw new \ClassNotFoundException(sprintf(
                        'The class `%s` does not exist in entry point `%s`',
                        $clazz,
                        $entryPoint['_url']
                    ));
                }

                $obj = new $clazz($entryPoint['_url']);
                $obj->setEntryPoint($entryPoint);
                die($obj->run());

            }
        }

        if ($this->getKrynCore()->isActiveBundle($getArgv(2)) && $getArgv(2) != 'admin') {

            $bundle = $this->getKrynCore()->getBundle($getArgv(2));
            $namespace = $bundle->getNamespace();

            $clazz = $namespace . '\\Controller\\AdminController';

            if (get_parent_class($clazz) == 'RestService\Server') {
                $obj = new $clazz($this->getKrynCore()->getAdminPrefix() . '/' . $getArgv(2));
                $obj->getClient()->setUrl(substr($this->getKrynCore()->getRequest()->getPathInfo(), 1));
                $obj->setExceptionHandler($exceptionHandler);
                $obj->setDebugMode($debugMode);
            } else {
                $obj = new $clazz();
            }

            die($obj->run());

        } else {

            if ($getArgv(2) == 'object') {

                $entryPoint = new EntryPoint(null, $this->getKrynCore());
                $entryPoint->setFullPath('admin/object/' . $getArgv(3));
                $entryPoint->setType('combine');

                $objectKey = rawurldecode($getArgv(3));
                $definition = $this->getKrynCore()->getObjects()->getDefinition($objectKey);

                if (!$definition) {
                    throw new ObjectNotFoundException(sprintf('Object `%s` not found.', $objectKey));
                }

                $object = new ObjectCrud();
                $object->setObject($objectKey);
                $object->setKrynCore($this->getKrynCore());
                $object->setRequest($this->getKrynCore()->getRequest());
                $object->setAllowCustomSelectFields(true);

                $object->initialize();

                $epc = new ObjectCrudController('/' . $entryPoint->getFullPath());
                $epc->setObj($object);
                $epc->setKrynCore($this->getKrynCore());
                $epc->setRequest($this->getKrynCore()->getRequest());
                $epc->getClient()->setUrl($url);
                $epc->setExceptionHandler($exceptionHandler);
                $epc->setDebugMode($debugMode);

                die($epc->run($entryPoint));
            }

            $krynCore = $this->getKrynCore();

            \RestService\Server::create('/admin', $this)

                ->getClient()->setUrl($url)->getController()
                ->setControllerFactory(
                    function ($className, $server) use ($krynCore) {
                        $controller = new $className($server);
                        if ($controller instanceof ContainerAware) {
                            $controller->setContainer($krynCore->getKernel()->getContainer());
                        }

                        return $controller;
                    }
                )
                ->setExceptionHandler($exceptionHandler)
                ->setDebugMode($debugMode)

                ->addGetRoute('login', 'loginUser')
                ->addGetRoute('logged-in', 'loggedIn')
                ->addGetRoute('logout', 'logoutUser')

                ->addGetRoute('stream', 'getStream')

                ->addSubController('ui', 'Kryn\CmsBundle\Controller\Admin\UIAssets')
                    ->addGetRoute('languages', 'getPossibleLangs')
                    ->addGetRoute('language-plural', 'getLanguagePluralForm')
                    ->addGetRoute('language', 'getLanguage')
                ->done()

                //admin/backend
                ->addSubController('backend', 'Kryn\CmsBundle\Controller\Admin\Backend')
                    ->addGetRoute('script', 'loadJs')
                    ->addGetRoute('script-map', 'loadJsMap')
                    ->addGetRoute('style', 'loadCss')

                    ->addGetRoute('settings', 'getSettings')

                    ->addGetRoute('desktop', 'getDesktop')
                    ->addPostRoute('desktop', 'saveDesktop')

                    ->addGetRoute('widgets', 'getWidgets')
                    ->addPostRoute('widgets', 'saveWidgets')

                    ->addGetRoute('menus', 'getMenus')
                    ->addGetRoute('custom-js', 'getCustomJs')
                    ->addPostRoute('user-settings', 'saveUserSettings')

                    ->addDeleteRoute('cache', 'clearCache')

                    ->addGetRoute('search', 'getSearch')
                    //->addPutRoute('content', 'saveContents')

                ->done()

                ->addGetRoute('content/template', 'getContentTemplate')
                ->addPostRoute('content/preview', 'getContentPreview')

                //->addGetRoute('editor', 'getKEditor')

                ->addSubController('', 'Kryn\CmsBundle\Controller\Admin\Object\Controller')
                    ->addGetRoute('objects', 'getItemsByUrl')
                    ->addGetRoute('object', 'getItemPerUrl')
                    ->addGetRoute('object-version', 'getVersionsPerUrl')

                    /*
                    ->addGetRoute('field-object/([a-zA-Z-_]+)/([^/]+)', 'getFieldItem')
                    ->addGetRoute('field-object-count/([a-zA-Z-_]+)', 'getFieldItemsCount')
                    ->addGetRoute('field-object/([a-zA-Z-_]+)', 'getFieldItems')
                    */

                    ->addGetRoute('object-browser/([a-zA-Z-_\.\\\\:]+)', 'getBrowserItems')
                    ->addGetRoute('object-browser-count/([a-zA-Z-_\.\\\\:]+)', 'getBrowserItemsCount')
                ->done()

                //admin/system
                ->addSubController('system', 'Kryn\CmsBundle\Controller\Admin\System')

                    ->addGetRoute('', 'getSystemInformation')

                    ->addSubController('config', 'Kryn\CmsBundle\Controller\Admin\Config')
                        ->addGetRoute('', 'getConfig')
                        ->addGetRoute('labels', 'getLabels')
                        ->addPostRoute('', 'saveConfig')
                    ->done()

                    //admin/system/module/manager
                    ->addSubController('module/manager', 'Kryn\CmsBundle\Controller\Admin\BundleManager\Manager')
                        ->addGetRoute('install/pre', 'installPre')
                        ->addGetRoute('install/extract', 'installExtract')
                        ->addGetRoute('install/database', 'installDatabase')
                        ->addGetRoute('install/post', 'installPost')
                        ->addGetRoute('check-updates', 'check4updates')
                        ->addGetRoute('local', 'getLocal')
                        ->addGetRoute('installed', 'getInstalled')

                        ->addPostRoute('install', 'install')
                        ->addPostRoute('uninstall', 'uninstall')

                        ->addPutRoute('', 'createBundle')

                        ->addPostRoute('activate', 'activate')
                        ->addPostRoute('deactivate', 'deactivate')

                        ->addPostRoute('composer/install', 'installComposer')
                        ->addPostRoute('composer/uninstall', 'uninstallComposer')

                        //->addGetRoute('composer/packages', 'getComposerPackages')

                        ->addGetRoute('info', 'getInfo')
                    ->done()

                    //admin/system/orm
                    ->addSubController('orm', 'Kryn\CmsBundle\Controller\Admin\ORM')
                        ->addGetRoute('environment', 'buildEnvironment')
                        ->addGetRoute('models', 'writeModels')
                        ->addGetRoute('update', 'updateScheme')
                        ->addGetRoute('check', 'checkScheme')
                    ->done()

                    //admin/system/orm
                    ->addSubController('tools', 'Kryn\CmsBundle\Controller\Admin\Tools')
                        ->addGetRoute('request', 'getRequest')
                        ->addGetRoute('logs', 'getLogs')
                        ->addDeleteRoute('logs', 'clearLogs')
                    ->done()

                    //admin/system/module/editor
                    ->addSubController('module/editor', 'Kryn\CmsBundle\Controller\Admin\BundleManager\Editor')
                        ->addGetRoute('config', 'getConfig')

                        ->addGetRoute('basic', 'getBasic')
                        ->addPostRoute('basic', 'saveBasic')

                        ->addGetRoute('entry-points', 'getEntryPoints')
                        ->addPostRoute('entry-points', 'saveEntryPoints')

                        ->addGetRoute('windows', 'getWindows')
                        ->addGetRoute('window', 'getWindowDefinition')
                        ->addPostRoute('window', 'saveWindowDefinition')
                        ->addPutRoute('window', 'newWindow')

                        ->addGetRoute('objects', 'getObjects')
                        ->addPostRoute('objects', 'saveObjects')

                        ->addGetRoute('plugins', 'getPlugins')
                        ->addPostRoute('plugins', 'savePlugins')

                        ->addPostRoute('model/from-objects', 'setModelFromObjects')

                        ->addPostRoute('model', 'saveModel')
                        ->addGetRoute('model', 'getModel')

                        ->addPostRoute('themes', 'saveThemes')
                        ->addGetRoute('themes', 'getThemes')

                        ->addPostRoute('docu', 'saveDocu')
                        ->addGetRoute('docu', 'getDocu')

                        ->addSubController('language', 'Kryn\CmsBundle\Controller\Admin\Languages')
                            ->addGetRoute('overview', 'getOverviewExtract')
                            ->addPostRoute('', 'saveLanguage')
                            ->addGetRoute('', 'getLanguage')
                            ->addGetRoute('extract', 'getExtractedLanguage')

                        ->done()

                        ->addPostRoute('general', 'saveGeneral')
                        ->addPostRoute('entryPoints', 'saveEntryPoints')
                    ->done()

                ->done()

                ->addSubController('file', 'Kryn\CmsBundle\Controller\Admin\File')
                    ->addGetRoute('', 'getContent')
                    ->addGetRoute('image', 'showImage')
                    ->addgetRoute('content', 'viewFile')
                    ->addPostRoute('content', 'setContent')

                    ->addPostRoute('', 'createFile')
                    ->addDeleteRoute('', 'deleteFile')
                    ->addPostRoute('folder', 'createFolder')
                    ->addGetRoute('search', 'search')

                    ->addPostRoute('move', 'moveFile')
                    ->addGetRoute('single', 'getFile')
                    ->addGetRoute('preview', 'showPreview')
                    ->addPostRoute('upload', 'doUpload')
                    ->addPostRoute('paste', 'paste')
                    ->addPostRoute('upload/prepare', 'prepareUpload')
                ->done()
                ->run();
            exit;

        }
    }

    public function getContentTemplate($template, $type = 'text')
    {
        $data = [
            'html' => '<div class="ka-content-container"></div>',
            'type' => $type
        ];

        return $this->getKrynCore()->getInstance()->renderView($template, $data);
    }

    public function getContentPreview($template, $type = 'text', $content)
    {
        $contentObject = new Content();
        $contentObject->setType($type);
        $contentObject->setTemplate($template);
        $contentObject->setContent($content);

        return Render::renderContent($contentObject);
    }

//    public static function handleKEditor()
//    {
//        self::addMainResources(['noJs' => true]);
//        self::addSessionScripts();
//        $response = $this->getKrynCore()->getResponse();
//        $response->addJsFile('@CoreBundle/mootools-core.js');
//        $response->addJsFile('@CoreBundle/mootools-more.js');
//
//        //$response->addJs('ka = parent.ka;');
//
//        $response->setResourceCompression(false);
//        $response->setDomainHandling(false);
//
//        $nodeArray['id'] = $this->getKrynCore()->$page->getId();
//        $nodeArray['title'] = $this->getKrynCore()->$page->getTitle();
//        $nodeArray['domainId'] = $this->getKrynCore()->$page->getDomainId();
//
//        $options = [
//            'id' => $_GET['_kryn_editor_id'],
//            'node' => $nodeArray
//        ];
//
//        if (is_array($_GET['_kryn_editor_options'])) {
//            $options = array_merge($options, $_GET['_kryn_editor_options']);
//            $options['standalone'] = filter_var($options['standalone'], FILTER_VALIDATE_BOOLEAN);
//        }
//        $response->addJs(
//            'window.editor = new parent.ka.Editor(' . json_encode($options) . ', document.documentElement);',
//            'bottom'
//        );
//    }


    public function loginUser($username, $password)
    {
        $status = $this->getKrynCore()->getAdminClient()->login($username, $password);

        if ($this->getKrynCore()->getAdminClient()->getUser()) {
            $lastLogin = $this->getKrynCore()->getAdminClient()->getUser()->getLastLogin();
            if ($status) {
                $this->getKrynCore()->getAdminClient()->getUser()->setLastLogin(time());

                return array(
                    'token' => $this->getKrynCore()->getAdminClient()->getToken(),
                    'userId' => $this->getKrynCore()->getAdminClient()->getUserId(),
                    'username' => $this->getKrynCore()->getAdminClient()->getUser()->getUsername(),
                    'lastLogin' => $lastLogin,
                    'access' => $this->getKrynCore()->getACL()->check('core:entryPoint', '/admin'),
                    'firstName' => $this->getKrynCore()->getAdminClient()->getUser()->getFirstName(),
                    'lastName' => $this->getKrynCore()->getAdminClient()->getUser()->getLastName()
                );
            }
        }


        return false;
    }

    public function loggedIn()
    {
        return $this->getKrynCore()->getAdminClient()->getUserId() > 0;
    }

    public function logoutUser()
    {
        $this->getKrynCore()->getAdminClient()->logout();

        return true;
    }

    public function searchAdmin($query, $lang)
    {
        $res = array();
        $lang = preg_replace('[^a-zA-Z0-9_-]', '', $lang);

        //pages
        $nodes = NodeQuery::create()->filterByTitle('%' . $query . '%', \Criteria::LIKE)->find();

        if (count($nodes) > 0) {
            foreach ($nodes as $node) {
                $respages[] =
                    array(
                        $node->getTitle(),
                        'admin/pages',
                        array('id' => $node->getId(), 'lang' => $node->getDomain()->getLang())
                    );
            }
            $res[t('Pages')] = $respages;
        }

        //help
        $helps = array();
        foreach ($this->getKrynCore()->getConfigs() as $key => $mod) {
            $helpFile = PATH_MODULE . "$key/lang/help_$lang.json";
            if (!file_exists($helpFile)) {
                continue;
            }
            if (count($helps) > 10) {
                continue;
            }

            $json = json_decode(file_get_contents($helpFile), 1);
            if (is_array($json) && count($json) > 0) {
                foreach ($json as $help) {

                    if (count($helps) > 10) {
                        continue;
                    }
                    $found = false;

                    if (preg_match("/$query/i", $help['title'])) {
                        $found = true;
                    }

                    if (preg_match("/$query/i", $help['tags'])) {
                        $found = true;
                    }

                    if (preg_match("/$query/i", $help['help'])) {
                        $found = true;
                    }

                    if ($found) {
                        $helps[] = array($help['title'], 'admin/help', array('id' => $key . '/' . $help['id']));
                    }
                }
            }
        }
        if (count($helps) > 0) {
            $res[t('Help')] = $helps;
        }

        return $res;
    }

    public function getStream($__streams)
    {
        if (!is_array($__streams)) {
            throw new \InvalidArgumentException('__streams has to be an array.');
        }
        $__streams = array_map('strtolower', $__streams);

        $response = array();
        foreach ($this->getKrynCore()->getConfigs() as $bundleConfig) {
            if ($streams = $bundleConfig->getStreams()) {
                foreach ($streams as $stream) {
                    $id = strtolower($bundleConfig->getName()) . '/' . $stream->getPath();
                    if (false !== in_array($id, $__streams)) {
                        $stream->run($response);
                    }
                }
            }
        }

        return $response;
    }
}
