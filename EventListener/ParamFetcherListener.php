<?php

namespace Kryn\CmsBundle\EventListener;

use FOS\RestBundle\EventListener\ParamFetcherListener as FosParamFetcherListener;
use Kryn\CmsBundle\Core;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

class ParamFetcherListener extends FosParamFetcherListener
{
    /**
     * @var Core
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct($container, true);
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        if ($this->container->get('kryn_cms')->isAdmin()) {
            parent::onKernelController($event);
        }
    }
}