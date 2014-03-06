<?php

namespace Kryn\CmsBundle;

use Kryn\CmsBundle\Configuration\Condition;
use Kryn\CmsBundle\Exceptions\AccessDeniedException;
use Kryn\CmsBundle\Exceptions\InvalidArgumentException;
use Kryn\CmsBundle\Exceptions\ObjectNotFoundException;
use Symfony\Component\EventDispatcher\GenericEvent;

class Objects
{
    /**
     * Array of instances of the object classes
     *
     * @var array
     */
    public $instances = array();

    /**
     * @var Core
     */
    protected $krynCore;

    function __construct($krynCore)
    {
        $this->krynCore = $krynCore;
    }

    /**
     * @param Core $krynCore
     */
    public function setKrynCore(Core $krynCore)
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
     * Translates the internal url to the real path.
     *
     * Example: getUrl('file://45') => '/myImageFolder/Picture1.png'
     *          getUrl('news://4') => '/newspage/detail/my-news-title'
     *          getUrl('user://1') => '/userdetail/admini-strator'
     *
     * @link http://docu.kryn.org/developer/extensions/internal-url
     *
     * Can return additionally 'http(s)://myDomain/' at the beginning if the target
     * is on a different domain.
     *
     * @static
     *
     * @param string $internalUrl
     *
     * @return string|bool
     */
    public function getUrl($internalUrl)
    {
        $pos = strpos($internalUrl, '://');
        $objectIds = substr($internalUrl, 0, $pos);
        $params = explode('/', substr($internalUrl, $pos + 2));

        $objectDefinition = $this->getDefinition($objectIds);
        if (!$objectDefinition) {
            return false;
        }

        if (method_exists($objectDefinition['_extension'], $objectDefinition['urlGetter'])) {
            return call_user_func(array($objectDefinition['_extension'], $objectDefinition['urlGetter']), $params);

        } else {
            return false;
        }
    }

    /**
     * Clears the instances cache.
     *
     */
    public function cleanup()
    {
        $this->instances = array();
    }

    /**
     * Parse the internal object url scheme and return the information as array.
     *
     * Pattern:
     *    object://<object_key>[/<primay_values_url_encoded-1>][/<primay_values_url_encoded-n>][/?<options_as_querystring>]
     *
     * Examples:
     *
     * 1. object://news/1
     *   => returns the object news with primary value equal 1
     *
     * 2. object://news/id=1
     *   => equal as 1.
     *
     * 3. object://news/1/2
     *   => returns a list of the objects with primary value equal 1 or 2
     *
     * 4. object://news/id=1/id=2
     *   => equal as 3.
     *
     * 5. object://object_with_multiple_primary/2,54
     *   => returns the object with the first primary field equal 2 and second primary field equal 54
     *
     * 6. object://object_with_multiple_primary/2,54/34,55
     *   => returns a list of the objects
     *
     * 7. object://object_with_multiple_primary/id=2,parent_id=54/id=34,parent_id=55
     *   => equal as 6 if the first defined primary is 'id' and the second 'parent_id'
     *
     * 8. object://news/1?fields=title
     *   => equal as 1. but returns only the field title
     *
     * 9. object://news/1?fields=title,category_id
     *   => equal as 1. but returns only the field title and category_id
     *
     * 10. object://news?fields=title
     *   => returns all objects from type news
     *
     * 11. object://news?fields=title&limit=5
     *   => returns first 5 objects from type news
     *
     *
     * @static
     *
     * @param  string $internalUrl
     *
     * @return array  [object_key, object_id/s, queryParams]
     */
    public function parseUrl($internalUrl)
    {
        $internalUrl = trim($internalUrl);

        $list = false;

        $catch = 'object://';
        if (substr(strtolower($internalUrl), 0, strlen($catch)) == $catch) {
            $internalUrl = substr($internalUrl, strlen($catch));
        }

        $catch = 'objects://';
        if (substr(strtolower($internalUrl), 0, strlen($catch)) == $catch) {
            $list = true;
            $internalUrl = substr($internalUrl, strlen($catch));
        }

        $firstSlashPos = strpos($internalUrl, '/');
        $questionPos = strpos($internalUrl, '?');

        if ($firstSlashPos === false && $questionPos === false) {
            return array(
                $internalUrl,
                false,
                array(),
                $list
            );
        }

        if ($firstSlashPos === false && $questionPos != false) {
            $objectKey = substr($internalUrl, 0, $questionPos);
        } else {
            $objectKey = $this->getObjectKey($internalUrl);
        }

        if (strpos($objectKey, '%')) {
            $objectKey = Tools::urlDecode($objectKey);
        }

        if (!$objectKey) {
            throw new \LogicException(sprintf('The url `%s` does not contain a object key.', $internalUrl));
        }

        $params = array();

        if ($questionPos !== false) {
            parse_str(substr($internalUrl, $questionPos + 1), $params);

            if ($firstSlashPos !== false) {
                $objectIds = substr($internalUrl, $firstSlashPos + 1, $questionPos - ($firstSlashPos + 1));
            }

        } else {
            $objectIds = substr($internalUrl, strlen($objectKey) + 1);
        }

        $objectIds = $this->parsePk($objectKey, $objectIds);

        if ($params && $params['condition']) {
            $params['condition'] = json_decode($params['condition'], true);
        }

        return array(
            $objectKey,
            (!$objectIds) ? false : $objectIds,
            $params,
            $list
        );
    }

