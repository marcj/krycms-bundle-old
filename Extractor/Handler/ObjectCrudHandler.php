<?php

namespace Kryn\CmsBundle\Extractor\Handler;

use Kryn\CmsBundle\Core;
use Nelmio\ApiDocBundle\Extractor\HandlerInterface;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Component\Routing\Route;
use FOS\RestBundle\Controller\Annotations\RequestParam;
use FOS\RestBundle\Controller\Annotations\QueryParam;

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
            $annotation->setSection(sprintf('Entrypoint: %s %s',
                $entryPoint->getLabel() ?: $entryPoint->getPath(),
                $entryPoint->isFrameworkWindow() ? '(Framework Window) ' : ''
            ));
        } else {
            $object = $this->krynCore->getObjects()->getDefinition($route->getDefault('_kryn_object'));
            $annotation->setSection(sprintf('Object %s -> %s (%s)',
                $object->getBundle()->getBundleName(),
                $object->getLabel() ?: $object->getId(),
                $route->getDefault('_kryn_object')
            ));
        }

        $method = explode('::', $route->getDefault('_controller'))[1];

        if ($route->hasRequirement('pk')) {

            $requirement = [];
            $description = [];

            foreach ($object->getFields() as $field) {
                if ($field->isPrimaryKey()) {
                    $requirement[] = $field->getRequiredRegex();
                    $description[] = $field->getId(). ($field->getDesc() ? '('.$field->getDesc().')' : '');
                }
            }

            $annotation->addRequirement('pk', [
                    'requirement'   => implode("/", $requirement),
                    'dataType'      => 'string',
                    'description'   => "UrlEncoded. Fields of the primaryKey: \n" . implode("\n", $description),
                ]);
        }

        //add all required fields to addAction
        if (in_array($method, ['addItemAction', 'patchItemAction', 'updateItemAction'])) {
            foreach ($object->getFields() as $field) {
                if ($field->isRequired() && !$field->getDefault()) {
                    $annotation->addRequirement($field->getId(), array(
                        'requirement'   => $field->getRequiredRegex(),
                        'dataType'      => $field->getPhpDataType(),
                        'description'   => $field->getLabel(),
                    ));
                } else {
                    $annotation->addParameter($field->getId(), array(
                        'format'        => $field->getRequiredRegex(),
                        'dataType'      => $field->getPhpDataType(),
                        'default'       => $field->getDefault(),
                        'description'   => $field->getLabel() . ($field->isAutoIncrement() ? ' (autoIncremented)':''),
                        'readonly'      => false,
                        'required'      => false,
                    ));
                }
            }
        }

//
//        foreach ($annotations as $annot) {
//            if ($annot instanceof RequestParam) {
//                $annotation->addParameter($annot->name, array(
//                        'required'    => $annot->strict && $annot->default === null,
//                        'dataType'    => $annot->requirements,
//                        'description' => $annot->description,
//                        'readonly'    => false
//                    ));
//            } elseif ($annot instanceof QueryParam) {
//                if ($annot->strict && $annot->nullable === false && $annot->default === null) {
//                    $annotation->addRequirement($annot->name, array(
//                            'requirement'   => $annot->requirements,
//                            'dataType'      => '',
//                            'description'   => $annot->description,
//                        ));
//                } elseif ($annot->default !== null) {
//                    $annotation->addFilter($annot->name, array(
//                            'requirement'   => $annot->requirements,
//                            'description'   => $annot->description,
//                            'default'   => $annot->default,
//                        ));
//                } else {
//                    $annotation->addFilter($annot->name, array(
//                            'requirement'   => $annot->requirements,
//                            'description'   => $annot->description,
//                        ));
//                }
//            }
//        }
    }
}