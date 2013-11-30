<?php

namespace Kryn\CmsBundle\Twig;

use Kryn\CmsBundle\Core;
use Kryn\CmsBundle\Model\Base\Node;
use Kryn\CmsBundle\Twig\TokenParser\Unsearchable;

class UnsearchableExtension extends \Twig_Extension
{
    public function getName()
    {
        return 'unsearchable';
    }

    public function getTokenParsers()
    {
        return array(new Unsearchable());
    }
}