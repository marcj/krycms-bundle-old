<?php

namespace Kryn\CmsBundle\Extractor\Handler;

use Kryn\CmsBundle\Core;
use Nelmio\ApiDocBundle\Extractor\HandlerInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Routing\Route;

class ObjectCrudHandler implements HandlerInterface
{
    /**
     * @var Core
     */
    protected $krynCore;

    function __construct(Core $krynCore)
    {
        $this->krynCore = $krynCore;
    }

    public function handle(ApiDoc $annotation, array $annotations, Route $route, \ReflectionMethod $method)
    {
        if (!($objectName = $route->getDefault('_kryn_object')) || !$object = $this->krynCore->getObjects()->getDefinition($objectName)) {
            return;
        }

        if ($entryPointPath = $route->getDefault('_kryn_entry_point')) {
//            $entryPoint = $this->krynCore->
            $adminUtils = new \Kryn\CmsBundle\Admin\Utils($this->krynCore);
            $entryPoint = $adminUtils->getEntryPoint($entryPointPath);
            $annotation->setSection(
                sprintf(
                    'Entrypoint: %s %s',
                    $entryPoint->getLabel() ? : $entryPoint->getPath(),
                    $entryPoint->isFrameworkWindow() ? '(Framework Window) ' : ''
                )
            );
        } else {
            $objectKey = $route->getDefault('_kryn_object_section') ? : $route->getDefault('_kryn_object');
            $objectSection = $this->krynCore->getObjects()->getDefinition($objectKey);
            $annotation->setSection(
                sprintf(
                    'Object %s -> %s (%s)',
                    $objectSection->getBundle()->getBundleName(),
                    $objectSection->getLabel() ? : $objectSection->getId(),
                    $objectKey
                )
            );
        }

        $filters = $annotation->getFilters();
        if (@$filters['fields']) {

            $fields = [];
            foreach ($object->getFields() as $field) {
                if ('object' === $field->getId()) {
                    $foreignObject = $this->krynCore->getObjects()->getDefinition($field->getObject());
                    foreach ($foreignObject->getFields() as $fField) {
                        $filters[] = $field->getId().'.'.$fField->getId();
                    }
                } else {
                    $fields[] = $field->getId();
                }
            }

            $annotation->addFilter('fields', [
                'requirement' => '.*',
                'description' => "Comma separated list of fields. Possible fields to select: \n" . implode(', ', $fields)
            ]);
        }

        $annotation->setDescription(
            str_replace('%object%', $object->getBundle()->getBundleName() . ':' . lcfirst($object->getId()), $annotation->getDescription())
        );

        $isRelationRoute = $route->getDefault('_kryn_object_relation');
        $requirePk = $route->getDefault('_kryn_object_requirePk');

        $method = explode('::', $route->getDefault('_controller'))[1];

//        maybe in version 1.1
//        if ($isRelationRoute) {
//            $objectKey = $route->getDefault('_kryn_object_section') ? : $route->getDefault('_kryn_object');
//            $objectParent = $this->krynCore->getObjects()->getDefinition($objectKey);
//
//            foreach ($objectParent->getFields() as $field) {
//                if ($field->isPrimaryKey()) {
//                    $annotation->addRequirement(
//                        $field->getId(),
//                        [
//                            'requirement' => $field->getRequiredRegex(),
//                            'dataType' => $field->getPhpDataType(),
//                            'description' => '(' . $objectParent->getId() . ') ' . $field->getDesc()
//                        ]
//                    );
//                }
//            }
//        }

        if ($requirePk) {
            foreach ($object->getFields() as $field) {
                if ($field->isPrimaryKey()) {

                    $annotation->addRequirement(
                        ($isRelationRoute ? lcfirst($object->getId()) . '_' : '') . $field->getId(),
                        [
                            'requirement' => $field->getRequiredRegex(),
                            'dataType' => $field->getPhpDataType(),
                            'description' => ($isRelationRoute ? '(' . $object->getId() . ') ' : '') . $field->getDesc()
                        ]
                    );
                }
            }
        }

        //add all fields to some actions
        if (in_array($method, ['addItemAction', 'patchItemAction', 'updateItemAction'])) {
            foreach ($object->getFields() as $field) {
                if ($field->isRequired() && !$field->getDefault()) {
                    $annotation->addRequirement(
                        $field->getId(),
                        array(
                            'requirement' => $field->getRequiredRegex(),
                            'dataType' => $field->getPhpDataType(),
                            'description' => ($isRelationRoute ? '(' . $object->getId() . ') ' : '') . $field->getLabel() . ' ' . $field->getDesc(),
                        )
                    );
                } else {
                    $annotation->addParameter(
                        $field->getId(),
                        array(
                            'format' => $field->getRequiredRegex(),
                            'dataType' => $field->getPhpDataType(),
                            'default' => $field->getDefault(),
                            'description' => $field->getLabel() . ($field->isAutoIncrement() ? ' (autoIncremented)' : ''),
                            'readonly' => false,
                            'required' => false,
                        )
                    );
                }
            }
        }

    }
}