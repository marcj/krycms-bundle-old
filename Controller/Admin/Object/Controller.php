<?php

namespace Kryn\CmsBundle\Controller\Admin\Object;

use Core\Kryn;
use Core\Object;

/**
 * Controller
 *
 * Proxy class for \Core\Object
 */
class Controller
{
    /**
     * General object items output. /admin/object?uri=...
     *
     * @param  string                   $url
     * @param  string                   $fields
     *
     * @return array|bool
     * @throws \ObjectNotFoundException
     */
    public function getItemPerUrl($url, $fields = null)
    {
        list($objectKey, $object_id) = \Core\Object::parseUrl($url);

        $definition = \Core\Object::getDefinition($objectKey);
        if (!$definition) {
            throw new \ObjectNotFoundException(tf('Object %s does not exists.', $objectKey));
        }
        return \Core\Object::get($objectKey, $object_id[0], array('fields' => $fields, 'permissionCheck' => true));
    }

    /**
     * Object items output for user interface field.  /admin/backend/field-object?uri=...
     *
     * @param  string                   $pUrl
     * @param  string                   $fields
     *
     * @return array|bool
     * @throws \ObjectNotFoundException
     * @throws \ClassNotFoundException
     */
    public function getFieldItem($objectKey, $pk, $fields = null)
    {
        $definition = \Core\Object::getDefinition($objectKey);
        if (!$definition) {
            throw new \ObjectNotFoundException(tf('Object %s does not exists.', $objectKey));
        }

        if ($definition['chooserFieldDataModel'] != 'custom') {
            return \Core\Object::get($objectKey, $pk);
        } else {

            $class = $definition['chooserFieldDataModelClass'];
            if (!class_exists($class)) {
                throw new \ClassNotFoundException(tf('Class %s can not be found.', $class));
            }
            $dataModel = new $class($objectKey);

            return $dataModel->getItem($pk, array('fields' => $fields, 'permissionCheck' => true));
        }
    }

    /**
     * General object items output. /admin/objects?uri=...
     *
     * @param  string                   $url
     * @param  string                   $fields
     * @param  bool                     $returnKey             Returns the list as a hash with the primary key as index. key=implode(',',urlencode($keys))
     * @param  bool                     $returnKeyAsRequested  . Returns the list as a hash with the requested id as key.
     *
     * @return array
     * @throws \Exception
     * @throws \ClassNotFoundException
     * @throws \ObjectNotFoundException
     */
    public function getItemsByUrl($url, $fields = null, $returnKey = true, $returnKeyAsRequested = false)
    {
        list($objectKey, $objectIds, $params) = \Core\Object::parseUrl($url);
        //check if we got an id
        if ($objectIds[0] === '') {
            throw new \Exception(tf('No id given in uri %s.', $url));
        }

        $definition = \Core\Object::getDefinition($objectKey);
        if (!$definition) {
            throw new \ObjectNotFoundException(tf('Object %s can not be found.', $objectKey));
        }

        $options['fields'] = $fields;
        $options['permissionCheck'] = true;

        $items = array();
        if (count($objectIds) == 1) {
            $items[] = \Core\Object::get($objectKey, $objectIds[0], $options);
        } else {
            foreach ($objectIds as $primaryKey) {
                if ($item = \Core\Object::get($objectKey, $primaryKey, $options)) {
                    $items[] = $item;
                }
            }
        }

        if ($returnKey || $returnKeyAsRequested) {

            $res = array();
            if ($returnKeyAsRequested) {

                //map requetsed id to real ids
                $requestedIds = explode('/', \Core\Object::getCroppedObjectId($url));
                $map = array();
                foreach ($requestedIds as $id) {
                    $pk = \Core\Object::parsePk($objectKey, $id);
                    $map[\Core\Object::getObjectUrlId($objectKey, $pk[0]) + ''] = $id;
                }

                if (is_array($items)) {
                    foreach ($items as &$item) {
                        $pk = \Core\Object::getObjectUrlId($objectKey, $item);
                        $res[$map[$pk + '']] = $item;
                    }
                }

            } else {
                $primaryKeys = \Core\Object::getPrimaries($objectKey);

                $c = count($primaryKeys);
                $firstPK = key($primaryKeys);

                if (is_array($items)) {
                    foreach ($items as &$item) {

                        if ($c > 1) {
                            $keys = array();
                            foreach ($primaryKeys as $key => &$field) {
                                $keys[] = Kryn::urlEncode($item[$key]);
                            }
                            $res[implode(',', $keys)] = $item;
                        } else {
                            $res[$item[$firstPK]] = $item;
                        }
                    }
                }
            }

            return $res;
        } else {
            return $items;
        }
    }

