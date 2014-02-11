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

use FOS\RestBundle\Request\ParamFetcher;
use Kryn\CmsBundle\Core;
use Kryn\CmsBundle\Model\Content;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class AdminController extends Controller
{
    protected $utils;

    /**
     * @return Core
     */
    protected function getKrynCore()
    {
        return $this->get('kryn_cms');
    }

    /**
     * @return \Kryn\CmsBundle\Admin\Utils
     */
    protected function getUtils()
    {
        if (null === $this->utils) {
            $this->utils = new \Kryn\CmsBundle\Admin\Utils($this->getKrynCore());
        }

        return $this->utils;
    }

    /**
     * @ApiDoc(
     *  section="Administration",
     *  description="Returns a content template/view with placeholder for ka.Editor."
     * )
     *
     * @Rest\QueryParam(name="template", requirements=".+", strict=true, description="The template/view to be used for this content")
     * @Rest\QueryParam(name="type", requirements=".+", strict=true, description="The content type")
     *
     * @Rest\Get("/admin/content/template")
     *
     * @param ParamFetcher $paramFetcher
     *
     * @return array
     */
    public function getContentTemplateAction(ParamFetcher $paramFetcher)
    {
        $template = $paramFetcher->get('template');
        $type = $paramFetcher->get('type');

        //todo, check if $template is defined as content template

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
     *  section="Administration",
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
     * @Rest\RequestParam(name="content", requirements=".*", strict=true, description="The actual content")
     *
     * @Rest\Post("/admin/content/preview")
     *
     * @param ParamFetcher $paramFetcher
     *
     * @return array
     */
    public function getContentPreviewAction(ParamFetcher $paramFetcher)
    {
        $template = $paramFetcher->get('template');
        $type = $paramFetcher->get('type');
        $content = $paramFetcher->get('content');
        $nodeId = $paramFetcher->get('nodeId');
        $domainId = $paramFetcher->get('domainId');

        //todo, check if $template is defined as content template

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
     *  section="Administration",
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
     * @Rest\RequestParam(name="username", requirements=".+", strict=true)
     * @Rest\RequestParam(name="password", requirements=".+", strict=true)
     *
     * @Rest\Post("/admin/login")
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
     *  section="Administration",
     *  description="Logs out a user from the current session"
     * )
     *
     * @Rest\Post("/admin/logout")
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
     *  section="Administration",
     *  description="Returns the status of current user"
     * )
     *
     * @Rest\Get("/admin/logged-in")
     *
     * @return bool
     */
    public function loggedInAction()
    {
        return $this->getKrynCore()->getAdminClient()->getUserId() > 0;
    }

    /**
     * @ApiDoc(
     *  section="Administration",
     *  description="Returns a stream value collection"
     * )
     *
     * @Rest\QueryParam(name="streams", array=true, requirements=".+", strict=true, description="List of stream ids")
     *
     * @Rest\Get("/admin/stream")
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
