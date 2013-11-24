<?php

namespace Kryn\CmsBundle\Twig;

use Kryn\CmsBundle\Core;

class ResizeImageExtension extends \Twig_Extension
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
        return 'resizeImage';
    }

    public function getFunctions()
    {
        return array(
            'resizeImage' => new \Twig_Function_Method($this, 'resizeImage')
        );
    }

    public function resizeImage($imagePath, $dimension = '100x100')
    {
        if (!$imagePath) return '';

        return '';
    }

}