    /**
     * Get object's definition.
     *
     * @param  string $objectKey `Core\Language` or `Core.Language`.
     *
     * @return Configuration\Object
     */
    public function getDefinition($objectKey)
    {
        $objectKey = Objects::normalizeObjectKey($objectKey);
        $temp = explode('/', $objectKey);
        if (2 !== count($temp)) {
            return null;
        }
        $bundleName = $temp[0];
        $name = $temp[1];

        $config = $this->getKrynCore()->getConfig($bundleName);

        if ($config) {
            return $config->getObject($name);
        }
    }

    /**
     * Cuts of the namespace/module name of a object key.
     *
     * kryncms/node => Node.
     *
     * @param  string $objectKey
     *
     * @return string
     */
    public function getName($objectKey)
    {
        $objectKey = Objects::normalizeObjectKey($objectKey);
        $temp = explode('/', $objectKey);
        $config = $this->getKrynCore()->getConfig($temp[0]);

        if ($config && ($object = $config->getObject($temp[1]))) {
            return $object->getId();
        }
    }

    /**
     * Cuts of the object name of the object key.
     *
     * kryncms/node => KrynCmsBundle.
     *
     * @param $objectKey
     * @return null|string
     */
    public function getBundleName($objectKey) {
        $objectKey = Objects::normalizeObjectKey($objectKey);
        $temp = explode('/', $objectKey);
        $config = $this->getKrynCore()->getConfig($temp[0]);

        return $config ? $config->getBundleName() : null;
    }

    /**
     * Returns the namespace of the bundle of the object key.
     *
     * KrynCmsBundle/node => KrynCmsBundle.
     * bundleWithNameSpace/myObject => Bundle\With\Namespace.
     *
     * @param  string $objectKey
     *
     * @return string
     */
    public function getNamespace($objectKey)
    {
        $objectKey = Objects::normalizeObjectKey($objectKey);
        $temp = explode('/', $objectKey);
        $config = $this->getKrynCore()->getConfig($temp[0]);

        return $config ? $config->getBundleClass()->getNamespace() : null;
    }

    /**
     * Returns true of the object is nested.
     *
     * @param  string $objectKey
     *
     * @return mixed
     */
    public function isNested($objectKey)
    {
        static $nested;
        if ($nested && $nested[$objectKey]) {
            return $nested[$objectKey];
        }
        $def = $this->getDefinition($objectKey);
        $nested[$objectKey] = ($def['nested']) ? true : false;

        return $nested[$objectKey];
    }

    /**
     * Returns the table name behind a object.
     * Not all objects has a table. If the object is based on propel's orm, then it has one.
     *
     * @param  string $objectKey
     *
     * @return string
     */
    public function getTable($objectKey)
    {
        static $tableName;
        if ($tableName && $tableName[$objectKey]) {
            return $this->getKrynCore()->getSystemConfig()->getDatabase()->getPrefix() . $tableName[$objectKey];
        }
        $def = $this->getDefinition($objectKey);
        $tableName[$objectKey] = $def['table'];

        return $this->getKrynCore()->getSystemConfig()->getDatabase()->getPrefix() . $tableName[$objectKey];
    }

    /**
     * Converts the primary key statement of a url to normalized structure.
     * Generates a array for the usage of Core\Object:get()
     *
     * 1,2,3 => array( array(id =>1),array(id =>2),array(id =>3) )
     * 1 => array(array(id => 1))
     * idFooBar => array( id => "idFooBar")
     * idFoo-Bar => array(array(id => idFoo, id2 => "Bar"))
     * 1-45, 2-45 => array(array(id => 1, pid = 45), array(id => 2, pid=>45))
     *
     *
     * @static
     *
     * @param  string $objectKey
     * @param  string $primaryKey
     *
     * @return array|mixed
     */
    public function parsePk($objectKey, $primaryKey)
    {
        $obj = $this->getClass($objectKey);

        $objectIds = $obj->primaryStringToArray($primaryKey);

        return $objectIds;
    }

    /**
     * Returns the object key (not id) from an object url.
     *
     * @param  string $url
     *
     * @return string
     */
    public function getObjectKey($url)
    {
        if (0 === strpos($url, 'object://')){
            $url = substr($url, strlen('object://'));
        }

        $idx = strpos($url, '/');

        if (false === $idx) {
            return false;
        }

        $idx = $idx + strpos(substr($url, $idx + 1), '/');

        return static::normalizeObjectKey(substr($url, 0, $idx + 1));
    }

    /**
     * Return only the primary keys of pItem as object.
     *
     * @param  string $objectKey
     * @param  array  $item
     *
     * @return string
     */
    public function getObjectPk($objectKey, $item)
    {
        $pks = $this->getPrimaryList($objectKey);
        $result = array();
        foreach ($pks as $pk) {
            if (@$item[$pk] !== null) {
                $result[$pk] = $item[$pk];
            }
        }

        return $result;
    }

    /**
     * This just cut off object://<objectName>/ and returns the primary key part as plain text.
     *
     * @param  string $url
     *
     * @return string
     */
    public function getCroppedObjectId($url)
    {
        if (strpos($url, 'object://') === 0) {
            $url = substr($url, 9);
        }

        $idx = strpos($url, '/'); //cut of bundleName
        $url = -1 === $idx ? $url : substr($url, $idx +1 );

        $idx = strpos($url, '/'); //cut of objectName
        $url = -1 === $idx ? $url : substr($url, $idx +1 );

        return $url;
    }

