<?php

namespace Kryn\CmsBundle\EventListener;

use FOS\RestBundle\View\ViewHandler;
use Kryn\CmsBundle\Exceptions\RestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Bundle\FrameworkBundle\Templating\TemplateReference;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
            $exception = $event->getException();
            $statusCode = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;

            $view = [
                'status' => $statusCode,
                'error' => get_class($event->getException()),
                'message' => $event->getException()->getMessage()
            ];

            if ($exception instanceof RestException) {
                $view['data'] = $exception->getData();
            }

            if ($this->container->get('kernel')->isDebug()) {
                $trace = [];
                foreach ($event->getException()->getTrace() as $t) {
                    $args = [];
                    foreach ((array)@$t['args'] as $arg) {
                        $args[] = gettype($arg);
                    }

                    $trace[] = [
                        'function' => @$t['function'],
                        'class' => @$t['class'],
                        'file' => @$t['file'],
                        'line' => @$t['line'],
                        'type' => @$t['type'],
                        'args' => $args,
                    ];
                }

                $view['file'] = $event->getException()->getFile();
                $view['line'] = $event->getException()->getLine();
                $view['trace'] = $trace;
            }

            $response = new Response(json_encode($view, JSON_PRETTY_PRINT));
            $response->headers->set('Content-Type', 'application/json');
            $event->setResponse($response);
            //why does the kernel send a 500 statusCode ?
        }
    }

} 