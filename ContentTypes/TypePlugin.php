<?php

namespace Kryn\CmsBundle\ContentTypes;

use Kryn\CmsBundle\Model\Content;

use Kryn\CmsBundle\Exceptions\PluginException;
use Kryn\CmsBundle\PluginResponse;
use Propel\Runtime\Exception\LogicException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Kryn\CmsBundle\Core;
use Kryn\CmsBundle\Configuration\Plugin;

class TypePlugin extends AbstractType
{

    /**
     * @var array
     */
    private $plugin;

    /**
     * @var string
     */
    private $bundleName;

    /**
     * @var Plugin
     */
    private $pluginDef;

    /**
     * @var Core
     */
    protected $krynCore;

    function __construct($krynCore)
    {
        $this->krynCore = $krynCore;
    }

    /**
     * @param Core $krynCore
     */
    public function setKrynCore($krynCore)
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

    public function exceptionHandler(GetResponseForExceptionEvent $event) {
        throw new PluginException(sprintf(
            'The plugin `%s` from bundle `%s` [%s] returned a wrong result.',
            $this->plugin['plugin'],
            $this->bundleName,
            $this->pluginDef->getClass() . '::' . $this->pluginDef->getMethod()
        ), null, $event->getException());
    }

    public function setContent(Content $content)
    {
        parent::setContent($content);
        $this->plugin = json_decode($content->getContent(), 1);
        $this->bundleName = $this->plugin['bundle'] ? : $this->plugin['module']; //module for BC
    }

    public function fixResponse(GetResponseForControllerResultEvent $event)
    {
        $data = $event->getControllerResult();

        if ($data instanceof PluginResponse) {
            $response = $data;
        } else {
            $response = new PluginResponse('');
        }
        $response->setControllerRequest($event->getRequest());
        $event->setResponse($response);
    }

    public function render()
    {
        if ($response = $this->getKrynCore()->getPageResponse()->getPluginResponse($this->getContent())) {
            return $response->getContent();
        } elseif ($this->plugin) {
            $config = $this->getKrynCore()->getConfig($this->bundleName);

            if (!$config) {
                return sprintf(
                    'Bundle `%s` does not exist. You probably have to install this bundle.',
                    $this->bundleName
                );
            }

            if ($this->pluginDef = $config->getPlugin($this->plugin['plugin'])) {
                $clazz = $this->pluginDef->getClass();
                $method = $this->pluginDef->getMethod();

                if (class_exists($clazz)) {
                    if (method_exists($clazz, $method)) {
                        //create a sub request
                        $request = new Request();
                        $request->attributes->add(
                            array(
                                 '_controller' => $clazz . '::' . $method,
                                 'options' => $this->plugin['options'] ?: array()
                            )
                        );

                        $dispatcher = $this->getKrynCore()->getEventDispatcher();

                        $callable = array($this, 'exceptionHandler');
                        $fixResponse = array($this, 'fixResponse');

                        $dispatcher->addListener(
                            KernelEvents::EXCEPTION,
                            $callable,
                            100
                        );

                        $dispatcher->addListener(
                            KernelEvents::VIEW,
                            $fixResponse,
                            100
                        );

                        ob_start();
                        $response = $this->getKrynCore()->getKernel()->handle($request, HttpKernelInterface::SUB_REQUEST);
                        $ob = ob_get_clean();

                        $dispatcher->removeListener(
                            KernelEvents::EXCEPTION,
                            $callable
                        );
                        $dispatcher->removeListener(
                            KernelEvents::VIEW,
                            $fixResponse
                        );

                        return $ob . $response->getContent();
                    } else {
                        return '';
                    }
                } else {
                    return sprintf('Class `%s` does not exist. You should create this class.', $clazz);
                }
            } else {
                return sprintf(
                    'Plugin `%s` in bundle `%s` does not exist. You probably have to install the bundle first.',
                    $this->plugin['plugin'],
                    $this->bundleName
                );
            }
        }
    }

}