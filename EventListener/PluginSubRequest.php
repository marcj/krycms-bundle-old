<?php

namespace Kryn\CmsBundle\EventListener;

use Kryn\CmsBundle\Core;
use Kryn\CmsBundle\PluginResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class PluginSubRequest {

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

    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        return;
        $data = $event->getControllerResult();

        if (null !== $data) {
            if ($data instanceof PluginResponse) {
                $response = $data;
            } else {
                $response = new PluginResponse($data);
            }
            $response->setControllerRequest($event->getRequest());
            $event->setResponse($response);
        } else {
            $content = $event->getRequest()->attributes->get('_content');
            if ($content) {
                $foundRoute = false;

                $router = $this->getKrynCore()->getRouter();
                $routes = $router->getRouteCollection();

                foreach ($routes as $idx => $route) {
                    /** @var \Symfony\Component\Routing\Route $route */
                    if ($content == $route->getDefault('_content')) {
                        $routes->remove($idx);
                        $foundRoute = true;
                        break;
                    }
                }
                if ($foundRoute) {
                    die('omg?');
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
}