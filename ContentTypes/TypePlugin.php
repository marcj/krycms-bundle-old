<?php

namespace Kryn\CmsBundle\ContentTypes;

use Kryn\CmsBundle\Model\Content;

use Kryn\CmsBundle\Exceptions\PluginException;
use Propel\Runtime\Exception\LogicException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

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
     * @var \Core\Config\Plugin
     */
    private $pluginDef;

    public function exceptionHandler(GetResponseForExceptionEvent $event) {
        throw new PluginException(tf(
            'The plugin `%s` from bundle `%s` [%s] returned a wrong result.',
            $this->plugin['plugin'],
            $this->bundleName,
            $this->pluginDef->getClass() . '::' . $this->pluginDef->getMethod()
        ), null, $event->getException());
    }

    public function __construct(Content $content, array $parameters)
    {
        parent::__construct($content, $parameters);
        $this->plugin = json_decode($content->getContent(), 1);
        $this->bundleName = $this->plugin['bundle'] ? : $this->plugin['module']; //module for BC
    }

    public function render()
    {
        if ($response = Kryn::getResponse()->getPluginResponse($this->getContent())) {
            return $response->getContent();
        } elseif ($this->plugin) {
            $config = Kryn::getConfig($this->bundleName);

            if (!$config) {
                return tf(
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

                        $dispatcher = Kryn::getEventDispatcher();

                        $callable = array($this, 'exceptionHandler');
                        $dispatcher->addListener(
                            KernelEvents::EXCEPTION,
                            $callable,
                            100
                        );

                        ob_start();
                        $response = Kryn::getHttpKernel()->handle($request, HttpKernelInterface::SUB_REQUEST);
                        $ob = ob_get_clean();

                        $dispatcher->removeListener(
                            KernelEvents::EXCEPTION,
                            $callable
                        );

                        return $ob . $response->getContent();
                    } else {
                        return '';
                    }
                } else {
                    return tf('Class `%s` does not exist. You should create this class.', $clazz);
                }
            } else {
                return tf(
                    'Plugin `%s` in bundle `%s` does not exist. You probably have to install the bundle first.',
                    $this->plugin['plugin'],
                    $this->bundleName
                );
            }
        }
    }

}