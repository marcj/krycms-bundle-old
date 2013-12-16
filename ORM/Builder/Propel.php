<?php

namespace Kryn\CmsBundle\ORM\Builder;

use Kryn\CmsBundle\Configuration\Bundle;
use \Kryn\CmsBundle\Exceptions\ModelBuildException;
use Kryn\CmsBundle\Configuration\Field;
use Kryn\CmsBundle\Configuration\Object;
use Kryn\CmsBundle\Filesystem\Filesystem;
use Kryn\CmsBundle\Objects;
use Kryn\CmsBundle\Tools;

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

    function __construct(Filesystem $filesystem, Objects $objects)
    {
        $this->filesystem = $filesystem;
        $this->objects = $objects;
    }

    public function build(Object $object)
    {
        $bundlePath = $object->getKrynCore()->getBundleDir($object->getBundle()->getName());
        $bundle = $object->getBundle();

        $modelsFile = $bundlePath . 'Resources/config/kryn.propel.schema.test.xml';

        if ($this->filesystem->has($modelsFile) && $xmlString = $this->filesystem->read($modelsFile)) {
            $xml = @simplexml_load_string($xmlString);

            if ($xml === false) {
                $errors = libxml_get_errors();
                throw new ModelBuildException(sprintf('Parse error in %s: %s', $modelsFile, json_encode($errors, JSON_PRETTY_PRINT)));
            }
        } else {
            $xml = simplexml_load_string('<database></database>');
        }

        $xml['namespace'] = ucfirst($bundle->getNamespace());

        //search if we've already the table defined.
        $tables = $xml->xpath('table[@name=\'' . $object['table'] . '\']');

        if (!$tables) {
            $objectTable = $xml->addChild('table');
        } else {
            $objectTable = current($tables);
        }

        if (!$object['table']) {
            throw new ModelBuildException(sprintf('The object `%s` has no table defined.', $object->getId()));
        }

        $objectTable['name'] = $object['table'];
        $objectTable['phpName'] = $object['propelClassName'] ? : ucfirst($object->getId());

        $columnsDefined = array();

//        $clonedTable = simplexml_load_string($objectTable->asXML());

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

        foreach ($object->getFields() as $field) {

            $columns = $this->getColumnFromField(
                ucfirst($bundle->getNamespace()) . '\\' . $object->getId(),
                $field->getId(),
                $field,
                $objectTable,
                $xml,
                $null,
                $bundle
            );

            if (!$columns) {
                continue;
            }

            foreach ($columns as $key => $column) {
                //column exist?
                $eColumns = $objectTable->xpath('column[@name =\'' . $key . '\']');

                if ($eColumns) {
                    $newCol = current($eColumns);
                    if ($newCol['custom'] == true) {
                        continue;
                    }
                } else {
                    $newCol = $objectTable->addChild('column');
                }

                $newCol['name'] = $key;
                $columnsDefined[] = $key;

                foreach ($column as $k => $v) {
                    $newCol[$k] = $v;
                }
            }
        }

        //check for deleted columns
        $columns = $objectTable->xpath("column[not(@custom='true')]");
        foreach ($columns as $k => $column) {
            $col = $object->getField(underscore2Camelcase($column['name']));
            if (!$col) {
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
            $vendor = current($vendors);
        } else {
            $vendor = $objectTable->addChild('vendor');
        }
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

        $this->filesystem->write($modelsFile, $dom->saveXml());

        return true;
    }

    public function getPropelColumnType($field)
    {

        switch (strtolower($field['type'])) {
            case 'textarea':
            case 'wysiwyg':
            case 'codemirror':
            case 'textlist':
            case 'filelist':
            case 'layoutelement':
            case 'textlist':
            case 'array':
            case 'fieldtable':
            case 'fieldcondition':
            case 'objectcondition':
            case 'filelist':

                return 'LONGVARCHAR';

            case 'text':
            case 'password':
            case 'files':

                return 'VARCHAR';

            case 'page':
                return 'INTEGER';

            case 'file':
            case 'folder':

                return 'VARCHAR';

            case 'properties':

                return 'OBJECT';

            case 'select':

                if ($field['multi']) {
                    return 'LONGVARCHAR';
                } else {
                    return 'VARCHAR';
                }

            case 'lang':

                return 'VARCHAR';

            case 'number':

                return $field['number_type'] ? : 'INTEGER';

            case 'checkbox':

                return 'BOOLEAN';

            case 'custom':

                return $field['propelType'];

            case 'date':
            case 'datetime':

                if ($field['asUnixTimestamp'] === false) {
                    return $field['type'] == 'date' ? 'DATE' : 'TIMESTAMP';
                }

                return 'BIGINT';

            default:
                return false;
        }
    }

    public function getPropelAdditional($field)
    {

        $column = [];

        switch (strtolower($field['type'])) {
            case 'textarea':
            case 'wysiwyg':
            case 'codemirror':
            case 'textlist':
            case 'filelist':
            case 'layoutelement':
            case 'textlist':
            case 'array':
            case 'fieldtable':
            case 'fieldcondition':
            case 'objectcondition':
            case 'filelist':

                unset($column['size']);
                break;

            case 'text':
            case 'password':
            case 'files':

                if ($field['maxlength']) {
                    $column['size'] = $field['maxlength'];
                }
                break;

            case 'page':
                unset($column['size']);
                break;

            case 'file':
            case 'folder':

                $column['size'] = 255;
                break;

            case 'properties':
                unset($column['size']);
                break;

            case 'select':

                if (!$field['multi']) {
                    $column['size'] = 255;
                }

                break;

            case 'lang':

                $column['size'] = 3;

                break;

            case 'number':

                if ($field['maxlength']) {
                    $column['size'] = $field['maxlength'];
                }

                break;

            case 'checkbox':

                break;

            case 'custom':

                if ($field['column']) {
                    foreach ($field['column'] as $k => $v) {
                        $column[$k] = $v;
                    }
                }

                break;

            case 'date':
            case 'datetime':

                break;

            case 'object':

                if ($field['objectRelation'] == 'nTo1' || $field['objectRelation'] == '1ToN') {
//                    $rightPrimaries = \Kryn\CmsBundle\Object::getPrimaries($field['object']);
                }

                break;
        }

        return $column;
    }


    /**
     * @param  string $object
     * @param  string $fieldKey
     * @param  array $field
     * @param  xml $table
     * @param  xml $database
     * @param  xml $refColumn
     * @param  Bundle $bundle
     *
     * @return array|bool
     */
    public function getColumnFromField(
        $object,
        $fieldKey,
        Field $field,
        &$table,
        &$database,
        &$refColumn = null,
        Bundle $bundle = null
    ) {

        $columns = array();
        if ($refColumn) {
            $column =& $refColumn;
        } else {
            $column = $this->getPropelAdditional($field);
            $column['type'] = $this->getPropelColumnType($field);
        }

        $object2 = \Kryn\CmsBundle\Object::getDefinition($object);

        if ($field->getVirtual()) {
            return;
        }

        switch (strtolower($field['type'])) {
            case 'object':

                $foreignObject = \Kryn\CmsBundle\Object::getDefinition($field['object']);

                if (!$foreignObject) {
                    throw new ModelBuildException(sprintf('The object `%s` does not exist in field `%s`.', $field['object'], $field['id']));
                }

                $relationName = ucfirst($field['objectRelationName'] ? : $foreignObject->getId());

                if ($field['objectRelation'] == 'nTo1' || $field['objectRelation'] == '1ToN') {

                    $leftPrimaries = \Kryn\CmsBundle\Object::getPrimaryList($object);
                    $rightPrimaries = \Kryn\CmsBundle\Object::getPrimaries($field['object']);

                    $foreignObject = \Kryn\CmsBundle\Object::getDefinition($field['object']);

                    if (!$foreignObject['table']) {
                        throw new ModelBuildException(sprintf(
                            'The object `%s` has no table defined. Used in field `%s`.',
                            $field['object'],
                            $field['id']
                        ));
                    }

                    $foreigns = $table->xpath('foreign-key[@phpName=\'' . $relationName . '\']');
                    if ($foreigns) {
                        $foreignKey = current($foreigns);
                    } else {
                        $foreignKey = $table->addChild('foreign-key');
                    }

                    $foreignKey['phpName'] = $relationName;
                    $foreignKey['foreignTable'] = $foreignObject['table'];

                    if ($field['objectRelationOnDelete']) {
                        $foreignKey['onDelete'] = $field['objectRelationOnDelete'];
                    }

                    if ($field['objectRelationOnUpdate']) {
                        $foreignKey['onUpdate'] = $field['objectRelationOnUpdate'];
                    }

                    $references = $foreignKey->xpath("reference[not(@custom='true')]");
                    foreach ($references as $i => $ref) {
                        unset($references[$i][0]);
                    }

                    if (count($rightPrimaries) == 1) {

                        $references = $foreignKey->xpath('reference[@local=\'' . camelcase2Underscore($fieldKey) . '\']');
                        if ($references) {
                            $reference = current($references);
                        } else {
                            $reference = $foreignKey->addChild('reference');
                        }

                        $reference['local'] = camelcase2Underscore($fieldKey);
                        $reference['foreign'] = key($rightPrimaries);

                        $column = $this->getPropelAdditional(current($rightPrimaries));
                        $column['type'] = $this->getPropelColumnType(current($rightPrimaries));

                    } else {

                        $columns = [];

                        //add left primary keys
                        foreach ($rightPrimaries as $key => $def) {
                            $references = $table->xpath('reference[@local=\'' . $fieldKey . '_' . $key . '\']');
                            if ($references) {
                                $reference = current($references);
                            } else {
                                $reference = $foreignKey->addChild('reference');
                            }

                            $reference['local'] = camelcase2Underscore($fieldKey) . '_' . $key;
                            $reference['foreign'] = $key;

                            //create additional fields
                            $columns = array_merge(
                                $columns,
                                $this->getColumnFromField(
                                    $object,
                                    underscore2Camelcase($fieldKey . '_' . $key),
                                    $def,
                                    $table,
                                    $database,
                                    $bundle
                                )
                            );
                        }

                        return $columns;
                    }

                } else {
                    //n-n, we need a extra table

                    $probableName = $bundle->getName() . '_' . camelcase2Underscore(
                            \Kryn\CmsBundle\Object::getName($object)
                        ) . '_' . camelcase2Underscore($fieldKey) . '_relation';

                    $table2Name = $field['objectRelationTable'] ? $field['objectRelationTable'] : $probableName;

                    //search if we've already the table defined.
                    $table2s = $database->xpath('table[@name=\'' . $table2Name . '\']');

                    if (!$table2s) {
                        $relationTable = $database->addChild('table');
                        $relationTable['name'] = $table2Name;
                        $relationTable['isCrossRef'] = "true";
                    } else {
                        $relationTable = current($table2s);
                    }

                    $relationTable['phpName'] = $relationName;

                    $foreignKeys = array();

                    //left columns
                    $leftPrimaries = \Kryn\CmsBundle\Object::getPrimaries($object);
                    foreach ($leftPrimaries as $key => $primary) {

                        $name = strtolower(\Kryn\CmsBundle\Object::getName($object)) . '_' . $key;
                        $cols = $relationTable->xpath('column[@name=\'' . $name . '\']');
                        $foreignKeys[$object2['table']][$key] = $name;
                        if ($cols) {
                            continue;
                        }

                        $col = $relationTable->addChild('column');
                        $col['name'] = $name;
                        $this->getColumnFromField($object, $key, $primary, $table, $database, $col, $bundle);
                        unset($col['autoIncrement']);
                        $col['required'] = "true";

                    }

                    //right columns
                    $rightPrimaries = \Kryn\CmsBundle\Object::getPrimaries($field['object']);
                    foreach ($rightPrimaries as $key => $primary) {

                        $name = camelcase2Underscore(\Kryn\CmsBundle\Object::getName($field['object'])) . '_' . $key;
                        $foreignKeys[$foreignObject['table']][$key] = $name;
                        $cols = $relationTable->xpath('column[@name=\'' . $name . '\']');
                        if ($cols) {
                            continue;
                        }

                        $col = $relationTable->addChild('column');
                        $col['name'] = $name;
                        $this->getColumnFromField($object, $key, $primary, $table, $database, $col, $bundle);
                        unset($col['autoIncrement']);
                        $col['required'] = "true";

                    }

                    //foreign keys
                    foreach ($foreignKeys as $table2 => $keys) {

                        $foreigns = $relationTable->xpath('foreign-key[@foreignTable=\'' . $table2 . '\']');
                        if ($foreigns) {
                            $foreignKey = current($foreigns);
                        } else {
                            $foreignKey = $relationTable->addChild('foreign-key');
                        }

                        $foreignKey['foreignTable'] = $table2;

                        if ($table2 == $foreignObject['table']) {
                            $foreignKey['phpName'] = ucfirst($fieldKey);
                        } else {
                            $foreignKey['phpName'] = ucfirst($fieldKey) . \Kryn\CmsBundle\Object::getName($object);
                        }

                        if ($object2['workspace']) {
                            $references = $foreignKey->xpath('reference[@local=\'workspace_id\']');
                            if ($references) {
                                $reference = current($references);
                            } else {
                                $reference = $foreignKey->addChild('reference');
                            }
                            $reference['local'] = 'workspace_id';
                            $reference['foreign'] = 'workspace_id';
                        }

                        foreach ($keys as $k => $v) {

                            $references = $foreignKey->xpath('reference[@local=\'' . $v . '\']');
                            if ($references) {
                                $reference = current($references);
                            } else {
                                $reference = $foreignKey->addChild('reference');
                            }

                            $reference['local'] = $v;
                            $reference['foreign'] = $k;

                        }
                    }

                    //workspace behavior if $object is workspaced
                    if ($object2['workspace']) {

                        $behaviors = $relationTable->xpath('behavior[@name=\'workspace\']');
                        if ($behaviors) {
                            $behavior = current($behaviors);
                        } else {
                            $behavior = $relationTable->addChild('behavior');
                        }
                        $behavior['name'] = 'workspace';
                    }

                    $vendors = $relationTable->xpath('vendor[@type=\'mysql\']');
                    if ($vendors) {
                        $vendor = current($vendors);
                    } else {
                        $vendor = $relationTable->addChild('vendor');
                    }
                    $vendor['type'] = 'mysql';

                    $params = $vendor->xpath('parameter[@name=\'Charset\']');
                    if ($params) {
                        $param = current($params);
                    } else {
                        $param = $vendor->addChild('parameter');
                    }

                    $param['name'] = 'Charset';
                    $param['value'] = 'utf8';

                    return false;

                }

                break;

        }

        if ($field['empty'] === 0 || $field['empty'] === false) {
            $column['required'] = "true";
        }

        if ($field['primaryKey']) {
            $column['primaryKey'] = "true";
        }
        if ($field['autoIncrement']) {
            $column['autoIncrement'] = "true";
        }

        $columns[Tools::camelcase2Underscore($fieldKey)] = $column;

        return $columns;
    }


}