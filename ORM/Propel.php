<?php

namespace Kryn\CmsBundle\ORM;

use Kryn\CmsBundle\Configuration\Condition;
use Kryn\CmsBundle\Configuration\ConditionSubSelect;
use Kryn\CmsBundle\Exceptions\FileNotFoundException;
use Kryn\CmsBundle\Exceptions\ObjectNotFoundException;
use Kryn\CmsBundle\Objects;
use Kryn\CmsBundle\Propel\StandardEnglishPluralizer;
use Kryn\CmsBundle\Tools;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Map\RelationMap;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Propel as RuntimePropel;

/**
 * Propel ORM Wrapper.
 */
class Propel extends ORMAbstract
{

    /**
     * The key of the object
     *
     * @var string
     */
    public $objectKey;

    protected $query;

    /**
     * @var TableMap
     */
    protected $tableMap;

    protected $propelPrimaryKeys;

    public function init()
    {
        if ($this->propelPrimaryKeys) {
            return;
        }

        $this->query = $this->getQueryClass();
        $this->tableMap = $this->query->getTableMap();
        $this->propelPrimaryKeys = $this->tableMap->getPrimaryKeys();
    }

    /**
     * Propel uses for his nested-set objects `lft` and `rgt` fields.
     * So we include with this condition all entries 'inside' the entry
     * defined through $condition.
     *
     * @param Condition $condition
     * @return Condition
     */
    public function getNestedSubCondition(Condition $condition)
    {
        $result = new Condition(null, $this->getKrynCore());
        $sub = new ConditionSubSelect(null, $this->getKrynCore());
        $sub->select('lft');
        $sub->setTableName('parent');
        $sub->addSelfJoin('parent', '%table%.lft BETWEEN parent.lft+1 AND parent.rgt-1');
        $sub->setRules($condition->getRules());

        $result->addAnd([
            'lft', 'IN', $sub
        ]);

        return $result;
    }

