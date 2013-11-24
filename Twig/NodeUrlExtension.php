<?php

namespace Kryn\CmsBundle\Twig;

use Kryn\CmsBundle\Core;
use Kryn\CmsBundle\Model\Node;

class NodeUrlExtension extends \Twig_Extension
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
        return 'nodeUrl';
    }

    public function getFilters()
    {
        return array(
            'url' => new \Twig_SimpleFilter('url', [$this, 'getUrl'])
        );
    }

    public function getUrl($nodeOrId)
    {
        return $this->getKrynCore()->getNodeUrl($nodeOrId);
    }

}