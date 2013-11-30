<?php

namespace Kryn\CmsBundle\EventListener;

use Kryn\CmsBundle\Core;
use Kryn\CmsBundle\PluginResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Class PluginSubRequest
 *
 * This converts a PluginResponse to a PageResponse.
 */
class PluginResponseListener {

    /**
     * @var Core
     */
    protected $krynCore;

    /**
     * @var FrontendRouteListener
     */
    protected $frontendRouteListener;

    function __construct(Core $krynCore, FrontendRouteListener $frontendRouteListener)
    {
        $this->krynCore = $krynCore;
        $this->frontendRouteListener = $frontendRouteListener;
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

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $response = $event->getResponse();
        if (null !== $response && $response instanceof PluginResponse) {
            $response->setControllerRequest($event->getRequest());
            $response = $this->getKrynCore()->getPageResponse()->setPluginResponse($response);
            if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
                $response->renderContent();
            }
            $event->setResponse($response);
        }
    }

    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $data = $event->getControllerResult();
        $request = $event->getRequest();
        if (!$request->attributes->has('_content')) {
            return;
        }

        $content = $request->attributes->get('_content');

        if (null !== $data) {
            if ($data instanceof PluginResponse) {
                $response = $data;
            } else {
                $response = new PluginResponse($data);
            }
            $response->setControllerRequest($event->getRequest());
            $event->setResponse($response);
        } else {
            $foundRoute = false;

            $router = $this->getKrynCore()->getRouter();
            $routes = $this->frontendRouteListener->getRoutes();

            foreach ($routes as $idx => $route) {
                /** @var \Symfony\Component\Routing\Route $route */
                if ($content == $route->getDefault('_content')) {
                    $routes->remove($idx);
                    $foundRoute = true;
                    break;
                }
            }
            if ($foundRoute) {
                //we've remove the route and fire now again a sub request
                $request = clone $this->getKrynCore()->getRequest();
                $request->attributes = new ParameterBag();
                $response = $this->getKrynCore()->getKernel()->handle(
                    $request,
                    HttpKernelInterface::SUB_REQUEST
                );
                $event->setResponse($response);

                return;
            }
        }
    }
}