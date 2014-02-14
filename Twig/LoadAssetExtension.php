<?php

namespace Kryn\CmsBundle\Twig;

use Kryn\CmsBundle\Core;
use Kryn\CmsBundle\Model\Node;

class LoadAssetExtension extends \Twig_Extension
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
        return 'loadAsset';
    }

    public function getFunctions()
    {
        return array(
            'loadAsset' => new \Twig_Function_Method($this, 'loadAsset'),
            'loadAssetAtBottom' => new \Twig_Function_Method($this, 'loadAssetAtBottom')
        );
    }

    public function loadAsset($asset)
    {
        $this->getKrynCore()->getPageResponse()->loadAssetFile($asset);
    }

    public function loadAssetAtBottom($asset)
    {
        $this->getKrynCore()->getPageResponse()->loadAssetFileAtBottom($asset);
    }

}