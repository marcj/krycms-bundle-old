<?php

namespace Kryn\CmsBundle;

trait ContainerHelperTrait
{
    /**
     * @return Core
     */
    public function getKrynCore()
    {
        return $this->container->get('kryn_cms');
    }

    /**
     * @return ORM\Builder\Builder
     */
    public function getModelBuilder()
    {
        return $this->container->get('kryn_cms.model.builder');
    }

    /**
     * @return AssetHandler\Container
     */
    public function getAssetCompilerContainer()
    {
        return $this->container->get('kryn_cms.asset_handler.container');
    }

    /**
     * Returns a Filesystem interface for the root folder (where your composer.json is placed)
     *
     * @return \Kryn\CmsBundle\Filesystem\FilesystemInterface
     */
    public function getFileSystem()
    {
        return $this->container->get('kryn_cms.filesystem.local');
    }

    /**
     * Returns a Filesystem interface for the current cache directory.
     *
     * @return \Kryn\CmsBundle\Filesystem\FilesystemInterface
     */
    public function getCacheFileSystem()
    {
        return $this->container->get('kryn_cms.filesystem.cache');
    }

    /**
     * Returns a Filesystem interface with mount-capability for the /web directory.
     *
     * @return \Kryn\CmsBundle\Filesystem\WebFilesystem
     */
    public function getWebFileSystem()
    {
        return $this->container->get('kryn_cms.filesystem.web');
    }

    /**
     * @return Objects
     */
    public function getObjects()
    {
        return $this->container->get('kryn_cms.objects');
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
     * @return \Symfony\Component\HttpFoundation\RequestStack
     */
    public function getRequestStack()
    {
        return $this->container->get('request_stack');
    }

    /**
     * @return PageResponse
     */
    public function getPageResponse()
    {
        return $this->container->get('kryn_cms.page.response');
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
        return $this->container->get('kryn_cms.cache.fast');
    }

    /**
     * @return Navigation
     */
    public function getNavigation()
    {
        return $this->container->get('kryn_cms.navigation');
    }

    /**
     * @return StopwatchHelper
     */
    public function getStopwatch()
    {
        return $this->container->get('kryn_cms.stopwatch');
    }

    /**
     * @return ACL
     */
    public function getACL()
    {
        return $this->container->get('kryn_cms.acl');
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
        return $this->container->get('kryn_cms.translator');
    }

    /**
     * @return ContentRender
     */
    public function getContentRender()
    {
        return $this->container->get('kryn_cms.content.render');
    }

    /**
     * @return ContentTypes\ContentTypes
     */
    public function getContentTypes()
    {
        return $this->container->get('kryn_cms.content.types');
    }

    /**
     * @return Admin\FieldTypes\FieldTypes
     */
    public function getFieldTypes()
    {
        return $this->container->get('kryn_cms.field.types');
    }

}