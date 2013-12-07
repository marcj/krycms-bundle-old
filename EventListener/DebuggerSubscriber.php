<?php

namespace Kryn\CmsBundle\EventListener;

use Kryn\CmsBundle\Core;
use Kryn\CmsBundle\Logger\KrynHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class DebuggerSubscriber implements EventSubscriberInterface
{

    /**
     * @var Core
     */
    protected $krynCore;

    protected $start = 0;

    protected $latency = [];

    /**
     * @var KrynHandler
     */
    protected $krynLogHandler;

    function __construct(Core $krynCore, KrynHandler $krynLogHandler)
    {
        $this->krynCore = $krynCore;
        $this->krynLogHandler = $krynLogHandler;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::TERMINATE => array('onKernelTerminate', -2048),
        ];
    }

    public function onKernelTerminate(PostResponseEvent $event)
    {
        if ($this->krynCore->has('profiler')) {
            /** @var $profiler \Symfony\Component\HttpKernel\Profiler\Profiler */
            $profiler = $this->krynCore->get('profiler');

            if ($profile = $profiler->loadProfileFromResponse($event->getResponse())) {
                $logRequest = $this->krynLogHandler->getLogRequest();
                $logRequest->setCounts(json_encode($this->krynLogHandler->getCounts()));
                $logRequest->setProfileToken($profile->getToken());
                $logRequest->save();
                return;
            }
        }

        //are there any warnings+?
        if ($this->krynLogHandler->getCounts()) {
            $logRequest = $this->krynLogHandler->getLogRequest();
            $logRequest->setCounts(json_encode($this->krynLogHandler->getCounts()));
            $logRequest->save();
        }
    }

}