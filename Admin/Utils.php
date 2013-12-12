<?php

namespace Kryn\CmsBundle\Admin;

use Kryn\CmsBundle\Configuration\EntryPoint;
use Kryn\CmsBundle\Core;

class Utils
{
    /**
     * @var Core
     */
    protected $krynCore;

    function __construct($krynCore)
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

    public function clearCache()
    {
//        \Core\TempFile::remove('cache-object');
//        \Core\TempFile::remove('smarty-compile');
//
//        \Core\WebFile::remove('cache');
//        \Core\WebFile::createFolder('cache');

        foreach ($this->getKrynCore()->getKernel()->getBundles() as $bundleName => $bundle) {
            $this->clearBundleCache($bundleName);
        }

        return true;
    }

    public function clearBundleCache($bundleName)
    {
        $config = $this->getKrynCore()->getKernel()->getBundle($bundleName);

        if ($config) {
            $this->getKrynCore()->invalidateCache(strtolower($config->getName()));
        }
    }

    /**
     * Gets the item from the administration entry points defined in the config.json, by the given code.
     *
     * @param string  $code <bundleName>/news/foo/bar/edit
     *
     * @return EntryPoint
     */
    public function getEntryPoint($code)
    {
        if ('/' === $code) return null;

        $path = $code;
        if (substr($code, 0, 1) == '/') {
            $code = substr($code, 1);
        }

        $bundleName = $code;
        if (false !== (strpos($code, '/'))) {
            $bundleName = substr($code, 0, strpos($code, '/'));
            $path = substr($code, strpos($code, '/') + 1);
        }

        //$bundleName = ucfirst($bundleName) . 'Bundle';
        $config = $this->getKrynCore()->getConfig($bundleName);

        if (!$path && $config) {
            //root
            $entryPoint = new EntryPoint();
            $entryPoint->setType(0);
            $entryPoint->setPath($code);
            $entryPoint->setChildren(
                $config->getEntryPoints()
            );
            return $entryPoint;
        }

        $entryPoint = null;
        if ($config) {

            while (!($entryPoint = $config->getEntryPoint($path))) {
                if (false === strpos($path, '/')) {
                    break;
                }
                $path = substr($path, 0, strrpos($path, '/'));
            };
        }

        return $entryPoint;
    }
}