    /**
     * Filters $fields by allowed fields.
     * If '*' we return all allowed fields.
     *
     * @param  array|string $fields
     *
     * @return array
     */
    public function getFields($fields)
    {
        $this->init();

        if ($fields != '*' && is_string($fields)) {
            $fields = explode(',', str_replace(' ', '', trim($fields)));
        }

        $query = $this->getQueryClass();
        $tableMap = $query->getTableMap();

        $fields2 = array();
        $relations = array();
        $relationFields = array();

        foreach ($this->propelPrimaryKeys as $primaryKey) {
            $fields2[$primaryKey->getPhpName()] = $primaryKey;
        }

        if ($fields == '*') {

            $columns = $tableMap->getColumns();
            foreach ($columns as $column) {
                $fields2[$column->getPhpName()] = $column;
            }

            //add relations
            $relationMap = $tableMap->getRelations();

            foreach ($relationMap as $relationName => $relation) {
                if (!isset($relations[$relationName])) {
                    $relations[$relationName] = $relation;

                    //add columns
                    if ($localColumns = $relation->getLeftColumns()) {
                        foreach ($localColumns as $col) {
                            $fields2[$col->getPhpName()] = $col;
                        }
                    }
                    $relations[ucfirst($relationName)] = $relation;

                    $cols = $relation->getRightTable()->getColumns();
                    foreach ($cols as $col) {
                        if ($relation->getType() == RelationMap::ONE_TO_ONE || $relation->getType() == RelationMap::MANY_TO_ONE) {
                            $fields2[$relationName . '.' . $col->getPhpName()] = $col;
                        } else {
                            $relationFields[ucfirst($relationName)][] = $col->getPhpName();
                        }
                    }
                }
            }

        } else if (is_array($fields)) {
            foreach ($fields as $field) {

                $relationFieldSelection = [];
                $relationName = '';

                if (($pos = strpos($field, '.')) !== false) {
                    $relationName = ucfirst(substr($field, 0, $pos));
                    $relationFieldSelection = explode(',', str_replace(' ', '', ucfirst(substr($field, $pos + 1))));
                    if (!$tableMap->hasRelation(ucfirst($relationName))) {
                        continue;
                    }

                } elseif ($tableMap->hasRelation(ucfirst($field))) {
                    $relationName = ucfirst($field);
                }

                if ($relationName) {
                    $relation = $tableMap->getRelation(ucfirst($relationName));

                    //select at least all pks of the foreign table
                    $pks = $relation->getRightTable()->getPrimaryKeys();
                    foreach ($pks as $pk) {
                        $relationFields[ucfirst($relationName)][] = $pk->getPhpName();
                    }

                    if (isset($relationFieldSelection[0]) && '*' === $relationFieldSelection[0]) {
                        foreach ($relation->getRightTable()->getColumns() as $col) {
                            if (!$col->isPrimaryKey()) {
                                $relationFields[ucfirst($relationName)][] = $col->getPhpName();
                            }
                        }
                    } else {

                        foreach ($relationFieldSelection as $relationField) {
                            //check if $relationField exists in the foreign table
                            if (!$relation->getRightTable()->hasColumnByPhpName($relationField)) {
                                continue;
                            }
                            $relationFields[ucfirst($relationName)][] = $relationField;
                        }
                    }

                    $relations[ucfirst($relationName)] = $relation;

                    //add foreignKeys in main table.
                    if ($localColumns = $relation->getLeftColumns()) {
                        foreach ($localColumns as $col) {
                            $fields2[$col->getPhpName()] = $col;
                        }
                    }

                    continue;
                }

                if ($tableMap->hasColumnByPhpName(ucfirst($field)) &&
                    $column = $tableMap->getColumnByPhpName(ucfirst($field))
                ) {
                    $fields2[$column->getPhpName()] = $column;
                }
            }

        }

        //filer relation fields
        foreach ($relationFields as $relation => &$objectFields) {

            $relationDef = $this->getDefinition()->getField($relation);
            if (!$relationDef) {
                continue;
            }
            $def = $this->getKrynCore()->getObjects()->getDefinition($relationDef->getObject());
            $limit = $def['blacklistSelection'];
            if (!$limit) {
                continue;
            }
            $allowedFields = strtolower(',' . str_replace(' ', '', trim($limit)) . ',');

            $filteredFields = array();
            foreach ($objectFields as $name) {
                if (strpos($allowedFields, strtolower(',' . $name . ',')) === false) {
                    $filteredFields[] = $name;
                }
            }
            $objectFields = $filteredFields;

        }

        //filter
        if ($blacklistSelection = $this->definition->getBlacklistSelection()) {

            $blacklistedFields = strtolower(',' . str_replace(' ', '', trim($blacklistSelection)) . ',');

            $filteredFields = array();
            foreach ($fields2 as $name => $def) {
                if (strpos($blacklistedFields, strtolower(',' . $name . ',')) === false) {
                    $filteredFields[$name] = $def;
                }
            }
            $filteredRelations = array();
            foreach ($relations as $name => $def) {
                if (strpos($blacklistedFields, strtolower(',' . $name . ',')) === false) {
                    $filteredRelations[$name] = $def;
                }
            }

            return array($filteredFields, $filteredRelations, $relationFields);
        }

        return array($fields2, $relations, $relationFields);

    }

    /**
     * Returns a new query class.
     *
     * @param null $name
     *
     * @return mixed
     * @throws ObjectNotFoundException
     */
    public function getQueryClass($name = null)
    {
        $objectKey = $this->getPhpName($name);

        $clazz = $objectKey . 'Query';
        if (!class_exists($clazz)) {
            throw new ObjectNotFoundException(sprintf('The object query %s of %s does not exist.', $clazz, $objectKey));
        }

        return $clazz::create();
    }

    /**
     * Returns php class name.
     *
     * @param  string $objectName
     *
     * @return string
     */
    public function getPhpName($objectName = null)
    {
        $clazz = Objects::normalizeObjectKey($objectName ?: $this->getObjectKey());
        $clazz = ucfirst($this->getKrynCore()->getObjects()->getNamespace($clazz) . '\\Model\\' . $this->getKrynCore()->getObjects()->getName($clazz));
        return $clazz;
    }

    /**
     * Since the core provide the pk as array('id' => 123) and not as array(123) we have to convert it for propel orm.
     *
     * @param  array $pk
     *
     * @return mixed Propel PK
     */
    public function getPropelPk($pk)
    {
        $pk = array_values($pk);
        if (count($pk) == 1) {
            $pk = $pk[0];
        }
        return $pk;
    }

