<?php

namespace Kryn\CmsBundle\Twig;

use Kryn\CmsBundle\Core;
use Propel\Runtime\Map\TableMap;

class BreadcrumbExtension extends \Twig_Extension
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
        return 'breadcrumb';
    }

    public function getFunctions()
    {
        return array(
            'breadcrumb' => new \Twig_Function_Method($this, 'breadcrumb', [
                    'is_safe' => ['html']
                ])
        );
    }

    public function breadcrumb($view = 'KrynCmsBundle:Default:breadcrumb.html.twig')
    {
        $breadcrumbs = [];
        foreach ($this->getKrynCore()->getCurrentPage()->getParents() as $parent) {
            if ($parent->getType() >= 2) continue;
            $a = $parent->toArray(TableMap::TYPE_STUDLYPHPNAME);
            $a['url'] = $this->getKrynCore()->getNodeUrl($parent);
            $breadcrumbs[] = $a;
        }

        $data = [
            'domain' => $this->getKrynCore()->getCurrentDomain(),
            'baseUrl' => $this->getKrynCore()->getCurrentDomain(),
            'breadcrumbs' => $breadcrumbs,
            'currentPage' => $this->getKrynCore()->getCurrentPage()
        ];
        return $this->getKrynCore()->getTemplating()->render($view, $data);
    }

}