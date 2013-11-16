<?php

namespace Kryn\CmsBundle\Filesystem\Adapter;

class Local extends \Flysystem\Adapter\Local implements AdapterInterface {
    use AdapterTrait;

    public function __construct($root, $mode = null)
    {
        static::$permissions['public'] = $mode ?: 0644;
        parent::__construct($root);
    }

    public function getRoot()
    {
        return $this->root;
    }

}