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
        return $this->getKrynCore()->resolveWebPath($path);
    }

    /**
     *
     * @param string $path
     * @return string
     */
    protected function getPublicAssetPath($path)
    {
        return $this->getKrynCore()->resolvePublicWebPath($path);
    }
}