    /**
     * Returns the id of an object item for the usage in urls (internal url's) - urlencoded.
     *
     * @param  string $objectKey
     * @param  array  $pk
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getObjectUrlId($objectKey, $pk)
    {
        $pk = $this->normalizePk($objectKey, $pk);
        $pks = $this->getPrimaryList($objectKey);

        if (count($pks) == 0) {
            throw new \InvalidArgumentException($objectKey . ' does not have primary keys.');
        }

        $withFieldNames = !is_numeric(key($pk));

        if (count($pks) == 1 && is_array($pk)) {
            return Tools::urlEncode($pk[$withFieldNames ? $pks[0] : 0]);
        } else {
            $c = 0;
            $urlId = array();
            foreach ($pks as $pk2) {
                $urlId[] = Tools::urlEncode($pk[$withFieldNames ? $pk2 : $c]);
                $c++;
            }

            return implode(',', $urlId);
        }
    }

    /**
     * Checks if a field in a object exists.
     *
     * @param  string $objectKey
     * @param  string $field
     *
     * @return bool
     */
    public function checkField($objectKey, $field)
    {
        $definition = $this->getDefinition($objectKey);
        if (!$definition->getField($field)) {
            return false;
        }
        return true;
    }

    /**
     * Converts given object key and the object item to the internal url.
     *
     * @static
     *
     * @param  string $objectKey
     * @param  mixed  $primaryValues
     *
     * @return string
     */
    public function toUrl($objectKey, $primaryValues)
    {
        $url = 'object://' . $objectKey . '/';
        if (is_array($primaryValues)) {
            foreach ($primaryValues as $key => $val) {
                $url .= Tools::urlEncode($val) . ',';
            }
        } else {
            return $url . Tools::urlEncode($primaryValues);
        }

        return substr($url, 0, -1);
    }

    /**
     * Returns the object for the given url. Same arguments as in krynObjects::get() but given by a string.
     *
     * Take a look at the krynObjects::parseUrl() method for more information.
     *
     * @static
     *
     * @param $internalUrl
     *
     * @return object
     */
    public function getFromUrl($internalUrl)
    {
        list($objectKey, $objectIds, $params, $asList) = $this->parseUrl($internalUrl);

        return $asList ? $this->getList($objectKey, $objectIds, $params) : $this->get($objectKey, $objectIds, $params);
    }


    /**
     * Returns the single row of a object.
     * $options is a array which can contain following options. All options are optional.
     *
     *  'fields'          Limit the columns selection. Use a array or a comma separated list (like in SQL SELECT)
     *                    If empty all columns will be selected.
     *  'offset'          Offset of the result set (in SQL OFFSET)
     *  'limit'           Limits the result set (in SQL LIMIT)
     *  'order'           The column to order. Example:
     *                    array(
     *                      array('field' => 'category', 'direction' => 'asc'),
     *                      array('field' => 'title',    'direction' => 'asc')
     *                    )
     *
     *  'foreignKeys'     Define which column should be resolved. If empty all columns will be resolved.
     *                    Use a array or a comma separated list (like in SQL SELECT)
     *
     *  'permissionCheck' Defines whether we check against the ACL or not. true or false. default false
     *
     * @static
     *
     * @param  string $objectKey
     * @param  mixed  $pk
     * @param  array  $options
     *
     * @return array|null
     */
    public function get($objectKey, $pk, $options = array())
    {
        $obj = $this->getClass($objectKey);
        $primaryKey = $obj->normalizePrimaryKey($pk);
        $pk2s = $obj->getPrimaryKeys();
        $deleteFieldValues = null;

        if (!@$options['fields']) {
            if ($selection = $obj->definition->getDefaultSelection()) {
                $options['fields'] = $selection;
            } else {
                $options['fields'] = '*';
            }
        }

        if ($options['fields'] != '*' && $limitDataSets = $obj->definition->getLimitDataSets()) {

            if (is_string($options['fields'])) {
                $options['fields'] = explode(',', trim(str_replace(' ', '', $options['fields'])));
            }

            $extraFields = $limitDataSets->extractFields();
            $deleteFieldValues = array();

            foreach ($extraFields as $field) {
                if ($obj->definition->getField($field)) {
                    if (array_search($field, $options['fields']) === false && array_search($field, $pk2s) === false) {
                        $options['fields'][] = $field;
                        $deleteFieldValues[] = $field;
                    }
                }
            }
        }

        $item = $obj->getItem($primaryKey, $options);

        if (!$item) {
            return null;
        }

        if (@$options['permissionCheck'] && !$this->getKrynCore()->getACL()->checkViewExact($objectKey, $pk)) {
            return null;
        }

        if ($limitDataSets = $obj->definition->getLimitDataSets()) {
            if (!$this->satisfy($item, $limitDataSets)) {
                return null;
            }
        }

        if ($deleteFieldValues) {
            foreach ($deleteFieldValues as $field) {
                unset($item[$field]);
            }
        }

        return $item;

    }

