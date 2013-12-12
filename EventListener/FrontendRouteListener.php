<?php

namespace Kryn\CmsBundle\EventListener;

use Kryn\CmsBundle\Core;
use Kryn\CmsBundle\PluginResponse;
use Kryn\CmsBundle\Router\FrontendRouter;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

class FrontendRouteListener extends RouterListener
{

    /**
     * @var Core
     */
    protected $krynCore;

    /**
     * @var RouteCollection
     */
    protected $routes;

    /**
     * @var bool
     */
    protected $loaded = false;

    function __construct(Core $krynCore)
    {
        $this->krynCore = $krynCore;
        $this->routes = new RouteCollection();

        parent::__construct(
            new UrlMatcher($this->routes, new RequestContext())
        );
    }

    /**
     * @param Core $krynCore
     */
    public function setKrynCore(Core $krynCore)
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
     * @param RouteCollection $routes
     */
    public function setRoutes($routes)
    {
        $this->routes = $routes;
    }

    /**
     * @return RouteCollection
     */
    public function getRoutes()
    {
        return $this->routes;
    }


    public function onKernelRequest(GetResponseEvent $event)
    {
        if (false === $this->loaded) {
            $router = new FrontendRouter($this->getKrynCore(), $event->getRequest());
            if ($response = $router->loadRoutes($this->routes)) {
                $event->setResponse($response);
                return;
            }
            $this->loaded = true;
        }

        try {
            parent::onKernelRequest($event);
        } catch(NotFoundHttpException $e) {
        }
    }
}