<?php

namespace Kryn\CmsBundle\EventListener;

use FOS\RestBundle\View\ViewHandler;
use Kryn\CmsBundle\Exceptions\RestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Bundle\FrameworkBundle\Templating\TemplateReference;
use FOS\RestBundle\View\View;

class RestExceptionListener
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }


    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if ($this->container->get('kryn_cms')->isAdmin()) {
            $request = $event->getRequest();
            if ($request->attributes->has('_controller')) {

                $exception = $event->getException();
                $statusCode = $exception instanceof RestException ? $exception->getCode() : 500;

                $view = [
                    'status' => $statusCode,
                    'error' => get_class($event->getException()),
                    'message' => $event->getException()->getMessage()
                ];

                if ($exception instanceof RestException) {
                    $view['data'] = $exception->getData();
                }

                if ($this->container->get('kernel')->isDebug()) {
                    $view['file'] = $event->getException()->getFile();
                    $view['line'] = $event->getException()->getLine();
                    $view['trace'] = $event->getException()->getTraceAsString();
                }


                $response = new Response(json_encode($view, JSON_PRETTY_PRINT));
                $response->headers->set('Content-Type', 'application/json');
                $event->setResponse($response);
            }
        }
    }

} 