    /**
     * Returns the list of objects.
     *
     *
     * $options is a array which can contain following options. All options are optional.
     *
     *  'fields'          Limit the columns selection. Use a array or a comma separated list (like in SQL SELECT)
     *                    If empty all columns will be selected.
     *  'offset'          Offset of the result set (in SQL OFFSET)
     *  'limit'           Limits the result set (in SQL LIMIT)
     *  'order'           The column to order. Example:
     *                    array(
     *                      array('category' => 'asc'),
     *                      array(title' => 'asc')
     *                    )
     *
     *  'permissionCheck' Defines whether we check against the ACL or not. true or false. default false
     *
     * @static
     *
     * @param string    $objectKey
     * @param array|Condition $condition
     * @param array     $options
     *
     * @return array|bool
     */
    public function getList($objectKey, $condition = null, $options = array())
    {
        $obj = $this->getClass($objectKey);
        $definition = $this->getDefinition($objectKey);

        if (!isset($options['fields'])) {
            $options['fields'] = $definition->getDefaultSelection() ? : '*';
        }

        $conditionObject = new \Kryn\CmsBundle\Configuration\Condition(null, $this->getKrynCore());

        if ($condition && is_array($condition)) {
            $conditionObject->fromPk($condition, $objectKey);
        } else if ($condition instanceof Condition) {
            $conditionObject = $condition;
        } else {
            $conditionObject = new \Kryn\CmsBundle\Configuration\Condition(null, $this->getKrynCore());
        }

        if ($limit = $obj->getDefinition()->getLimitDataSets()) {
            $conditionObject->mergeAnd($limit);
        }

        if (isset($options['permissionCheck']) && $aclCondition = $this->getKrynCore()->getACL()->getListingCondition($objectKey)) {
            $conditionObject->mergeAndBegin($aclCondition);
        }

        return $obj->getItems($conditionObject, $options);

    }

    /**
     * Returns the class object for $objectKey
     *
     * @param string $objectKey
     *
     * @return \Kryn\CmsBundle\ORM\ORMAbstract
     * @throws ObjectNotFoundException
     * @throws \Exception
     */
    public function &getClass($objectKey)
    {
        if (!isset($this->instances[$objectKey])) {
            $definition = $this->getDefinition($objectKey);

            if (!$definition) {
                throw new ObjectNotFoundException(sprintf('Object `%s` not found.', $objectKey));
            }

            if ('custom' === $definition->getDataModel()) {
                if (!class_exists($className = $definition['class'])) {
                    throw new \Exception(sprintf('Class for %s (%s) not found.', $objectKey, $definition['class']));
                }

                $this->instances[$objectKey] = new $className($objectKey, $definition, $this->getKrynCore());
            } else {
                $clazz = sprintf('\\Kryn\\CmsBundle\\ORM\\%s', ucfirst($definition->getDataModel()));
                if (class_exists($clazz) || class_exists($clazz = $definition->getDataModel())) {
                    $this->instances[$objectKey] = new $clazz($this->normalizeObjectKey($objectKey), $definition, $this->getKrynCore());
                }
            }
        }

        return $this->instances[$objectKey];
    }

    /**
     * Counts the items of $internalUrl
     *
     * @param $internalUrl
     *
     * @return array
     */
    public function getCountFromUrl($internalUrl)
    {
        list($objectKey, , $params) = $this->parseUrl($internalUrl);

        return $this->getCount($objectKey, $params['condition']);
    }


    /**
     * Removes all items.
     *
     * @param string $objectKey
     */
    public function clear($objectKey)
    {
        $obj = $this->getClass($objectKey);

        return $obj->clear();
    }

    /**
     * Counts the items of $objectKey filtered by $condition
     *
     * @param  string $objectKey
     * @param  array  $condition
     * @param  array  $options
     *
     * @return array
     */
    public function getCount($objectKey, $condition = null, $options = null)
    {
        $obj = $this->getClass($objectKey);

        $conditionObject = new \Kryn\CmsBundle\Configuration\Condition(null, $this->getKrynCore());

        if ($condition && is_array($condition)) {
            $conditionObject->fromPk($condition, $objectKey);
        } else if ($condition instanceof Condition) {
            $conditionObject = $condition;
        } else {
            $conditionObject = new \Kryn\CmsBundle\Configuration\Condition(null, $this->getKrynCore());
        }

        if ($limit = $obj->getDefinition()->getLimitDataSets()) {
            $conditionObject->mergeAnd($limit);
        }

        if ($options['permissionCheck'] && $aclCondition = $this->getKrynCore()->getACL()->getListingCondition($objectKey)) {
            $conditionObject->mergeAndBegin($aclCondition);
        }


        return $obj->getCount($conditionObject);
    }

    /**
     * Counts the items of $objectKey filtered by $condition
     *
     * @param  string $objectKey
     * @param  mixed  $pk
     * @param  array  $condition
     * @param  mixed  $scope
     * @param  array  $options
     *
     * @return array
     */
    public function getBranchChildrenCount(
        $objectKey,
        $pk = null,
        $condition = null,
        $scope = null,
        $options = null
    ) {
        $obj = $this->getClass($objectKey);

        if ($pk) {
            $pk = $obj->normalizePrimaryKey($pk);
        }

        $conditionObject = new Condition(null, $this->getKrynCore());

        if ($condition) {
            $conditionObject->fromPk($condition, $objectKey);
        }

        if ($limit = $obj->getDefinition()->getLimitDataSets()) {
            $conditionObject->mergeAnd($limit);
        }

        if ($options['permissionCheck'] && $aclCondition = $this->getKrynCore()->getACL()->getListingCondition($objectKey)) {
            $conditionObject->mergeAndBegin($aclCondition);
        }

        return $obj->getBranchChildrenCount($pk, $conditionObject, $scope, $options);

    }

