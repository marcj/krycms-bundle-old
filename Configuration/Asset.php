<?php

namespace Kryn\CmsBundle\Configuration;

use \Kryn\CmsBundle\Core as Kryn;

/**
 * Class Asset
 *
 * Paths are relative to `
 *
 * @bundlePath/Resources/public`.
 */
class Asset extends Model
{
    protected $attributes = ['compression'];
    protected $nodeValueVar = 'path';

    /**
     * @var string
     */
    protected $path;

    /**
     * If the asset can be compressed with other equal files (js/css compression)
     *
     * @var bool
     */
    protected $compression = true;

    /**
     * @param boolean $compression
     */
    public function setCompression($compression)
    {
        $this->compression = $this->bool($compression);
    }

    /**
     * @return boolean
     */
    public function getCompression()
    {
        return $this->compression;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Returns the full path relative to web root.
     *
     * @return string
     */
    public function getLocalPath()
    {
        return Kryn::resolvePath($this->path, 'Resources/public');
    }

    /**
     * Returns the public accessible path (`bundle/...`) through `Kryn::resolvePublicPath()`.
     *
     * @return string
     */
    public function getPublicPath()
    {
        return Kryn::resolvePublicPath($this->path);
    }

}