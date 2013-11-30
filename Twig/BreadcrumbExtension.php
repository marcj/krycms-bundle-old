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
        $page = $this->getKrynCore()->getCurrentPage();

        $cacheKey = 'core/breadcrumbs/' . $page->getId();
        if ($cache = $this->getKrynCore()->getDistributedCache($cacheKey)) {
            if (is_string($cache)) {
                return $cache;
            }
        }

        foreach ($page->getParents() as $parent) {
            if ($parent->getType() >= 2) {
                continue;
            }
            $breadcrumbs[] = $parent;
        }

        $data = [
            'domain' => $this->getKrynCore()->getCurrentDomain(),
            'baseUrl' => $this->getKrynCore()->getPageResponse()->getBaseHref(),
            'breadcrumbs' => $breadcrumbs,
            'currentPage' => $this->getKrynCore()->getCurrentPage()
        ];

        $html = $this->getKrynCore()->getTemplating()->render($view, $data);
        $this->getKrynCore()->setDistributedCache($cacheKey, $html);
        return $html;
    }

}