    /**
     * Adds a item.
     *
     * @param string $objectKey
     * @param array  $values
     * @param mixed  $targetPk              Full PK as array or as primary key string (url).
     * @param string $position        If nested set. `first` (child), `last` (last child), `prev` (sibling), `next` (sibling)
     * @param bool   $targetObjectKey If this object key differs from $objectKey then we'll use $pk as `scope`. Also
     *                                 it is then only possible to have position `first` and `last`.
     * @param  array $options
     *
     * @return mixed
     *
     * @throws \NoFieldWritePermission
     * @throws \Kryn\CmsBundle\Exceptions\InvalidArgumentException
     */
    public function add(
        $objectKey,
        $values,
        $targetPk = null,
        $position = 'first',
        $targetObjectKey = null,
        $options = array()
    ) {

        $targetPk = $this->normalizePk($objectKey, $targetPk);
        $objectKey = Objects::normalizeObjectKey($objectKey);
        if ($targetObjectKey) {
            $targetObjectKey = Objects::normalizeObjectKey($targetObjectKey);
        }

        $obj = $this->getClass($objectKey);

        if (@$options['permissionCheck']) {

            foreach ($values as $fieldName => $value) {

                //todo, what if $targetObjectKey differs from $objectKey

                if (!$this->getKrynCore()->getACL()->checkAdd($objectKey, $targetPk, $fieldName)) {
                    throw new \NoFieldWritePermission(sprintf(
                        "No update permission to field '%s' in item '%s' from object '%s'",
                        $fieldName,
                        $targetPk,
                        $objectKey
                    ));
                }
            }
        }

        $args = [
            'pk' => $targetPk,
            'values' => &$values,
            'options' => &$options,
            'position' => &$position,
            'targetObjectKey' => &$targetObjectKey,
            'mode' => 'add'
        ];
        $eventPre = new GenericEvent($objectKey, $args);

        $this->getKrynCore()->getEventDispatcher()->dispatch('core/object/modify-pre', $eventPre);
        $this->getKrynCore()->getEventDispatcher()->dispatch('core/object/add-pre', $eventPre);

        if ($targetObjectKey && $targetObjectKey != $objectKey) {
            if ($position == 'prev' || $position == 'next') {
                throw new \InvalidArgumentException(
                    sprintf('Its not possible to use `prev` or `next` to add a new entry with a different object key. [target: %s, self: %s]',
                        $targetObjectKey, $objectKey)
                );
            }

            $targetPk = $this->normalizePk($targetObjectKey, $targetPk);

            //since propel's nested set behaviour only allows a single value as scope, we need to use the first pk
            $scope = current($targetPk);

            $result = $obj->add($values, null, $position, $scope);
        } else {
            $result = $obj->add($values, $targetPk, $position);
        }

        if (@$options['newsFeed']) {
            $this->getKrynCore()->getUtils()->newNewsFeed($objectKey, $values, 'added');
        }

        $args['result'] = $result;
        $event = new GenericEvent($objectKey, $args);

        $this->getKrynCore()->getEventDispatcher()->dispatch('core/object/modify', $event);
        $this->getKrynCore()->getEventDispatcher()->dispatch('core/object/add', $event);

        return $result;
    }

    /**
     * Updates a item per url.
     *
     * @param  string $objectUrl
     * @param  array  $values
     *
     * @return bool
     */
    public function updateFromUrl($objectUrl, $values)
    {
        list($objectKey, $objectIds, $params) = $this->parseUrl($objectUrl);

        return $this->update($objectKey, $objectIds[0], $values, $params);
    }

    /**
     * Updates a object entry. This means, all fields which are not defined will be saved as NULL.
     *
     * @param  string $objectKey
     * @param  mixed  $pk
     * @param  array  $values
     * @param  array  $options
     *
     * @return bool
     */
    public function update($objectKey, $pk, $values, $options = null)
    {
        $item = $this->get($objectKey, $pk, $options);

        if ($options['permissionCheck']) {

            if (!$item) {
                return false;
            }

            if (!$this->getKrynCore()->getACL()->checkUpdateExact($objectKey, $pk)) {
                return false;
            }

            foreach ($values as $fieldName => $value) {
                if (!$this->getKrynCore()->getACL()->checkUpdateExact($objectKey, $pk, [$fieldName => $value])) {
                    throw new \NoFieldWritePermission(sprintf("No update permission to field '%s' in item '%s' from object '%s'", $fieldName, $pk, $objectKey));
                }
            }
        }

        $objectKey = Objects::normalizeObjectKey($objectKey);
        $obj = $this->getClass($objectKey);
        $primaryKey = $obj->normalizePrimaryKey($pk);

        $args = [
            'pk' => $pk,
            'values' => &$values,
            'options' => &$options,
            'mode' => 'update'
        ];
        $eventPre = new GenericEvent($objectKey, $args);

        $this->getKrynCore()->getEventDispatcher()->dispatch('core/object/modify-pre', $eventPre);
        $this->getKrynCore()->getEventDispatcher()->dispatch('core/object/update-pre', $eventPre);

        $result = $obj->update($primaryKey, $values);

        if (@$options['newsFeed']) {
            $this->getKrynCore()->getUtils()->newNewsFeed($objectKey, $item, 'updated');
        }

        $args['result'] = $result;
        $event = new GenericEvent($objectKey, $args);

        $this->getKrynCore()->getEventDispatcher()->dispatch('core/object/modify', $event);
        $this->getKrynCore()->getEventDispatcher()->dispatch('core/object/update', $event);

        return $result;
    }

