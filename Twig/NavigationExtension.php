<?php

namespace Kryn\CmsBundle\Twig;

use Kryn\CmsBundle\Core;
use Kryn\CmsBundle\Model\Base\Node;

class NavigationExtension extends \Twig_Extension
{
    /**
     * @var Core
     */
    protected $krynCore;

    function __construct(Core $krynCore)
    {
        $this->krynCore = $krynCore;
    }

    /**
     * @return \Kryn\CmsBundle\Core
     */
    public function getKrynCore()
    {
        return $this->krynCore;
    }

    public function getName()
    {
        return 'navigation';
    }

    public function getFunctions()
    {
        return array(
            'navigationLevel' => new \Twig_Function_Method($this, 'navigationLevel', [
                    'is_safe' => ['html']
                ]),
            'navigationNode' => new \Twig_Function_Method($this, 'navigationNode', [
                    'is_safe' => ['html']
                ])
        );
    }

    public function navigationNode($nodeOrId, $view = 'KrynCmsBundle:Default:navigation.html.twig')
    {
        $navigation = $this->getKrynCore()->getNavigation();

        $id = $nodeOrId;
        if ($id instanceof Node) {
            $id = $nodeOrId->getId();
        }

        $options = [
            'id' => $id,
            'template' => $view
        ];

        return $navigation->get($options);
    }

    public function navigationLevel($level, $view = 'KrynCmsBundle:Default:navigation.html.twig')
    {
        $navigation = $this->getKrynCore()->getNavigation();

        $options = [
            'level' => $level,
            'template' => $view
        ];

        return $navigation->get($options);
    }

}