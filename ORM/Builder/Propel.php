<?php

namespace Kryn\CmsBundle\ORM\Builder;

use Kryn\CmsBundle\Admin\FieldTypes\ColumnDefinitionInterface;
use Kryn\CmsBundle\Admin\FieldTypes\RelationDefinitionInterface;
use Kryn\CmsBundle\Configuration\Bundle;
use \Kryn\CmsBundle\Exceptions\ModelBuildException;
use Kryn\CmsBundle\Configuration\Field;
use Kryn\CmsBundle\Configuration\Object;
use Kryn\CmsBundle\Filesystem\Filesystem;
use Kryn\CmsBundle\Objects;
use Kryn\CmsBundle\ORM\ORMAbstract;
use Kryn\CmsBundle\Propel\PropelHelper;
use Kryn\CmsBundle\Tools;
use Symfony\Component\HttpKernel\Kernel;

class Propel implements BuildInterface
{

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Objects
     */
    protected $objects;

    function __construct(Filesystem $filesystem, Objects $objects, Kernel $kernel)
    {
        $this->filesystem = $filesystem;
        $this->objects = $objects;
        $this->kernel = $kernel;
    }

    /**
     * @param \Kryn\CmsBundle\Configuration\Object[] $objects
     */
    public function build(array $objects)
    {
        foreach ($objects as $object) {
            if ('propel' === $object->getDataModel()) {
                $this->writeSchemaXml($object);
            }
        }

        $krynCore = $this->kernel->getContainer()->get('kryn_cms');
        $propelHelper = new PropelHelper($krynCore);

        $propelHelper->init();
    }

    /**
     * {@inheritDocs}
     */
    public function needsBuild()
    {
        return !file_exists($this->kernel->getCacheDir() . '/propel-classes/');
    }

    public function writeSchemaXml(Object $object)
    {
        $bundlePath = $object->getKrynCore()->getBundleDir($object->getBundle()->getName());
        $modelsFile = $bundlePath . 'Resources/config/kryn.propel.schema.xml';
        $modelsFileOut = $bundlePath . 'Resources/config/kryn.propel.schema.built.xml';

        $xml = null;
        if ($this->filesystem->has($modelsFile) && $xmlString = $this->filesystem->read($modelsFile)) {
            $xml = @simplexml_load_string($xmlString);

            if ($xml === false) {
                $errors = libxml_get_errors();
                throw new ModelBuildException(sprintf(
                    'Parse error in %s: %s',
                    $modelsFile,
                    json_encode($errors, JSON_PRETTY_PRINT)
                ));
            }
        }

        $schema = $this->getSchema($object, $xml);

        return $this->filesystem->write($modelsFileOut, $schema);
    }

    public function getSchema(Object $object, $xml = null)
    {
        if (!$xml) {
            $xml = simplexml_load_string('<database></database>');
        }
        $bundle = $object->getBundle();

        $xml['namespace'] = ucfirst($bundle->getNamespace());

        //search if we've already the table defined.
        $tables = $xml->xpath('table[@name=\'' . $object['table'] . '\']');

        if (!$tables) {
            $objectTable = $xml->addChild('table');
        } else {
            $objectTable = current($tables);
        }

        if (!$object->getTable()) {
            throw new ModelBuildException(sprintf('The object `%s` has no table defined', $object->getId()));
        }

        $objectTable['name'] = $object->getTable();
        $objectTable['phpName'] = ucfirst($object->getId());

        if ($object->isCrossRef()) {
            $objectTable['isCrossRef'] = 'true';
        }

        $columnsDefined = array();

        //removed all non-custom foreign-keys
        $foreignKeys = $objectTable->xpath("foreign-key[not(@custom='true')]");
        foreach ($foreignKeys as $k => $fk) {
            unset($foreignKeys[$k][0]);
        }

        //removed all non-custom behaviors
        $items = $objectTable->xpath("behavior[not(@custom='true')]");
        foreach ($items as $k => $v) {
            unset($items[$k][0]);
        }

        if (!$object->getFields()) {
            throw new ModelBuildException(sprintf('The object `%s` has no fields defined', $object->getId()));
        }

        foreach ($object->getFields() as $field) {

            if ($columns = $field->getFieldType()->getColumns()) {
                foreach ($columns as $column) {
                    $name = Tools::camelcase2Underscore($column->getName());

                    //column exist?
                    $eColumns = $objectTable->xpath('column[@name =\'' . $name . '\']');

                    if ($eColumns) {
                        $newCol = current($eColumns);
                        if ($newCol['custom'] == true) {
                            continue;
                        }
                    } else {
                        $newCol = $objectTable->addChild('column');
                    }

                    $columnsDefined[] = $name;

                    $this->setupColumnAttributes($column, $newCol);

                    if ($field->isRequired()) {
                        $newCol['required'] = 'true';
                    }

                    if ($field->isPrimaryKey()) {
                        $newCol['primary'] = 'true';
                    }

                    if ($field->isAutoIncrement()) {
                        $newCol['autoIncrement'] = 'true';
                    }
                }
            }
        }

        if ($relations = $object->getRelations()) {
            foreach ($relations as $relation) {
                $this->addRelation($relation, $objectTable);
            }
        }

        //check for deleted columns
        $columns = $objectTable->xpath("column[not(@custom='true')]");
        foreach ($columns as $k => $column) {
            if (!in_array($column['name'], $columnsDefined)) {
                unset($columns[$k][0]);
            }
        }

        if ($object['workspace']) {
            $behaviors = $objectTable->xpath('behavior[@name=\'Kryn\CmsBundle\WorkspaceBehavior\']');
            if ($behaviors) {
                $behavior = current($behaviors);
            } else {
                $behavior = $objectTable->addChild('behavior');
            }
            $behavior['name'] = 'Kryn\CmsBundle\WorkspaceBehavior';
        }

        $vendors = $objectTable->xpath('vendor[@type=\'mysql\']');
        if ($vendors) {
            foreach ($vendors as $k => $v) {
                unset($vendors[$k][0]);
            }
        }

        $vendor = $objectTable->addChild('vendor');
        $vendor['type'] = 'mysql';

        $params = $vendor->xpath('parameter[@name=\'Charset\']');
        if ($params) {
            $param = current($params);
        } else {
            $param = $vendor->addChild('parameter');
        }

        $param['name'] = 'Charset';
        $param['value'] = 'utf8';

        $dom = new \DOMDocument;
        $dom->preserveWhiteSpace = false;
        $dom->loadXML($xml->asXML());
        $dom->formatOutput = true;

        $xml = $dom->saveXML();
        $prefix = '<?xml version="1.0"?>';
        if (0 === strpos($xml, $prefix)) {
            $xml = substr($xml, strlen($prefix));
        }

        return trim($xml);
    }