    /**
     * Patches a object entry. This means, only defined fields will be saved. Fields which are not defined will
     * not be overwritten.
     *
     * @param  string $objectKey
     * @param  mixed  $pk
     * @param  array  $values
     * @param  array  $options
     *
     * @return bool
     */
    public function patch($objectKey, $pk, $values, $options = null)
    {
        $objectKey = Objects::normalizeObjectKey($objectKey);
        $obj = $this->getClass($objectKey);
        $pk = $obj->normalizePrimaryKey($pk);

        $item = $this->get($objectKey, $pk, $options);

        if ($options['permissionCheck']) {
            if (!$item) {
                return false;
            }

            foreach ($values as $fieldName => $value) {
                if (!$this->getKrynCore()->getACL()->checkUpdateExact($objectKey, $pk, [$fieldName => $value])) {
                    throw new AccessDeniedException(sprintf("No update permission to field '%s' in item '%s' from object '%s'", $fieldName, $pk, $objectKey));
                }
            }
        }

        $args = [
            'pk' => $pk,
            'values' => &$values,
            'options' => &$options,
            'mode' => 'update'
        ];
        $eventPre = new GenericEvent($objectKey, $args);

        $this->getKrynCore()->getEventDispatcher()->dispatch('core/object/modify-pre', $eventPre);
        $this->getKrynCore()->getEventDispatcher()->dispatch('core/object/patch-pre', $eventPre);

        $result = $obj->patch($pk, $values);

        if (@$options['newsFeed']) {
            $this->getKrynCore()->getUtils()->newNewsFeed($objectKey, $item, 'updated');
        }

        $args['result'] = $result;
        $event = new GenericEvent($objectKey, $args);

        $this->getKrynCore()->getEventDispatcher()->dispatch('core/object/modify', $event);
        $this->getKrynCore()->getEventDispatcher()->dispatch('core/object/patch', $event);

        return $result;
    }


    /**
     * Removes a object item per url.
     *
     * @param  string $objectUrl
     *
     * @return bool
     */
    public function removeFromUrl($objectUrl, $options)
    {
        list($objectKey, $objectIds, ) = $this->parseUrl($objectUrl);

        return $this->remove($objectKey, $objectIds[0], $options);
    }

    /**
     * Removes a object item.
     *
     * @param  string $objectKey
     * @param  mixed  $pk
     * @param  array  $options
     *
     * @return boolean
     */
    public function remove($objectKey, $pk, $options)
    {
        $objectKey = Objects::normalizeObjectKey($objectKey);
        $obj = $this->getClass($objectKey);
        $primaryKey = $obj->normalizePrimaryKey($pk);

        $item = $this->get($objectKey, $pk, $options);

        if (!$item) {
            return false;
        }

        $args = [
            'pk' => $pk,
            'mode' => 'remove'
        ];
        $eventPre = new GenericEvent($objectKey, $args);

        $this->getKrynCore()->getEventDispatcher()->dispatch('core/object/modify-pre', $eventPre);
        $this->getKrynCore()->getEventDispatcher()->dispatch('core/object/remove-pre', $eventPre);

        $result = $obj->remove($primaryKey);

        $args['result'] = $result;
        $event = new GenericEvent($objectKey, $args);

        if (@$options['newsFeed']) {
            $this->getKrynCore()->getUtils()->newNewsFeed($objectKey, $item, 'removed');
        }

        $this->getKrynCore()->getEventDispatcher()->dispatch('core/object/modify', $event);
        $this->getKrynCore()->getEventDispatcher()->dispatch('core/object/remove', $event);

        return $result;
    }

    /*
    public function removeUsages($pObjectUrl)
    {
    }

    public function removeUsage($pObjectUrl, $pUseObjectId)
    {
    }

    public function addUsage($pObjectUrl, $pUseObjectId)
    {
    }
    */


    /**
     * Returns a single root item. Only for nested objects.
     *
     * @param  string $objectKey
     * @param  mixed  $scope
     * @param  bool   $options
     *
     * @return array
     * @throws \Exception
     */
    public function getRoot($objectKey, $scope, $options = false)
    {
        $definition = $this->getDefinition($objectKey);

        if ($definition->getNestedRootAsObject() && $scope === null) {
            throw new \Exception('No `scope` defined.');
        }

        $options['fields'] = $definition->getNestedRootObjectLabelField();

        return $this->get($definition->getNestedRootObject(), $scope, $options);
    }

    /**
     * Returns all roots. Only for nested objects.
     *
     * @param  string  $objectKey
     * @param  array   $condition
     * @param  array   $options
     *
     * @return array
     * @throws \Exception
     */
    public function getRoots($objectKey, $condition = null, $options = null)
    {
        $definition = $this->getDefinition($objectKey);

        if (!$definition->isNested()) {
            throw new \Exception('Object is not a nested set.');
        }

        if ($definition->getNestedRootObjectLabelField() && !@$options['fields']) {
            $options['fields'] = $definition->getNestedRootObjectLabelField();
        }

        if ($definition->getNestedRootAsObject()) {
            return $this->getList($definition->getNestedRootObject(), null, $options);
        } else {
            $obj = $this->getClass($objectKey);

            $conditionObject = new Condition(null, $this->getKrynCore());

            if ($condition) {
                $conditionObject->fromPk($condition, $objectKey);
            }

            if ($options['permissionCheck'] && $aclCondition = $this->getKrynCore()->getACL()->getListingCondition($objectKey)) {
                $conditionObject->mergeAndBegin($aclCondition);
            }

            return $obj->getRoots($conditionObject, $options);

        }
    }

