<?php

namespace Kryn\CmsBundle\Twig;

use Kryn\CmsBundle\Core;
use Kryn\CmsBundle\Model\Node;

class NodeIsActiveExtension extends \Twig_Extension
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
        return 'nodeIsActive';
    }

    public function getFilters()
    {
        return array(
            'isActive' => new \Twig_SimpleFilter('isActive', [$this, 'isActive'])
        );
    }

    public function isActive(Node $node, $exact = false)
    {
        $current = $this->getKrynCore()->getCurrentPage();

        if ($node->getId() == $current->getId()) {
            return true;
        }

        if (!$exact) {
            $url = $this->getKrynCore()->getNodeUrl($current);
            $purl = $this->getKrynCore()->getNodeUrl($node);

            if ($url && $purl) {
                $pos = strpos($url, $purl);
                if ($url == '/' || $pos != 0 || $pos === false) {
                    return false;
                } else {
                    return true;
                }
            }
        }
    }

}