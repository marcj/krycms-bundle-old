<?php

namespace Kryn\CmsBundle\Filesystem;

use Kryn\CmsBundle\File\FileInfoInterface;
use Kryn\CmsBundle\Filesystem\Adapter\AdapterInterface;

interface FilesystemInterface {

    public function write($path, $content);
    public function read($path);
    public function has($path);

    /**
     * @return AdapterInterface
     */
    public function getAdapter();

    /**
     * @param string $path
     * @return FileInfoInterface[]
     */
    public function getFiles($path);

    /**
     * @param string $path
     * @return FileInfoInterface
     */
    public function getFile($path);

}