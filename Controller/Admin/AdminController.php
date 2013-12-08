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

use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Request\ParamFetcher;
use Kryn\CmsBundle\Admin\ObjectCrud;
use Kryn\CmsBundle\Configuration\EntryPoint;
use Kryn\CmsBundle\Core;
use Kryn\CmsBundle\Exceptions\AccessDeniedException;
use Kryn\CmsBundle\Exceptions\ClassNotFoundException;
use Kryn\CmsBundle\Exceptions\ObjectNotFoundException;
use Kryn\CmsBundle\Model\Content;
use Kryn\CmsBundle\Model\NodeQuery;
use Propel\Runtime\Map\TableMap;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

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
            return $response = new Response(json_encode(
                [
                    'status' => 403,
                    'error' => 'AccessDeniedException',
                    'message' => 'Access denied. No access or login first.'
                ],
                JSON_PRETTY_PRINT
            ), 403);
        }

        #$access = Permission::check('KrynCmsBundle:EntryPoint', $url);
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
     * @Route("/123{url}", requirements={"url" = ".+"})
     */
    public function mainAction($url = '/')
    {
        @header('Expires:');

        $exceptionHandler = array($this, 'exceptionHandler');
        $debugMode = false;
        if ($this->getKrynCore()->getKernel()->isDebug()) {
            $debugMode = true;
        }

        if ('JSON' === $this->getRequest()->headers->get('x-request')) {
            $exceptionHandler = null;
        }

        if ('/' !== substr($url, 0, 1)) {
            $url = '/' . $url;
        }

        $response = $this->getKrynCore()->getPageResponse();
        $request = $this->getKrynCore()->getRequest();

//        if ('/' !== substr($url, -1)) {
//            $url .= '/'; //substr($url, 0, -1);
//        }

        if ($adminUrl = $this->getKrynCore()->getSystemConfig()->getAdminUrl()) {
            if (0 === strpos($url, $adminUrl)) {
                $url = substr($url, strlen($adminUrl) - 1) ? : '/';
            }
        }

        $getArgv = function ($id) use ($url) {
            $exploded = explode('/', $url);

            return isset($exploded[$id]) ? $exploded[$id] : null;
        };

        //checkAccess
        if ($checkAccessResponse = $this->checkAccess($url)) {
            return $checkAccessResponse;
        }
        $entryPoint = $this->getUtils()->getEntryPoint($url);

        if ($entryPoint) {
            //is window entry point?
            $objectWindowTypes = array('list', 'edit', 'add', 'combine');

            if (in_array($entryPoint->getType(), $objectWindowTypes)) {
                $epc = new ObjectCrudController('/' . $getArgv(1) . '/' . $entryPoint->getFullPath());
                $epc->setKrynCore($this->getKrynCore());
                $epc->setRequest($this->getKrynCore()->getRequest());
                $epc->setExceptionHandler($exceptionHandler);
                $epc->setDebugMode($debugMode);
                $epc->setEntryPoint($entryPoint);
                $symfonyClient = new SymfonyClient($epc);
                $symfonyClient->setResponse($response);
                $symfonyClient->setRequest($request);
                $epc->setClient($symfonyClient);
                $epc->getClient()->setUrl($url);

                return $epc->run();
            }
        }

        $_GET = $this->getRequest()->query->all();
        $_POST = $this->getRequest()->request->all();

        if ($this->getKrynCore()->isActiveBundle($getArgv(2)) && $getArgv(2) != 'admin') {

            $bundle = $this->getKrynCore()->getBundle($getArgv(2));
            $namespace = $bundle->getNamespace();

            $clazz = $namespace . '\\Controller\\AdminController';

            if (get_parent_class($clazz) == 'RestService\Server') {
                $obj = new $clazz($this->getKrynCore()->getAdminPrefix() . '/' . $getArgv(2));
                $obj->setExceptionHandler($exceptionHandler);
                $symfonyClient = new SymfonyClient($obj);
                $symfonyClient->setResponse($response);
                $symfonyClient->setRequest($request);
                $obj->setClient($symfonyClient);
                $obj->getClient()->setUrl(substr($this->getKrynCore()->getRequest()->getPathInfo(), 1));
                $obj->setDebugMode($debugMode);
            } else {
                $obj = new $clazz();
            }

            $response = $obj->run();
            if ($response instanceof Response) {
                return $response;
            } else {
                die($response);
            }

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
                $symfonyClient = new SymfonyClient($epc);
                $symfonyClient->setResponse($response);
                $symfonyClient->setRequest($request);
                $epc->setClient($symfonyClient);
                $epc->setObj($object);
                $epc->setKrynCore($this->getKrynCore());
                $epc->setRequest($this->getKrynCore()->getRequest());
                $epc->getClient()->setUrl($url);
                $epc->setExceptionHandler($exceptionHandler);
                $epc->setDebugMode($debugMode);

                return $epc->run($entryPoint);
            }
        }
    }

    /**
     * @ApiDoc(
     *  description="Returns a content template/view with placeholder for ka.Editor."
     * )
     *
     * @Rest\QueryParam(name="template", requirements=".+", strict=true,
     *      description="The template/view to be used for this content")
     *
     * @Rest\QueryParam(name="type", requirements=".+", strict=true, description="The content type")
     *
     * @Rest\View()
     *
     * @Rest\Get("/content/template")
     *
     * @param ParamFetcher $paramFetcher
     *
     * @return array
     */
    public function getContentTemplate(ParamFetcher $paramFetcher)
    {
        $template = $paramFetcher->get('template');
        $type = $paramFetcher->get('type');

        $contentObject = new Content();
        $contentObject->setType($type);
        $contentObject->setTemplate($template);
        $contentObject->setContent('');

        $data = [
            'html' => '<div class="ka-content-container"></div>',
            'content' => $contentObject,
            'type' => $type
        ];

        return $this->renderView($template, $data);
    }

    /**
     * @ApiDoc(
     *  description="Returns a renderer content element as preview for ka.Editor"
     * )
     *
     * @Rest\QueryParam(name="template", requirements=".+", strict=true,
     *      description="The template/view to be used for this content")
     *
     * @Rest\QueryParam(name="type", requirements=".+", strict=true, description="The content type")
     *
     * @Rest\QueryParam(name="nodeId", requirements="[0-9]+", strict=true,
     *      description="The node id in which context this content should be rendered")
     * @Rest\QueryParam(name="domainId", requirements="[0-9]+", strict=true,
     *      description="The domain id in which context this content should be rendered")
     *
     * @Rest\RequestParam(name="content", requirements=".?", strict=true, description="The actual content")
     *
     * @Rest\View()
     *
     * @Rest\Get("/content/preview")
     *
     * @param ParamFetcher $paramFetcher
     *
     * @return array
     */
    public function getContentPreview(ParamFetcher $paramFetcher)
    {
        $template = $paramFetcher->get('template');
        $type = $paramFetcher->get('type');
        $content = $paramFetcher->get('content');
        $nodeId = $paramFetcher->get('nodeId');
        $domainId = $paramFetcher->get('domainId');

        $contentObject = new Content();
        $contentObject->setType($type);
        $contentObject->setTemplate($template);
        $contentObject->setContent($content);

        $domain = $this->getKrynCore()->getUtils()->getDomain($domainId);
        $this->getKrynCore()->setCurrentDomain($domain);

        $page = $this->getKrynCore()->getUtils()->getPage($nodeId);
        $this->getKrynCore()->setCurrentPage($page);

        $render = $this->getKrynCore()->getContentRender();

        return $render->renderContent($contentObject);
    }

    /**
     * @ApiDoc(
     *  description="Logs in a user to the current session"
     * )
     *
     * Result on success:
     * {
     *    token: "c7405b2be7da96b0db784f2dc8b2b974",
     *    userId: 1,
     *    username: "admin",
     *    access: true, #administration access
     *    firstName: "Admini",
     *    lastName: "strator"
     *}
     *
     * @Rest\QueryParam(name="username", requirements=".+", strict=true)
     * @Rest\QueryParam(name="password", requirements=".+", strict=true)
     *
     * @Rest\View()
     *
     * @Rest\Get("/login")
     *
     * @param ParamFetcher $paramFetcher
     *
     * @return array|bool Returns false on failure or a array if successful.
     */
    public function loginUserAction(ParamFetcher $paramFetcher)
    {
        $username = $paramFetcher->get('username');
        $password = $paramFetcher->get('password');
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
                    'access' => $this->getKrynCore()->getACL()->check('KrynCmsBundle:entryPoint', '/admin'),
                    'firstName' => $this->getKrynCore()->getAdminClient()->getUser()->getFirstName(),
                    'lastName' => $this->getKrynCore()->getAdminClient()->getUser()->getLastName()
                );
            }
        }

        return false;
    }

    /**
     * @ApiDoc(
     *  description="Logs out a user from the current session"
     * )
     *
     * @Rest\View()
     *
     * @Rest\Get("/logout")
     *
     * @return bool returns false if the user is not logged in or true when successfully logged out.
     */
    public function logoutUserAction()
    {
        if ($this->getKrynCore()->getAdminClient()->hasSession() && $this->getKrynCore()->getAdminClient()->getUser()) {
            $this->getKrynCore()->getAdminClient()->logout();

            return true;
        }

        return false;
    }

    /**
     * @ApiDoc(
     *  description="Returns the status of current user"
     * )
     *
     * @Rest\View()
     *
     * @Rest\Get("/logged-in")
     *
     * @return bool
     */
    public function loggedIn()
    {
        return $this->getKrynCore()->getAdminClient()->getUserId() > 0;
    }

    /**
     * @ApiDoc(
     *  description="Returns a stream value collection"
     * )
     *
     * @Rest\QueryParam(name="streams", array=true, requirements=".+", strict=true, description="List of stream ids")
     *
     * @Rest\View()
     *
     * @Rest\Get("/stream")
     *
     * @param ParamFetcher $paramFetcher
     *
     * @throws \InvalidArgumentException
     * @return array
     */
    public function getStreamAction(ParamFetcher $paramFetcher)
    {
        $streams = $paramFetcher->get('streams');
        if (!is_array($streams)) {
            throw new \InvalidArgumentException('__streams has to be an array.');
        }
        $__streams = array_map('strtolower', $streams);

        $response = array();
        foreach ($this->getKrynCore()->getConfigs() as $bundleConfig) {
            if ($streams = $bundleConfig->getStreams()) {
                foreach ($streams as $stream) {
                    $id = strtolower($bundleConfig->getBundleName()) . '/' . $stream->getPath();
                    if (false !== in_array($id, $__streams)) {
                        $stream->run($response);
                    }
                }
            }
        }

        return $response;
    }
}
