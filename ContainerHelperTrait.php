<?php

namespace Kryn\CmsBundle;

trait ContainerHelperTrait
{
    /**
     * @return Core
     */
    public function getKrynCore()
    {
        return $this->container->get('kryn.cms');
    }

    /**
     * @return \Kryn\CmsBundle\Filesystem\FilesystemInterface
     */
    public function getFileSystem()
    {
        return $this->container->get('kryn.filesystem.local');
    }

    /**
     * @return \Kryn\CmsBundle\Filesystem\FilesystemInterface
     */
    public function getCacheFileSystem()
    {
        return $this->container->get('kryn.filesystem.cache');
    }

    /**
     * @return \Kryn\CmsBundle\Filesystem\FilesystemInterface
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

    /**
     * @return \Symfony\Component\HttpFoundation\Request
     */
    public function getRequest()
    {
        return $this->container->get('request_stack')->getCurrentRequest();
    }

    /**
     * @return PageResponse
     */
    public function getPageResponse()
    {
        return $this->container->get('kryn.page.response');
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
     * @return StopwatchHelper
     */
    public function getStopwatch()
    {
        return $this->container->get('kryn.stopwatch');
    }

    /**
     * @return ACL
     */
    public function getACL()
    {
        return $this->container->get('kryn.acl');
    }

    /**
     * @return \Symfony\Bundle\FrameworkBundle\Routing\Router
     */
    public function getRouter()
    {
        return $this->container->get('router');
    }

    /**
     * @return Translation\Translator
     */
    public function getTranslator()
    {
        return $this->container->get('kryn.translator');
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

}