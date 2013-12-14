<?php

namespace Kryn\CmsBundle;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Kryn\CmsBundle\DependencyInjection\ContentTypesCompilerPass;
use Kryn\CmsBundle\DependencyInjection\FieldTypesCompilerPass;
use Kryn\CmsBundle\Propel\PropelHelper;
use Symfony\Component\ClassLoader\UniversalClassLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class KrynCmsBundle extends Bundle
{
    /**
     * @var UniversalClassLoader
     */
    protected $additionalLoader;

    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new ContentTypesCompilerPass());
        $container->addCompilerPass(new FieldTypesCompilerPass());
    }

    public function boot()
    {
        parent::boot();
        $this->additionalLoader = new UniversalClassLoader();
        $this->additionalLoader->registerNamespaceFallback($this->container->get('kernel')->getCacheDir().'/propel-classes/');
        $this->additionalLoader->register();

        /** @var $krynCore Core */
        $krynCore = $this->container->get('kryn_cms');

        /*
         * Propel orm initialisation.
         */
        $propelHelper = new PropelHelper($krynCore);

        if (!$propelHelper->loadConfig()) {
            $propelHelper->init();
        }

        $krynCore->prepareWebSymlinks();
        $krynCore->loadBundleConfigs();

        if ($krynCore->getSystemConfig()->getLogs(true)->isActive()) {
            /** @var $logger \Symfony\Bridge\Monolog\Logger */
            $logger = $this->container->get('logger');
            $logger->pushHandler($this->container->get('kryn_cms.logger.handler'));
        }
    }

    /**
     * Shutdowns the Bundle.
     */
    public function shutdown()
    {
        unset($this->additionalLoader);
    }
}
