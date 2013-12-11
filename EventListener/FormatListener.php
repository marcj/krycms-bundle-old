<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 09.12.13
 * Time: 00:06
 */

namespace Kryn\CmsBundle\EventListener;

use FOS\RestBundle\EventListener\FormatListener as FOSFormatListener;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Util\FormatNegotiatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class FormatListener extends FOSFormatListener
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $formatNegotiator = new \FOS\RestBundle\Util\FormatNegotiator();

        $adminPrefix = $this->container->getParameter('kryn_admin_prefix');

        $formatNegotiator->add(new RequestMatcher('^' . $adminPrefix), [
            'priorities' => ['json'],
            'prefer_extension' => false
        ]);

        //parent::__construct($formatNegotiator);
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($this->container->get('kryn_cms')->isAdmin()) {
            //parent::onKernelRequest($event);
        }
    }

} 