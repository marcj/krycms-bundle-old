<?php

namespace Kryn\CmsBundle\AssetHandler;

use Kryn\CmsBundle\Core;
use Kryn\CmsBundle\Exceptions\BundleNotFoundException;

abstract class AbstractHandler
{
    /**
     * @var Core
     */
    protected $krynCore;

    /**
     * @param Core $krynCore
     */
    function __construct(Core $krynCore)
    {
        $this->krynCore = $krynCore;
    }

    /**
     * @param \Kryn\CmsBundle\Core $krynCore
     */
    public function setKrynCore($krynCore)
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

    /**
     * Returns relative file path.
     *
     * @param string $path
     * @return string
     */
    protected function getAssetPath($path)
    {
        if ($path && '@' !== $path[0]) {
            return $path;
        }

        try {
            return $this->getKrynCore()->resolvePath($path, 'Resources/public', true);
        } catch (BundleNotFoundException $e) {
            return $path;
        }
    }

    /**
     *
     * @param string $path
     * @return string
     */
    protected function getPublicAssetPath($path)
    {
        if ($path && '@' !== $path[0]) {
            return $path;
        }

        $webDir = realpath($this->getKrynCore()->getKernel()->getRootDir().'/../web') . '/';
        try {
            $path = $this->getKrynCore()->resolveWebPath($path);
            if (file_exists($webDir . $path)) {
                return $path;
            }
        } catch (BundleNotFoundException $e) {
        }

        //do we need to add app_dev.php/ or something?
        $prefix = substr(
            $this->getKrynCore()->getRequest()->getBaseUrl(),
            strlen($this->getKrynCore()->getRequest()->getBasePath())
        );

        if (false !== $prefix) {
            $path = substr($prefix, 1) . '/' . $path;
        }

        return $path;
    }
}