    /**
     * @static
     *
     * @param        $objectKey
     * @param  mixed $pk
     * @param  array $condition
     * @param  int   $depth
     * @param  mixed $scope
     * @param  array $options
     *
     * @return mixed
     * @throws \Exception
     */
    public function getBranch(
        $objectKey,
        $pk = null,
        $condition = null,
        $depth = 1,
        $scope = null,
        $options = false
    ) {
        $obj = $this->getClass($objectKey);
        $definition = $this->getDefinition($objectKey);

        if (null !== $pk) {
            $pk = $obj->normalizePrimaryKey($pk);
        }

        if (null === $pk && $definition->getNestedRootAsObject() && $scope === null) {
            throw new \Exception('No scope defined.');
        }

        if (!@$options['fields']) {

            $fields = array();
            if ($rootField = $definition->getNestedRootObjectLabelField()) {
                $fields[] = $rootField;
            }

            if ($extraFields = $definition->getNestedRootObjectExtraFields()) {
                $extraFields = explode(',', trim(str_replace(' ', '', $extraFields)));
                foreach ($extraFields as $field) {
                    $fields[] = $field;
                }
            }
            $options['fields'] = implode(',', $fields);
        }

        $conditionObject = new Condition(null, $this->getKrynCore());

        if ($condition) {
            $conditionObject->fromPk($condition, $objectKey);
        }

        if ($limit = $obj->getDefinition()->getLimitDataSets()) {
            $conditionObject->mergeAnd($limit);
        }

        if (@$options['permissionCheck'] && $aclCondition = $this->getKrynCore()->getACL()->getListingCondition($objectKey)) {
            $conditionObject->mergeAndBegin($aclCondition);
        }

        return $obj->getBranch($pk, $conditionObject, $depth, $scope, $options);

    }

    /**
     * Returns a hash of all primary fields.
     *
     * Returns array('<keyOne>' => <arrayDefinition>, '<keyTwo>' => <arrayDefinition>, ...)
     *
     * @static
     *
     * @param $objectId
     *
     * @return array
     */
    public function getPrimaries($objectId)
    {
        $objectDefinition = $this->getDefinition($objectId);

        $primaryFields = array();
        foreach ($objectDefinition->getFields() as $field) {
            if ($field->isPrimaryKey()) {
                $primaryFields[$field->getId()] = $field;
            }
        }

        return $primaryFields;
    }

    /**
     * Return a list of all primary keys.
     *
     * Returns array('<keyOne>', '<keyTwo>', ...);
     *
     * @static
     *
     * @param $objectId
     *
     * @return array
     */
    public function getPrimaryList($objectId)
    {
        $objectDefinition = $this->getDefinition($objectId);

        $primaryFields = array();
        foreach ($objectDefinition->getFields() as $fieldKey => $field) {
            if ($field->getPrimaryKey()) {
                $primaryFields[] = $fieldKey;
            }
        }

        return $primaryFields;
    }

    /**
     * Returns the parent pk.
     *
     * @param  string $objectKey
     * @param  mixed  $pk
     *
     * @return array
     */
    public function getParentPk($objectKey, $pk)
    {
        $obj = $this->getClass($objectKey);
        $pk2 = $obj->normalizePrimaryKey($pk);

        return $obj->getParentId($pk2);
    }

    /**
     * Returns the parent pk from a url.
     *
     * @param  string $objectUrl
     *
     * @return array
     */
    public function getParentPkFromUrl($objectUrl)
    {
        list($objectKey, $objectIds, ) = $this->parseUrl($objectUrl);

        return $this->getParentPk($objectKey, $objectIds[0]);
    }

    /**
     * Returns the parent item per url. Only if the object is nested.
     *
     * @param  string $objectKey
     * @param  mixed  $pk
     * @param  null   $options
     *
     * @return mixed
     */
    public function getParent($objectKey, $pk, $options = null)
    {
        $obj = $this->getClass($objectKey);
        $pk2 = $obj->normalizePrimaryKey($pk);

        return $obj->getParent($pk2, $options);
    }

    /**
     * Returns the parent item. Only if the object is nested.
     *
     * @param  string $objectUrl
     *
     * @return array
     */
    public function getParentFromUrl($objectUrl)
    {
        list($objectKey, $objectIds, ) = $this->parseUrl($objectUrl);

        return $this->getParent($objectKey, $objectIds[0]);
    }

    /**
     * @param  string $objectUrl
     * @param  array  $options
     *
     * @return array
     */
    public function getVersionsFromUrl($objectUrl, $options = null)
    {
        list($objectKey, $objectId) = Objects::parseUrl($objectUrl);

        return $this->getVersions($objectKey, $objectId[0], $options);
    }

    /**
     * @param  string $objectKey
     * @param  mixed  $pk
     * @param  array  $options
     *
     * @return array
     */
    public function getVersions($objectKey, $pk, $options = null)
    {
        $obj = $this->getClass($objectKey);
        $pk2 = $obj->normalizePrimaryKey($pk);

        return $obj->getVersions($pk2, $options);
    }

    /**
     * Returns always a array with primary key and value pairs from a single pk.
     *
     * $pk can be
     *  - 24
     *  - array(24)
     *  - array('id' => 24)
     *
     * result:
     *  array(
     *    'id' => 24
     * );
     *
     * @param  string $objectKey
     * @param  mixed  $pk
     *
     * @return array  A single primary key as array. Example: array('id' => 1).
     */
    public function normalizePk($objectKey, $pk)
    {
        $obj = $this->getClass($objectKey);

        return $obj->normalizePrimaryKey($pk);
    }

