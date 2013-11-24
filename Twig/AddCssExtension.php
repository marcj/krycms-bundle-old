<?php

namespace Kryn\CmsBundle\Twig;

use Kryn\CmsBundle\Core;
use Kryn\CmsBundle\Model\Node;

class AddCssExtension extends \Twig_Extension
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
        return 'addCss';
    }

    public function getFunctions()
    {
        return array(
            'addCss' => new \Twig_Function_Method($this, 'addCss')
        );
    }

    public function addCss($cssFile)
    {
        return $this->getKrynCore()->getPageResponse()->addCssFile($cssFile);
    }

}