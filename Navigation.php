<?php

namespace Kryn\CmsBundle;

use Kryn\CmsBundle\Model\NodeQuery;

class Navigation
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
     * @param Core $krynCore
     */
    public function setKrynCore($krynCore)
    {
        $this->krynCore = $krynCore;
    }

    /**
     * @return Core
     */
    public function getKrynCore()
    {
        return $this->krynCore;
    }

    public function arrayLevel($array, $level)
    {
        return $array[$level - 2];
    }

    public function get($options)
    {
        $kryn = $this->getKrynCore();

        $view = $options['template'] ? : $options['view'];
        $options['noCache'] = isset($options['noCache']) ? $options['noCache'] : false;
        $options['id'] = isset($options['id']) ? $options['id'] : false;
        $options['level'] = isset($options['level']) ? $options['level'] : false;

//        $withFolders = (isset($options['folders']) && $options['folders'] == 1) ? true : false;

        $cacheKey = 'core/navigation/' . $kryn->getCurrentPage()->getDomainId() . '.' . $kryn->getCurrentPage()->getId() . '_' . md5(
            json_encode($options)
        );

        $navigation = false;
        $fromCache = false;

        $viewPath = $kryn->resolvePath($view, 'Resources/views/');
        if ('@' === $view[0]) {
            $view = substr($view, 1);
        }

        if (!file_exists($viewPath)) {
            throw new \Exception(sprintf('View `%s` not found.', $view));
        } else {
            $mtime = filemtime($viewPath);
        }

        $themeProperties = $kryn->getCurrentDomain()->getThemeProperties();

        if (!$options['noCache'] && $themeProperties && $themeProperties->getByPath('core/cacheNavigations') !== 0) {

            $cache = $kryn->getDistributedCache($cacheKey);
            if ($cache && is_array($cache) && $cache['html'] !== null && $cache['mtime'] == $mtime) {
                //return $cache['html'];
            }
        }

        $cache = $kryn->getDistributedCache($cacheKey);

        if ($cache && isset($cache['object']) && $cache['mtime'] == $mtime) {
            $navigation = unserialize($cache['object']);
            $fromCache = true;
        }

        if (!$navigation && $options['id'] != 'breadcrumb' && ($options['id'] || $options['level'])) {

            if ($options['id'] + 0 > 0) {
                $navigation = $this->getKrynCore()->getUtils()->getPage($options['id'] + 0);

                if (!$navigation) {
                    return null;
                }
            }

            if ($options['level'] > 1) {

                $currentPage = $this->getKrynCore()->getCurrentPage();
                $parents = $currentPage->getParents();
                $parents[] = $currentPage;

                $currentLevel = count($parents) + 1;
                $page = $this->arrayLevel($parents, $options['level']);

                if ($page && $page->getId() > 0) {
                    $navigation = $this->getKrynCore()->getUtils()->getPage($page->getId());
                } elseif ($options['level'] == $currentLevel + 1) {
                    $navigation = $kryn->getCurrentPage();
                }
            }

            if ($options['level'] == 1) {
                $navigation = NodeQuery::create()->findRoot($kryn->getCurrentDomain()->getId());
            }
        }

        $data['navigation'] = $navigation ?: false;
        if ($navigation !== false) {

            $html = $kryn->getTemplating()->render($view, $data);

            if (!$options['noCache'] && $themeProperties && $themeProperties->getByPath('core/cacheNavigations') !== 0) {
                $kryn->setDistributedCache($cacheKey, array('mtime' => $mtime, 'html' => $html));
            } elseif (!$fromCache) {
                $kryn->setDistributedCache($cacheKey, array('mtime' => $mtime, 'object' => serialize($navigation)));
            }

            return $html;
        }

        //no navigation found, probably the template just uses the breadcrumb
        return $kryn->getTemplating()->render($view, $data);
    }

}
