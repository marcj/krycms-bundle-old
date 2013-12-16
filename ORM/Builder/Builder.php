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
     * @var Objects
     */
    protected $objects;

    /**
     * @param Objects $objects
     */
    function __construct(Objects $objects)
    {
        $this->objects = $objects;
    }

    /**
     * @param Objects $objects
     */
    public function setObjects($objects)
    {
        $this->objects = $objects;
    }

    /**
     * @return Objects
     */
    public function getObjects()
    {
        return $this->objects;
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
     * @param string $id
     *
     * @return BuildInterface
     */
    public function getBuilder($id)
    {
        return $this->builder[$id];
    }

    public function buildObject($objectKey)
    {
        $object = $this->getObjects()->getDefinition($objectKey);

        return $this->buildModel($object);
    }

    public function buildModel(Object $object)
    {
        $builder = $this->getBuilder($object->getDataModel());

        $builder->build($object);
    }

}