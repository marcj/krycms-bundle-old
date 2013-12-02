<?php

namespace Kryn\CmsBundle\Filesystem;

use Kryn\CmsBundle\Core;
use Kryn\CmsBundle\File\FileInfo;
use Kryn\CmsBundle\Model\File;
use Kryn\CmsBundle\File\FileInfoInterface;
use Kryn\CmsBundle\Filesystem\Adapter\AdapterInterface;
use Propel\Runtime\Propel;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class WebFilesystem extends Filesystem
{

    /**
     * @var array
     */
    protected $adapterInstances = [];

    /**
     * @param string $path
     * @return AdapterInterface
     */
    public function getAdapter($path = null)
    {
        $adapterClass = '\Kryn\CmsBundle\Filesystem\Adapter\Local';

        $params['root'] = realpath($this->getKrynCore()->getKernel()->getRootDir() . '/../web/');

        if ($path && '/' !== $path[0]) {
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

        if (isset($this->adapterInstances[$firstFolder])) {
            return $this->adapterInstances[$firstFolder];
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

        $adapter = $this->newAdapter($adapterClass, $firstFolder, $params);
        $adapter->setMountPath($firstFolder);

        if ($adapter instanceof ContainerAwareInterface) {
            $adapter->setContainer($this->getKrynCore()->getKernel()->getContainer());
        }

        $adapter->loadConfig();

        return $this->adapterInstances[$firstFolder] = $adapter;
    }

    /**
     * @param string $class
     * @param string $mountPath
     * @param array $params
     * @return \Kryn\CmsBundle\Filesystem\Adapter\AdapterInterface
     */
    public function newAdapter($class, $mountPath, $params)
    {
        return new $class($mountPath, $params);
    }

    /**
     * @param string $path
     * @return \Kryn\CmsBundle\Model\File[]
     */
    public function getFiles($path)
    {
        $items = parent::getFiles($path);
        $fs = $this->getAdapter($path);

        if ($fs->getMountPath()) {
            foreach ($items as &$file) {
                $file->setMountPoint($fs->getMountPath());
            }
        }

        if ('/' === $path) {
            foreach ($this->getKrynCore()->getSystemConfig()->getMountPoints() as $mountPoint) {
                $fileInfo = new FileInfo();
                $fileInfo->setPath('/' . $mountPoint->getPath());
                $fileInfo->setIcon($mountPoint->getIcon());
                $fileInfo->setType(FileInfo::DIR);
                $fileInfo->setMountPoint(true);
                array_unshift($items, $fileInfo);
            }
        }

        return $items;
    }


    /**
     * Translates the internal id to the real path.
     * Example: getPath(45) => '/myImageFolder/Picture1.png'
     *
     * @static
     *
     * @param  integer|string $id String for backward compatibility
     *
     * @return string
     */
    public function getPath($id)
    {
        if (!is_numeric($id)) {
            return $id;
        }

        //page bases caching here
        $sql = 'SELECT path
        FROM ' . $this->getKrynCore()->getSystemConfig()->getDatabase()->getPrefix() . 'system_file
        WHERE id = ' . ($id + 0);
        $con = Propel::getReadConnection('default');
        $stmt = $con->prepare($sql);

        $stmt->execute();

        return $stmt->fetchColumn();

    }

}