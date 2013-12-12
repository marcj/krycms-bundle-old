<?php

namespace Kryn\CmsBundle\EventListener;

use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Util\FormatNegotiatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SecurityFirewallListener
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $krynCms = $this->container->get('kryn_cms');
        if ($krynCms->isAdmin()) {
            $whiteList = [
                '/',
                '/admin/backend/css',
                '/admin/backend/script',
                '/admin/ui/languages',
                '/admin/ui/language',
                '/admin/ui/language-plural',
                '/admin/login',
                '/admin/logged-in'
            ];

            $adminPrefix = $krynCms->getAdminPrefix();

            $url = substr($event->getRequest()->getPathInfo(), strlen($adminPrefix));
            if (in_array($url, $whiteList)) {
                return;
            }

            if (!$krynCms->getAdminClient()->getUser() || !$krynCms->getAcl()->check('KrynCmsBundle:EntryPoint', $url)) {
                $response = new Response(json_encode(
                    [
                        'status' => 403,
                        'error' => 'AccessDeniedException',
                        'message' => 'Access denied.'
                    ],
                    JSON_PRETTY_PRINT
                ), 403);

                $response->headers->set('Content-Type', 'application/json');
                $event->setResponse($response);
            }
        }
    }

} 