    /**
     * @param ModelCriteria $query
     * @param array $options
     *
     * @throws FileNotFoundException
     */
    public function mapOptions(ModelCriteria $query, $options = array())
    {
        if (isset($options['limit'])) {
            $query->limit($options['limit']);
        }

        if (isset($options['offset'])) {
            $query->offset($options['offset']);
        }

        if (isset($options['order']) && is_array($options['order'])) {
            foreach ($options['order'] as $field => $direction) {

                $fieldName = ucfirst($field);
                $tableMap = $this->tableMap;
                if (false !== $pos = strpos($field, '.')) {
                    $relationName = ucfirst(substr($field, 0, $pos));
                    $fieldName = ucfirst(substr($field, $pos + 1));
                    if (!$relation = $this->tableMap->getRelation($relationName)) {
                        throw new FileNotFoundException(sprintf('Relation `%s` in object `%s` not found', $relationName, $this->getObjectKey()));
                    }
                    $tableMap = $relation->getForeignTable();
                }

                if (!$tableMap->hasColumnByPhpName(ucfirst($fieldName))) {
                    throw new FileNotFoundException(sprintf('Field `%s` in object `%s` not found', $fieldName, $tableMap->getPhpName()));
                } else {
                    $column = $this->tableMap->getColumnByPhpName(ucfirst($fieldName));

                    $query->orderBy($column->getName(), $direction);
                }
            }
        }
    }

    /**
     * @param ModelCriteria $query
     * @param \Kryn\CmsBundle\Configuration\Condition $condition
     * @return \PDOStatement
     * @throws \PDOException
     */
    public function getStm(ModelCriteria $query, Condition $condition = null)
    {
        $params = [];
        $condition2Params = [];
        $id = (hexdec(uniqid()) / mt_rand()) + mt_rand();

        // check that the columns of the main class are already added (if this is the primary ModelCriteria)
        if (!$query->hasSelectClause() && !$query->getPrimaryCriteria()) {
            $query->addSelfSelectColumns();
        }

        $con = RuntimePropel::getServiceContainer()->getReadConnection($query->getDbName());
        $query->configureSelectColumns();

        $dbMap = RuntimePropel::getServiceContainer()->getDatabaseMap($query->getDbName());
        $db = RuntimePropel::getServiceContainer()->getAdapter($query->getDbName());

        $model = $query->getModelName();
        $tableMap = constant($model . '::TABLE_MAP');

        $query->setPrimaryTableName(constant($tableMap . '::TABLE_NAME'));

        $query->externalBasePreSelect($con);

        if ($condition) {
            $query->where($id . ' = ' . $id);
        }

        $sql = $query->createSelectSql($params);

        if ($condition) {
            $condition2Params = $params;
            $conditionSql = $condition->toSql($condition2Params, $this->getObjectKey());
        }

        if ($condition && $conditionSql) {
            $sql = str_replace($id . ' = ' . $id, '(' . $conditionSql . ')', $sql);
        }

        /** @var \PDOStatement $stmt */
        $stmt = $con->prepare($sql);
        $db->bindValues($stmt, $params, $dbMap);

        if ($condition2Params) {
            foreach ($condition2Params as $idx => $v) {
                if (!is_array($v)) { //propel uses arrays as bind values, we with Condition->toSql not.
                    $stmt->bindValue($idx, $v);
                }
            }
        }

        try {
            $stmt->execute();
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage() . "\nSQL: $sql");
        }