    protected function addRelation(RelationDefinitionInterface $relation, &$xmlTable)
    {
        if (ORMAbstract::MANY_TO_ONE == $relation->getType()) {
            $this->addForeignKey($relation, $xmlTable);
        }
    }

    protected function addForeignKey(RelationDefinitionInterface $relation, &$xmlTable)
    {
        $relationName = $relation->getName();
        $foreignObject = $this->objects->getDefinition($relation->getForeignObjectKey());

        if (!$foreignObject) {
            throw new ModelBuildException(sprintf(
                'Object `%s` does not exist in relation `%s`',
                $relation->getForeignObjectKey(),
                $relation->getName()
            ));
        }

        if ('propel' !== strtolower($foreignObject->getDataModel())) {
            throw new ModelBuildException(sprintf(
                'Can not create a relation between two different dataModels. Got `%s` but propel is needed.',
                $foreignObject->getDataModel()
            ));
        }

        $foreigns = $xmlTable->xpath('foreign-key[@phpName=\'' . $relationName . '\']');
        if ($foreigns) {
            $foreignKey = current($foreigns);
        } else {
            $foreignKey = $xmlTable->addChild('foreign-key');
        }

        $foreignKey['phpName'] = $relationName;
        $foreignKey['foreignTable'] = $foreignObject->getTable();

        $foreignKey['onDelete'] = $relation->getOnDelete();
        $foreignKey['onUpdate'] = $relation->getOnUpdate();

        $references = $foreignKey->xpath("reference[not(@custom='true')]");
        foreach ($references as $i => $ref) {
            unset($references[$i][0]);
        }

        foreach ($relation->getReferences() as $reference) {
            $localName = Tools::camelcase2Underscore($reference->getLocalColumn()->getName());
            $references = $foreignKey->xpath('reference[@local=\'' . $localName . '\']');
            if ($references) {
                $xmlReference = current($references);
            } else {
                $xmlReference = $foreignKey->addChild('reference');
            }

            $xmlReference['local'] = $localName;
            $xmlReference['foreign'] = Tools::camelcase2Underscore($reference->getForeignColumn()->getName());
        }

    }

    protected function setupColumnAttributes(ColumnDefinitionInterface $column, $xmlColumn)
    {
        $xmlColumn['name'] = Tools::camelcase2Underscore($column->getName());

        $type = $column->getSqlDataType();
        $size = null;
        if (false !== $pos = strpos($type, '(')) {
            $size = trim(str_replace(['(', ')'], '', substr($type, $pos)));
            $type = substr($type, 0, $pos);
        }

        $propelType = $this->getPropelColumnType($type);

        $xmlColumn['type'] = strtoupper($propelType);

        if ($size) {
            $xmlColumn['size'] = $size;
        }
    }

    /**
     * Transform some sql types to propel types
     *
     * @param string $type
     * @return mixed
     */
    public function getPropelColumnType($type)
    {
        $map = [
            'text' => 'LONGVARCHAR',
        ];

        return @$map[strtolower($type)] ? : $type;
    }

}