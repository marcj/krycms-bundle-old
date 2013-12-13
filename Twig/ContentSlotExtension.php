<?php

namespace Kryn\CmsBundle\Twig;

use Kryn\CmsBundle\Core;
use Kryn\CmsBundle\Model\Node;
use Kryn\CmsBundle\Controller\PageController;

class ContentSlotExtension extends \Twig_Extension
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
        return 'contentSlot';
    }

    public function getFunctions()
    {
        return array(
            'contentSlot' => new \Twig_Function_Method($this, 'contentSlot', [
                    'is_safe' => ['html']
                ])
        );
    }

    public function contentSlot($id, $name = 'Content')
    {
        $params['id'] = $id;
        $params['name'] = $name;

        $render = $this->getKrynCore()->getContentRender();
        return $render->renderSlot($this->krynCore->getCurrentPage()->getId(), $id, $params);
    }

}