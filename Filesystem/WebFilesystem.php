<?php

namespace Kryn\CmsBundle\Filesystem;

use Flysystem\Filesystem;
use Kryn\CmsBundle\Core;

class WebFilesystem
{

    //todo
    /**
     * @var Core
     */
    protected $krynCore;

    /**
     * @var array
     */
    protected $layers = [];

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
     * @param string $path
     * @return Filesystem
     */
    public function getLayer($path)
    {
        $adapterClass = '\Kryn\CmsBundle\Filesystem\Adapter\Local';

        $params[0] = $this->getKrynCore()->getKernel()->getRootDir() . '/../web/';
        $params[1] = null;

        if ('/' !== $path[0]) {
            $path = '/' . $path;
        }

        if ($path != '/') {
            $sPos = strpos(substr($path, 1), '/');
            if (false === $sPos) {
                $firstFolder = substr($path, 1);
            } else {
                $firstFolder = substr($path, 1, $sPos);
            }
        } else {
            $firstFolder = '/';
        }

        if (isset($this->layers[$firstFolder])) {
            return $this->layers[$firstFolder];
        }

        if ('/' !== $firstFolder) {
            //todo
            $mounts = $this->getKrynCore()->getSystemConfig()->getMountPoints(true);

            //if firstFolder a mounted folder?
            if ($mounts && $mounts->hasMount($firstFolder)) {
//                $mountPoint = $mounts->getMount($firstFolder);
//                $adapterClass = $mountPoint->getClass();
//                $params = $mountPoint->getParams();
//                $mountName = $firstFolder;
            } else {
                $firstFolder = '/';
            }
        }

        $adapter = $this->newAdapter($adapterClass, $params);
        $adapter->setMountPath($firstFolder);

        $fs = new Filesystem($adapter);

        return $this->layers[$firstFolder] = $fs;
    }

    /**
     * @param string $class
     * @param array  $params
     * @return \Kryn\CmsBundle\Filesystem\Adapter\AdapterInterface
     */
    public function newAdapter($class, $params)
    {
        switch ($class) {
            case '\Kryn\CmsBundle\Filesystem\Adapter\Local':
                return new $class($params[0], $params[1]);
        }

        return new $class($params);
    }

    /**
     * Removes the name of the mount point from the proper layer.
     * Also removes '..' and replaces '//' => '/'
     *
     * This is needed because the file layer gets the relative path under his own root.
     * Forces a / at the beginning, removes the trailing / if exists.
     *
     * @param  string|array $path
     *
     * @return string
     */
    public function normalizePath($path)
    {
        if (is_array($path)) {
            $result = [];
            foreach ($path as $p) {
                $result[] = $this->normalizePath($p);
            }

            return $result;
        } else {
            if (strpos($path, '@') === 0) {
                $path = $this->getKrynCore()->resolvePath($path);
            }

            if ('/' !== $path[0]) {
                $path = '/' . $path;
            }

            if ('/' === substr($path, -1)) {
                $path = substr($path, 0, -1);
            }

            $fs = static::getLayer($path);
            $path = substr($path, strlen($fs->getAdapter()->getMountPath()));

            $path = str_replace('..', '', $path);
            $path = str_replace('//', '/', $path);

            return $path;
        }
    }

    /**
     * @param string $path
     * @param string $content
     * @return mixed
     */
    public function put($path, $content)
    {
        $fs = $this->getLayer($path);
        return $fs->put($this->normalizePath($path), $content);
    }
}