    /**
     * Object items output for user interface field. /admin/backend/field-objects?uri=...
     *
     *
     * This method does check against object property 'chooserFieldDataModelClass'. If set, we use
     * this class to get the items.
     *
     * @param string $objectKey
     * @param string $fields
     * @param bool   $returnHash Returns the list as a hash with the primary key as index.
     * @param int    $limit
     * @param int    $offset
     * @param array  $order
     * @param mixed  $_
     *
     * @return array
     * @throws \Exception
     * @throws \ClassNotFoundException
     * @throws \ObjectNotFoundException
     */
    public function getFieldItems(
        $objectKey,
        $fields = null,
        $returnHash = true,
        $limit = null,
        $offset = null,
        $order = null,
        $_ = null
    ) {

        $definition = \Core\Object::getDefinition($objectKey);
        if (!$definition) {
            throw new \ObjectNotFoundException(tf('Object %s can not be found.', $objectKey));
        }

        $options = array(
            'permissionCheck' => true,
            'fields' => $fields,
            'limit' => $limit,
            'offset' => $offset,
            'order' => $order
        );

        $condition = \Admin\ObjectCrud::buildFilter($_);

        if ($definition['fieldDataModel'] == 'custom') {

            $class = $definition['fieldDataModelClass'];
            if (!class_exists($class)) {
                throw new \ClassNotFoundException(tf('The class %s can not be found.', $class));
            }

            $dataModel = new $class($objectKey);

            $items = $dataModel->getItems($condition, $options);

        } else {

            $items = \Core\Object::getList($objectKey, $condition, $options);

        }

        if ($returnHash) {
            $primaryKeys = \Core\Object::getPrimaries($objectKey);

            $c = count($primaryKeys);
            $firstPK = key($primaryKeys);

            $res = array();
            if (is_array($items)) {
                foreach ($items as &$item) {

                    if ($c > 1) {
                        $keys = array();
                        foreach ($primaryKeys as $key => &$field) {
                            $keys[] = Kryn::urlEncode($item[$key]);
                        }
                        $res[implode(',', $keys)] = $item;
                    } else {
                        $res[$item[$firstPK]] = $item;
                    }
                }
            }

            return $res;
        } else {
            return $items;
        }
    }

    /**
     * Object items output for user interface chooser window/browser. /admin/backend/browser-objects/<objectKey>
     *
     * This method does check against object property 'browserDataModel'. If custom, we use
     * this class to get the items.
     *
     * @param string $objectKey
     * @param string $fields
     * @param bool   $returnHash Returns the list as a hash with the primary key as index.
     *
     * @param int    $limit
     * @param int    $offset
     * @param array  $order
     * @param mixed  $_
     *
     * @return array
     * @throws \ObjectNotFoundException
     * @throws \ClassNotFoundException
     * @throws \ObjectMisconfiguration
     */
    public function getBrowserItems(
        $objectKey,
        $fields = null,
        $returnHash = false,
        $limit = null,
        $offset = null,
        $order = null,
        $_ = null
    ) {

        $definition = \Core\Object::getDefinition($objectKey);
        if (!$definition) {
            throw new \ObjectNotFoundException(tf('Object %s can not be found.', $objectKey));
        }

        if (!$definition['browserColumns']) {
            throw new \ObjectMisconfiguration(tf('Object %s does not have browser columns.', $objectKey));
        }

        $fields2 = array_keys($definition['browserColumns']);

        $options = array(
            'permissionCheck' => true,
            'fields' => $fields2,
            'limit' => $limit,
            'offset' => $offset,
            'order' => $order
        );

        $condition = \Admin\ObjectCrud::buildFilter($_);

        if ($definition['browserDataModel'] == 'custom') {

            $class = $definition['browserDataModelClass'];
            if (!class_exists($class)) {
                throw new \ClassNotFoundException(tf('The class %s can not be found.', $class));
            }

            $dataModel = new $class($objectKey);

            $items = $dataModel->getItems($condition, $options);

        } else {

            $items = \Core\Object::getList($objectKey, $condition, $options);

        }

        if ($returnHash) {
            $primaryKeys = \Core\Object::getPrimaries($objectKey);

            $c = count($primaryKeys);
            $firstPK = key($primaryKeys);

            $res = array();
            if (is_array($items)) {
                foreach ($items as &$item) {

                    if ($c > 1) {
                        $keys = array();
                        foreach ($primaryKeys as $key => &$field) {
                            $keys[] = Kryn::urlEncode($item[$key]);
                        }
                        $res[implode(',', $keys)] = $item;
                    } else {
                        $res[$item[$firstPK]] = $item;
                    }
                }
            }

            return $res;
        } else {
            return $items;
        }
    }

    public function getBrowserItemsCount($objectKey, $_ = null)
    {
        $definition = \Core\Object::getDefinition($objectKey);
        if (!$definition) {
            throw new \ObjectNotFoundException(tf('Object %s can not be found.', $objectKey));
        }

        if (!$definition['browserColumns']) {
            throw new \ObjectMisconfiguration(tf('Object %s does not have browser columns.', $objectKey));
        }

        $fields = array_keys($definition['browserColumns']);

        $options = array(
            'permissionCheck' => true
        );

        $condition = \Admin\ObjectCrud::buildFilter($_);

        if ($definition['browserDataModel'] == 'custom') {

            $class = $definition['browserDataModelClass'];
            if (!class_exists($class)) {
                throw new \ClassNotFoundException(tf('The class %s can not be found.', $class));
            }

            $dataModel = new $class($objectKey);

            $count = $dataModel->getCount($condition, $options);

        } else {

            $count = \Core\Object::getCount($objectKey, $condition, $options);

        }

        return $count;
    }
}