    public static function normalizeObjectKey($key)
    {
        $key = str_replace(['\\', ':', '.'], '/', $key);

        if (false === strpos($key, '/')) {
            return preg_replace('/bundle$/', '', strtolower($key));
        }

        list($bundleName, $objectName) = explode('/', $key);
        $bundleName = preg_replace('/bundle$/', '', strtolower($bundleName));
        $objectName = lcfirst($objectName);
        return $bundleName. '/' . $objectName;
    }

    /**
     * Parses a whole (can be multiple) primary key that is a represented as string and returns the first primary key.
     *
     * Example:
     *
     *  1/2/3 => array( array(id =>1),array(id =>2),array(id =>3) )
     *  1 => array(array(id => 1))
     *  idFooBar => array( id => "idFooBar")
     *  idFoo/Bar => array(array(id => idFoo), array(id2 => "Bar"))
     *  1,45/2,45 => array(array(id => 1, pid = 45), array(id => 2, pid=>45))
     *
     * @param $objectKey
     * @param $pkString
     *
     * @return array Example array('id' => 4)
     */
    public function normalizePkString($objectKey, $pkString)
    {
        if (is_array($pkString)) {
            return $pkString;
        }

        $obj = $this->getClass($objectKey);
        $objectIds = $obj->primaryStringToArray($pkString);

        return @$objectIds[0];
    }

    /**
     * Returns all parents, incl. the root object (if root is an object, it returns this object entry as well)
     *
     * @param  string $objectKey
     * @param  mixed  $pk
     * @param  array  $options
     *
     * @return mixed
     */
    public function getParents($objectKey, $pk, $options = null)
    {
        $obj = $this->getClass($objectKey);
        $pk2 = $obj->normalizePrimaryKey($pk);

        return $obj->getParents($pk2, $options);
    }

    /**
     * Returns all parents per url, incl. the root object (if root is an object, it returns this object entry as well)
     *
     * @param  string $objectUrl
     *
     * @return mixed
     */
    public function getParentsFromUrl($objectUrl)
    {
        list($objectKey, $objectIds, ) = $this->parseUrl($objectUrl);

        return $this->getParents($objectKey, $objectIds[0]);
    }

    /**
     * Moves a item to a new position.
     *
     * @param  string $objectKey
     * @param  array  $pk
     * @param  array  $targetPk
     * @param  string $position        `first` (child), `last` (last child), `prev` (sibling), `next` (sibling)
     * @param  string $targetObjectKey
     * @param  array  $options
     * @param  bool   $overwrite
     *
     * @return mixed
     */
    public function move(
        $objectKey,
        $pk,
        $targetPk,
        $position = 'first',
        $targetObjectKey = null,
        $options,
        $overwrite = false
    ) {
        $obj = $this->getClass($objectKey);

        $pk2 = $obj->normalizePrimaryKey($pk);
        $targetPk = $this->normalizePk($targetObjectKey ? $targetObjectKey : $objectKey, $targetPk);

        //todo check access

        if (@$options['newsFeed']) {
            $item = $this->get($objectKey, $pk);
            $this->getKrynCore()->getUtils()->newNewsFeed($objectKey, $item, 'moved');
        }

        return $obj->move($pk2, $targetPk, $position, $targetObjectKey, $overwrite);
    }

    /**
     * Moves a item around by a url.
     *
     * @param  string $sourceObjectUrl
     * @param  string $targetObjectUrl
     * @param  string $position
     * @param  array  $options
     *
     * @return mixed
     */
    public function moveFromUrl($sourceObjectUrl, $targetObjectUrl, $position = 'first', $options = null)
    {
        list($objectKey, $objectIds, ) = $this->parseUrl($sourceObjectUrl);
        list($targetObjectKey, $targetObjectIds, ) = $this->parseUrl($targetObjectUrl);

        return $this->move($objectKey, $objectIds[0], $targetObjectIds[0], $targetObjectKey, $position, $options);
    }

    /**
     * Checks whether the conditions in $condition are complied with the given object url.
     *
     * @param  string $objectUrl
     * @param  array  $condition
     *
     * @return bool
     */
    public function satisfyFromUrl($objectUrl, $condition)
    {
        $object = $this->getFromUrl($objectUrl);

        return $this->satisfy($object, $condition);

    }

    /**
     * Checks whether the conditions in $condition are complied with the given object item.
     *
     * @static
     *
     * @param array $objectItem
     * @param \Kryn\CmsBundle\Configuration\Condition|array $condition
     * @param string $objectKey
     *
     * @return bool
     */
    public function satisfy(&$objectItem, $condition, $objectKey = null)
    {
        if (is_array($condition)) {
            return Condition::create($condition)->satisfy($objectItem, $objectKey);
        } else if($condition instanceof Condition) {
            return $condition->satisfy($objectItem, $objectKey);
        }
    }

    /**
     * Returns the public URL.
     *
     * @param  string $objectKey
     * @param  string $pk
     * @param  array  $pluginProperties
     *
     * @return string
     */
    public function getPublicUrl($objectKey, $pk, $pluginProperties = null)
    {
        $definition = $this->getDefinition($objectKey);

        if ($definition && $callable = $definition->getPublicUrlGenerator()) {
            $pk = $this->normalizePkString($objectKey, $pk);

            return call_user_func_array($callable, array($objectKey, $pk, $pluginProperties));
        }

        return null;

    }

}
