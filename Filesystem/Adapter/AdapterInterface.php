<?php

namespace Kryn\CmsBundle\Filesystem\Adapter;

interface AdapterInterface {

    public function setMountPath($path);
    public function getMountPath();

    public function write($path, $content);
    public function read($path);
}