        return $stmt;
    }

    public function mapToOneRelationFields(&$query, $relations, $relationFields)
    {
        if ($relations) {
            foreach ($relations as $name => $relation) {
                if ($relation->getType() != RelationMap::MANY_TO_MANY && $relation->getType(
                ) != RelationMap::ONE_TO_MANY
                ) {

                    $query->{'join' . $name}($name);
                    //$query->with($name);

                    if (isset($relationFields[$name])) {
                        foreach ($relationFields[$name] as $col) {
                            $query->withColumn($name . "." . $col, '"' . $name . "." . $col . '"');
                        }
                    }

                }
            }
        }

    }

    /**
     * Generates a row from the propel object using the get*() methods. Resolves *-to-many relations.
     *
     * @param      $clazz
     * @param      $row
     * @param      $selects
     * @param      $relations
     * @param      $relationFields
     * @param bool $permissionCheck
     *
     * @return array
     */
    public function populateRow($clazz, $row, $selects, $relations, $relationFields, $permissionCheck = false)
    {
        $item = new $clazz();
        $item->fromArray($row);

        $newRow = [];
        foreach ($selects as $select) {
            if (strpos($select, '.') === false) {
                $newRow[lcfirst($select)] = $item->{'get' . $select}();
            }
        }

        if (!$relations) {
            return $newRow;
        }

        foreach ($relations as $name => $relation) {

            /** @var $relation \Propel\Runtime\Map\RelationMap */
            if ($relation->getType() != RelationMap::MANY_TO_MANY && $relation->getType() != RelationMap::ONE_TO_MANY) {

                if (isset($relationFields[$name]) && is_array($relationFields[$name])) {

                    $foreignClazz = $relation->getForeignTable()->getClassName();
                    $foreignObj = new $foreignClazz();
                    $foreignRow = array();
                    $allNull = true;

                    foreach ($relationFields[$name] as $col) {
                        if ($row[$name . "." . $col] !== null) {
                            $foreignRow[$col] = $row[$name . "." . $col];
                            $allNull = false;
                        }
                    }

                    if ($allNull) {
                        $newRow[lcfirst($name)] = null;
                    } else {
                        $foreignObj->fromArray($foreignRow);
                        $foreignRow = array();
                        foreach ($relationFields[$name] as $col) {
                            $foreignRow[lcfirst($col)] = $foreignObj->{'get' . $col}();
                        }
                        $newRow[lcfirst($name)] = $foreignRow;
                    }
                }
            } else {
                //many-to-one and many-to-many, we need a extra query
                if (is_array($relationFields[$name]) && $relationField = $this->getDefinition()->getField($name)) {
                    if (!$relationObjectName = $relationField->getObject()) {
                        $relationObjectName = $this->getDefinition()->getKey();
//                            if (!$relationField->getObjectDefinition() || !$relationObjectName = $relationField->getObjectDefinition()->getKey()) {
//                                throw new ObjectNotFoundException(sprintf('No object defined for relation `%s`.', $relationField->getId()));
//                            }
                    }

                    $sClazz = $relation->getRightTable()->getClassname();

                    $queryName = $sClazz . 'Query';
                    if ($relation->getType() === RelationMap::MANY_TO_MANY) {
                        $filterBy = 'filterBy' . $this->getDefinition()->getId();
                    } else {
                        $filterBy = 'filterBy' . $relation->getSymmetricalRelation()->getName();
                    }

                    $sQuery = $queryName::create()
                        ->select($relationFields[$name])
                        ->$filterBy(
                            $item
                        );

                    $condition = null;
                    if ($permissionCheck) {
                        $condition = $this->getKrynCore()->getACL()->getListingCondition($relationObjectName);
                    }
                    $sStmt = $this->getStm($sQuery, $condition);

                    $sItems = array();
                    while ($subRow = $sStmt->fetch(\PDO::FETCH_ASSOC)) {

                        $sItem = new $sClazz();
                        $sItem->fromArray($subRow);

                        $temp = array();
                        foreach ($relationFields[$name] as $select) {
                            $temp[lcfirst($select)] = $sItem->{'get' . $select}();
                        }
                        $sItems[] = $temp;
                    }
                } else {
                    $get = 'get' . $relation->getPluralName();
                    $sItems = $item->$get();
                }

                if ($sItems instanceof ObjectCollection) {
                    $newRow[lcfirst($name)] = $sItems->toArray(null, null, TableMap::TYPE_STUDLYPHPNAME) ? : null;
                } else if (is_array($sItems) && $sItems) {
                    $newRow[lcfirst($name)] = $sItems;
                } else {
                    $newRow[lcfirst($name)] = null;
                }
            }
        }

        return $newRow;
    }

    /**
     * {@inheritDoc}
     */
    public function getItems(Condition $condition = null, $options = null)
    {
        $this->init();
        $query = $this->getQueryClass();

        list($fields, $relations, $relationFields) = $this->getFields($options['fields']);
        $selects = array_keys($fields);

        $this->mapOptions($query, $options);
        $this->mapToOneRelationFields($query, $relations, $relationFields);

        if ($this->definition->isNested()) {
            $query->filterByLft(1, Criteria::GREATER_THAN);
            $selects[] = 'Lft';
            $selects[] = 'Rgt';
            $selects[] = 'Lvl';
        }

        $query->select($selects);

        $stmt = $this->getStm($query, $condition);

        $clazz = $this->getPhpName();

        $result = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $result[] = $this->populateRow(
                $clazz,
                $row,
                $selects,
                $relations,
                $relationFields,
                isset($options['permissionCheck']) ? $options['permissionCheck'] : null
            );
        }

        return $result;
    }

    /**
     * Sets the filterBy<pk> by &$query from $pk.
     *
     * @param mixed $query
     * @param array $pk
     */
    public function mapPk(&$query, $pk)
    {
        foreach ($this->primaryKeys as $key) {
            $filter = 'filterBy' . ucfirst($key);
            $val = $pk[$key];
            if (method_exists($query, $filter)) {
                $query->$filter($val);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getItem($pk, $options = array())
    {
        $this->init();
        $query = $this->getQueryClass();
        $query->limit(1);

        list($fields, $relations, $relationFields) = $this->getFields($options['fields']);

        $selects = array_keys($fields);

        $query->select($selects);

        $this->mapOptions($query, $options);

        $this->mapToOneRelationFields($query, $relations, $relationFields);

        $this->mapPk($query, $pk);

        $stmt = $this->getStm($query);

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        $clazz = $this->getPhpName();

        return $row === false ? null : $this->populateRow(
            $clazz,
            $row,
            $selects,
            $relations,
            $relationFields,
            @$options['permissionCheck']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function remove($pk)
    {
        $query = $this->getQueryClass();

        $this->mapPk($query, $pk);
        $item = $query->findOne();
        if (!$item) {
            return false;
        }

        $item->delete();

        return $item->isDeleted();
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $query = $this->getQueryClass();

        if ($this->definition->getWorkspace()) {
            //delete all versions
            $versionQueryQuery = $this->getPhpName() . 'Query';
            $versionQueryQuery::create()->deleteAll();
        }

        $query->deleteAll();
    }

    public function getVersions($pk, $options = null)
    {
        $queryClass = $this->getPhpName() . 'VersionQuery';
        $query = new $queryClass();

        $query->select(array('id', 'workspaceRev', 'workspaceAction', 'workspaceActionDate', 'workspaceActionUser'));

        $query->filterByWorkspaceId(\Kryn\CmsBundle\WorkspaceManager::getCurrent());

        $this->mapPk($query, $pk);

        return $query->find()->toArray();

    }

    public function getVersionDiff($pk, $options = null)
    {
        //default is the diff to the previous

    }

    /**
     * {@inheritdoc}
     */
    public function update($pk, $values)
    {
        $this->init();

        $query = $this->getQueryClass();

        $this->mapPk($query, $pk);
        $item = $query->findOne();
        $values2 = $pk + $values;
        $this->mapValues($item, $values2);

        return $item->save() ? true : false;
    }

    /**
     * {@inheritdoc}
     */
    public function patch($pk, $values)
    {
        $this->init();

        $query = $this->getQueryClass();

        $this->mapPk($query, $pk);
        $item = $query->findOne();
        $this->mapValues($item, $values, true);

        return $item->save() ? true : false;
    }

    /**
     * {@inheritDoc}
     */
    public function move($pk, $targetPk, $position = 'first', $targetObjectKey = null)
    {
        $query = $this->getQueryClass();
        $item = $query->findPK($this->getPropelPk($pk));

        $method = 'moveToFirstChildOf';
        if ($position == 'up' || $position == 'before' || $position == 'prev') {
            $method = 'moveToPrevSiblingOf';
        }
        if ($position == 'down' || $position == 'below' || $position == 'next') {
            $method = 'moveToNextSiblingOf';
        }

        if (!$targetPk) {
            //we need a target
            return null;
        } else {

            if ($targetObjectKey && $this->getObjectKey() != $targetObjectKey) {
                if (!$this->definition['nestedRootAsObject']) {
                    throw new \InvalidArgumentException('This object has no different object as root.');
                }

                $scopeId = $item->getScopeValue();
                $method = 'moveToFirstChildOf';

                $target = $query->findRoot($scopeId);
            } else {
                $target = $query->findPK($this->getPropelPk($targetPk));
            }

            if (!$target) {
                return false;
            }
        }

        if ($item == $target) {
            return false;
        }

        if ($target) {
            return $item->$method($target) ? true : false;
        } else {
            throw new \Exception('Can not find the appropriate target.');
        }

    }

    public function getRoots(Condition $condition = null, $options = null)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function add($values, $targetPk = null, $mode = 'first', $scope = null)
    {
        $this->init();

        $clazz = $this->getPhpName();
        $obj = new $clazz();

        $this->mapValues($obj, $values);

        if ($this->definition->isNested()) {

            $root = false;
            $query = $this->getQueryClass();
            if ($targetPk) {
                $branch = $query->findPk($this->getPropelPk($targetPk));
            } elseif ($scope !== null) {
                $branch = $query->findRoot($scope);
                $root = true;
                if (!$branch) {
                    //no root, create one
                    $branch = new $clazz();
                    $branch->setScopeValue($scope);
                    $branch->makeRoot();
                    $branch->save();
                }
            }

            if (!$branch) {
                return false;
            }

            if ($branch->getLeftValue() == 1) {
                $root = true;
            }

            if (!$scope) {
                $scope = $branch->getScopeValue();
            }

            switch (strtolower($mode)) {
                case 'last':
                    $obj->insertAsLastChildOf($branch);
                    break;
                case 'prev':
                    if (!$root) {
                        $obj->insertAsPrevSiblingOf($branch);
                    }
                    break;
                case 'next':
                    if (!$root) {
                        $obj->insertAsNextSiblingOf($branch);
                    }
                    break;

                case 'first':
                default:
                    $obj->insertAsFirstChildOf($branch);
                    break;
            }

            if ($scope) {
                $obj->setScopeValue($scope);
            }
        }

        if (!$obj->save()) {
            return false;
        }

        //return new pk
        $newPk = array();
        foreach ($this->primaryKeys as $pk) {
            $newPk[$pk] = $obj->{'get' . ucfirst($pk)}();
        }

        return $newPk;
    }

    /**
     * @param $item propel object
     * @param array $values
     * @param bool $ignoreNotExistingValues
     */
    public function mapValues(&$item, &$values, $ignoreNotExistingValues = false)
    {
        $pluralizer = new StandardEnglishPluralizer();
        $setted = [];

        foreach ($this->definition->getFields(true) as $field) {
            $fieldName = $field->getId();
            $fieldName = lcfirst($fieldName);
            $setted[] = $fieldName;

            if (!isset($values[$fieldName]) && $ignoreNotExistingValues) {
                continue;
            }

            $fieldValue = @$values[$fieldName];

            if ($field->isPrimaryKey() && $field->isAutoIncrement()) {
                continue;
            }

            $fieldName = ucfirst($fieldName);
            $set = 'set' . $fieldName;
            $methodExist = method_exists($item, $set);

            if (!$field && !$methodExist) {
                continue;
            }

            if ($field['type'] == 'object') {

                if ($field['objectRelation'] == ORMAbstract::MANY_TO_MANY || $field['objectRelation'] == ORMAbstract::ONE_TO_MANY) {

                    $name = $pluralizer->getPluralForm($pluralizer->getSingularForm(Tools::underscore2Camelcase($fieldName)));
                    $setItems = 'set' . $name;
                    $clearItems = 'clear' . $name;

                    if ($fieldValue) {
                        $foreignQuery = $this->getQueryClass($field['object']);
                        $foreignClass = $this->getPhpName($field['object']);
                        $foreignObjClass = $this->getKrynCore()->getObjects()->getClass($field['object']);

                        if ($field['objectRelation'] == ORMAbstract::ONE_TO_MANY) {

                            $coll = new ObjectCollection();
                            $coll->setModel(ucfirst($foreignClass));

                            foreach ($fieldValue as $foreignItem) {
                                $pk = $this->getKrynCore()->getObjects()->getObjectPk($field['object'], $foreignItem);
                                $item2 = null;
                                if ($pk) {
                                    $pk = $this->getPropelPk($pk);
                                    $item2 = $foreignQuery->findPk($pk);
                                }
                                if (!$item2) {
                                    $item2 = new $foreignClass();
                                }
                                $item2->fromArray($foreignItem, TableMap::TYPE_STUDLYPHPNAME);
                                $coll[] = $item2;
                            }

                            $item->$setItems($coll);

                        } else {

                            $primaryKeys = array();
                            if (is_array($fieldValue)) {
                                foreach ($fieldValue as $value) {
                                    $primaryKeys[] = $foreignObjClass->normalizePrimaryKey($value);
                                }
                            }

                            $propelPks = array();
                            foreach ($primaryKeys as $primaryKey) {
                                $propelPks[] = $this->getPropelPk($primaryKey);
                            }

                            $collItems = $foreignQuery->findPks($propelPks);
                            $item->$setItems($collItems);
                        }
                    } elseif ($ignoreNotExistingValues) {
                        $item->$clearItems();
                    }
                    continue;
                }

                if ($field['objectRelation'] == ORMAbstract::MANY_TO_ONE || $field['objectRelation'] == ORMAbstract::ONE_TO_ONE) {
                    $relation = $this->tableMap->getRelation($fieldName);
                    $localColumns = $relation->getLocalColumns();
                    if (is_array($fieldValue)) {
                        foreach ($localColumns as $column) {
                            $setter = 'set' . ucfirst($column->getPhpName());
                            $key = lcfirst($column->getPhpName());
                            $item->$setter($fieldValue[$key]);
                        }
                    } else {
                        $firstColumn = current($localColumns);

                        $setter = 'set' . ucfirst($firstColumn->getPhpName());
                        $item->$setter($fieldValue);
                    }

                    continue;
                }
            }

            if ($methodExist) {
                $item->$set($fieldValue);
            }
        }

        /*
         * all virtual fields which are not present in the object.
         * Virtual fields are all methods in the model which have a setter.
         * Examples:
         *
         *   setPassword => 'password'
         */
        foreach ($values as $fieldName => $fieldValue) {
            $fieldName = lcfirst($fieldName);
            if (in_array($fieldName, $setted)) {
                continue;
            }

            $fieldName = ucfirst($fieldName);
            $set = 'set' . $fieldName;
            $methodExist = method_exists($item, $set);

            if ($methodExist) {
                $item->$set($fieldValue);
            }
        }

    }

    /**
     * {@inheritdoc}
     */
    public function getCount(Condition $condition = null)
    {
        $query = $this->getQueryClass();

        $query->clearSelectColumns()->addSelectColumn('COUNT(*)');
        $stmt = $this->getStm($query, $condition);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return current($row) + 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getBranchChildrenCount($pk = null, Condition $condition = null, $scope = null)
    {
        $query = $this->getQueryClass();

        $query->clearSelectColumns()->addSelectColumn('COUNT(*)');

        if ($pk) {
            $pk2Query = $this->getQueryClass();

            $this->mapPk($pk2Query, $pk);
            $pk2Item = $pk2Query->findOne();

            if (!$pk2Item) {
                return null;
            }
            $query->childrenOf($pk2Item);
        } elseif ($scope) {
            $pk2Query = $this->getQueryClass();
            $root = $pk2Query->findRoot($scope);
            if (!$root) {
                return null;
            }
            $query->childrenOf($root);
        }

        $stmt = $this->getStm($query, $condition);

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return current($row) + 0;

    }

    public function pkFromRow($row)
    {
        $pks = array();
        foreach ($this->primaryKeys as $pk) {
            $pks[$pk] = $row[$pk];
        }

        return $pks;
    }

    /**
     * {@inheritdoc}
     */
    public function getBranch($pk = null, Condition $condition = null, $depth = 1, $scope = null, $options = null)
    {
        $query = $this->getQueryClass();
        if (!$pk) {
            if ($scope === null && $this->definition['nestedRootAsObject']) {
                throw new \InvalidArgumentException('Argument `scope` is missing. Since this object is a nested set with different roots, we need a `scope` to get the first level.');
            }
            $parent = $query->findRoot($scope);
        } else {
            $parent = $query->findPK($this->getPropelPk($pk));
        }

        if (!$parent) {
            return null;
        }

        if ($depth === null) {
            $depth = 1;
        }

        $query = $this->getQueryClass();

        $query->childrenOf($parent);

        list($fields, $relations, $relationFields) = $this->getFields($options['fields']);
        $selects = array_keys($fields);

        $selects[] = 'Lft';
        $selects[] = 'Rgt';
        $selects[] = 'Title';
        $query->select($selects);

        $query->orderByBranch();

        $this->mapOptions($query, $options);

        $this->mapToOneRelationFields($query, $relations, $relationFields);

        $stmt = $this->getStm($query, $condition);

        $clazz = $this->getPhpName();

        $result = null;
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $item = $this->populateRow(
                $clazz,
                $row,
                $selects,
                $relations,
                $relationFields,
                $options['permissionCheck']
            );

            if ($depth > 0) {
                if (!$condition) {
                    $item['_childrenCount'] = ($item['rgt'] - $item['lft'] - 1) / 2;
                    if ($depth > 1 && ($item['rgt'] - $item['lft']) > 0) {
                        $item['_children'] = $this->getBranch(
                            $this->pkFromRow($item),
                            $condition,
                            $depth - 1,
                            $scope,
                            $options
                        );
                    }
                } else {

                    //since we have a custom (probably a permission listing condition) we have
                    //firstly to select all children and then count
                    if ($depth > 1) {
                        $item['_children'] = $this->getBranch(
                            $this->pkFromRow($item),
                            $condition,
                            $depth - 1,
                            $scope,
                            $options
                        );
                        $item['_childrenCount'] = count($item['_children']);
                    } else {
                        $children = $this->getBranch(
                            $this->pkFromRow($item),
                            $condition,
                            $depth - 1,
                            $scope,
                            $options
                        );
                        $item['_childrenCount'] = count($children);
                    }
                }
            }
            $result[] = $item;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getParents($pk, $options = null)
    {
        $query = $this->getQueryClass();
        $item = $query->findPK($this->getPropelPk($pk));

        if (!$item) {
            throw new \Exception('Can not found entry. ' . var_export($pk, true));
        }
        if (!$item->getRgt()) {
            throw new \Exception('Entry it not in a tree. ' . var_export($pk, true));
        }

        list($fields, $relations, $relationFields) = $this->getFields(@$options['fields']);
        $selects = array_keys($fields);

        $selects[] = 'Lft';
        $selects[] = 'Rgt';
        $selects[] = 'Title';
        $query->select($selects);

        $this->mapOptions($query, $options);

        $this->mapToOneRelationFields($query, $relations, $relationFields);

        $query->ancestorsOf($item);
        $query->orderByLevel();

        $stmt = $this->getStm($query);
        $clazz = $this->getPhpName();

        $result = array();

        if ($this->definition['nestedRootAsObject']) {
            //fetch root object entry
            $scopeField = 'get' . ucfirst($this->definition['nestedRootObjectField']);
            $scopeId = $item->$scopeField();
            $root = $this->getKrynCore()->getObjects()->get($this->definition['nestedRootObject'], $scopeId);
            $root['_object'] = $this->definition['nestedRootObject'];
            $result[] = $root;

        }
        $item = false;

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

            //propels nested set requires a own root item, we do not return this
            if (false === $item) {
                $item = true;
                continue;
            }

            $item = $this->populateRow(
                $clazz,
                $row,
                $selects,
                $relations,
                $relationFields,
                $options['permissionCheck']
            );
            $result[] = $item;
        }

        return $result;

    }

    /**
     * {@inheritdoc}
     */
    public function getParent($pk, $options = null)
    {
        $query = $this->getQueryClass();
        $item = $query->findPK($this->getPropelPk($pk));
        if (!$item) {
            return null;
        }

        list($fields, $relations, $relationFields) = $this->getFields($options['fields']);
        $selects = array_keys($fields);

        $selects[] = 'Lft';
        $selects[] = 'Rgt';
        $selects[] = 'Title';
        $query->select($selects);

        $this->mapOptions($query, $options);

        $this->mapToOneRelationFields($query, $relations, $relationFields);

        $query->ancestorsOf($item);
        $query->orderByLevel(true);
        $query->limit(1);

        $stmt = $this->getStm($query);
        $clazz = $this->getPhpName();

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $item = $this->populateRow($clazz, $row, $selects, $relations, $relationFields, $options['permissionCheck']);

        return $item;

    }

}
