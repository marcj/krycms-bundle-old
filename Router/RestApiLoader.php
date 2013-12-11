<?php

namespace Kryn\CmsBundle\Router;

use Kryn\CmsBundle\Configuration\Bundle;
use Kryn\CmsBundle\Configuration\EntryPoint;
use Kryn\CmsBundle\Configuration\Object;
use Kryn\CmsBundle\Core;
use Kryn\CmsBundle\Exceptions\ObjectNotFoundException;
use Metadata\Driver\FileLocatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Routing\AnnotatedRouteControllerLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Loader\AnnotationFileLoader;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

class RestApiLoader extends Loader
{
    /**
     * @var Core
     */
    protected $krynCore;

    /**
     * @var RouteCollection
     */
    protected $routes;

    /**
     * @var AnnotationFileLoader
     */
    protected $annotationLoader;

    function __construct($krynCore)
    {
        $this->krynCore = $krynCore;
    }

    public function load($resource, $type = null)
    {
        $this->routes = new RouteCollection();

        $this->importObjectRoutes();
        $this->importWindowRoutes();

        return $this->routes;
    }

    public function setupObjectRouteRequirements(Route $route, Object $object)
    {
//        foreach ($object->getFields() as $field) {
//            if ($field->isRequired()) {
//                $route->setRequirement($field->getField(), $field->getRequiredRegex());
//            }
//        }
    }

    public function importWindowRoutes()
    {
        foreach ($this->krynCore->getBundles() as $bundleName => $bundle) {
            if ($this->krynCore->isKrynBundle($bundleName)) {
                if (!$config = $this->krynCore->getConfig($bundleName)) {
                    continue;
                }

                if ($entryPoints = $config->getEntryPoints()) {
                    foreach ($entryPoints as $entryPoint) {
                        if ($entryPoint->isFrameworkWindow()) {
                            $this->setupWindowRoute($entryPoint);
                        }
                    }
                }
            }
        }
    }

    public function setupWindowRoute(EntryPoint $entryPoint)
    {
        $class = $entryPoint->getClass();

        /** @var $importedRoutes \Symfony\Component\Routing\RouteCollection */
        $importedRoutes = $this->import(
            $class,
            'annotation'
        );

        $classReflection = new \ReflectionClass($class);

        $objectKey = $classReflection->getDefaultProperties()['object'];
        $object = $this->krynCore->getObjects()->getDefinition($objectKey);

        if (!$object) {
            throw new ObjectNotFoundException(sprintf(
                'Object `%s` in entryPoint `%s` of class `%s` not found.',
                $objectKey,
                $entryPoint->getFullPath(),
                $class
            ));
        }

        $pattern = $object->getBundle()->getName() . '/' . $entryPoint->getFullPath();

        $this->addObjectRoutes($importedRoutes, $pattern, $object);
    }

    public function addObjectRoutes(RouteCollection $routes, $pattern, Object $object)
    {
        $objectName = $object->getBundle()->getName() . '/' . lcfirst($object->getId());
        $routeName = 'kryn_cms_entrypoint_' . str_replace('/', '_', $pattern) . strtolower($object->getBundle()->getName() . '_' . $object->getId());

        /** @var $route \Symfony\Component\Routing\Route */
        foreach ($routes as $name => $route) {

            $route->setPath('%kryn_admin_prefix%/' . $pattern . $route->getPath());
            $route->setDefault('_kryn_object', $objectName);
            $route->setDefault('_kryn_entry_point', $pattern);

            if (false !== strpos($route->getPath(), '{pk}')) {
                $requirement = [];

                foreach ($object->getFields() as $field) {
                    if ($field->isPrimaryKey()) {
                        $requirement[] = $field->getRequiredRegex();
                    }
                }

                $route->setRequirement('pk', implode('/', $requirement));
            }

            $this->setupObjectRouteRequirements($route, $object);

            $this->routes->add($routeName . str_replace('kryn_cms_automatic', '_', $name), $route);
        }
    }

    public function importObjectRoutes()
    {
        $resource = '@KrynCmsBundle/Controller/AutomaticObjectCrudController.php';
        $resourceNested = '@KrynCmsBundle/Controller/AutomaticNestedObjectCrudController.php';

        foreach ($this->krynCore->getBundles() as $bundleName => $bundle) {
            if ($this->krynCore->isKrynBundle($bundleName)) {
                if (!$config = $this->krynCore->getConfig($bundleName)) {
                    continue;
                }

                if ($objects = $config->getObjects()) {
                    foreach ($objects as $object) {

                        $objectName = $this->krynCore->getShortBundleName($bundleName) . '/' . lcfirst($object->getId());
                        $pattern = '%kryn_admin_prefix%/admin/object/' . $objectName;
                        $routeName = 'kryn_cms_object_' . strtolower($bundleName . '_' . $object->getId());

                        /** @var $importedRoutes \Symfony\Component\Routing\RouteCollection */
                        $importedRoutes = $this->import(
                            $object->isNested() ? $resourceNested : $resource,
                            'annotation'
                        );

                        /** @var $route \Symfony\Component\Routing\Route */
                        foreach ($importedRoutes as $name => $route) {

                            $method = explode('::', $route->getDefault('_controller'))[1];

                            $route->setPath($pattern . $route->getPath());
                            $route->setDefault('_kryn_object', $objectName);

                            if (false !== strpos($route->getPath(), '{pk}')) {
                                $requirement = [];

                                foreach ($object->getFields() as $field) {
                                    if ($field->isPrimaryKey()) {
                                        $requirement[] = $field->getRequiredRegex();
                                    }
                                }

                                $route->setRequirement('pk', implode('/', $requirement));
                            }

                            $this->setupObjectRouteRequirements($route, $object);

                            $this->routes->add($routeName . str_replace('kryn_cms_automatic', '_', $name), $route);
                        }
                    }
                }
            }
        }

    }

    public function supports($resource, $type = null)
    {
        return $type === 'kryn_rest';
    }
} 