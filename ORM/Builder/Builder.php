<?php

namespace Kryn\CmsBundle\ORM\Builder;

use Kryn\CmsBundle\Configuration\Object;
use Kryn\CmsBundle\Core;
use Kryn\CmsBundle\Objects;

class Builder
{

    /**
     * @var BuildInterface[]
     */
    protected $builder;

    /**
     * @var Core
     */
    protected $krynCore;

    /**
     * @var \Kryn\CmsBundle\Configuration\Object[]
     */
    protected $objects;

    /**
     * @param Core $krynCore
     */
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

    /**
     * @param string $id
     * @param BuildInterface $builder
     */
    public function addBuilder($id, $builder)
    {
        $this->builder[$id] = $builder;
    }

    /**
     * Loads all objects and
     */
    public function bootBuildTime()
    {
        $this->objects = [];

        foreach ($this->getKrynCore()->getConfigs() as $bundleConfig) {
            if ($objects = $bundleConfig->getObjects()) {
                foreach ($objects as $object) {
                    $this->objects[strtolower($object->getKey())] = $object;
                    $object->bootBuildTime($this->getKrynCore()->getConfigs());
                }
            }
        }

//        foreach ($this->objects as $object) {
//            $object->bootBuildTime($this->getKrynCore()->getConfigs());
//        }
    }

    /**
     * @param string $objectKey
     *
     * @return bool
     */
    public function hasObject($objectKey)
    {
        return isset($this->objects[strtolower($objectKey)]);
    }

    /**
     * @param string $objectKey
     *
     * @return \Kryn\CmsBundle\Configuration\Object
     */
    public function getObject($objectKey)
    {
        return @$this->objects[strtolower($objectKey)];
    }

    /**
     * @param \Kryn\CmsBundle\Configuration\Object $objectDefinition
     */
    public function addObject(Object $objectDefinition)
    {
        $this->objects[strtolower($objectDefinition->getId())] = $objectDefinition;
    }

    /**
     * @param string $id
     *
     * @return BuildInterface
     */
    public function getBuilder($id)
    {
        return @$this->builder[$id];
    }

    public function build()
    {
        $this->bootBuildTime();

        foreach ($this->builder as $builder) {
            $builder->build($this->objects);
        }
    }
}