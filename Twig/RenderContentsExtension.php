<?php

namespace Kryn\CmsBundle\Twig;

use Kryn\CmsBundle\Core;
use Kryn\CmsBundle\Model\Node;

class RenderContentsExtension extends \Twig_Extension
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
        return 'renderContents';
    }

    public function getFilters()
    {
        return array(
            'renderContents' => new \Twig_SimpleFilter('renderContents', [$this, 'renderContents'])
        );
    }

    public function renderContents($contents, $view = '')
    {
        return $this->getKrynCore()->getContentRender()->renderView($contents, $view);
    }

}