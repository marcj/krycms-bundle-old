<?php

namespace Admin\Models;

use Core\Config\Condition;
use Core\Kryn;
use Core\SystemFile;

class ObjectView extends \Core\ORM\Propel
{
    /**
     * {@inheritDoc}
     */
    public function getItem($pk, $options = null)
    {
        $path = $pk['path'];

        $file = Kryn::resolvePath($path, 'Resources/views/');
        $fileObj = SystemFile::getFile($file);

        return $fileObj->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function getItems(\Core\Config\Condition $condition = null, $options = null)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function remove($primaryKey)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function add($values, $branchPk = null, $mode = 'into', $scope = null)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function update($primaryKey, $values)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function patch($primaryKey, $values)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getCount(Condition $condition = null)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function clear()
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getPrimaryKeys()
    {
        return parent::getPrimaryKeys();
    }

    /**
     * {@inheritDoc}
     */
    public static function normalizePath(&$path)
    {
        $path = str_replace('.', '/', $path); //debug

        if (substr($path, -1) == '/') {
            $path = substr($path, 0, -1);
        }

    }

    /**
     * {@inheritDoc}
     */
    public function getBranch($pk = null, Condition $condition = null, $depth = 1, $scope = null, $options = null)
    {
        $result = null;

        $path = $pk['path'];
        if ($depth === null) {
            $depth = 1;
        }

        if (substr($path, -1) !== '/') {
            $path .= '/';
        }

        $c = 0;
        $offset = $options['offset'];
        $limit = $options['limit'];
        $result = array();

        if (!$path) {

            $result = array();
            foreach (\Core\Kryn::getBundles() as $extension) {
                $directory = Kryn::resolvePath('@' . $extension, 'Resources/views');
                $file = SystemFile::getFile($directory);
                if (!$file) {
                    continue;
                }
                $file['name'] = $extension;
                $file['path'] = '@' . $extension;
                if ($offset && $offset > $c) {
                    continue;
                }
                if ($limit && $limit < $c) {
                    continue;
                }
                if ($condition && !\Core\Object::satisfy($file, $condition)) {
                    continue;
                }
                $c++;

                if ($depth > 0) {
                    $children = self::getBranch(array('path' => $extension), $condition, $depth - 1);
                    $file['_childrenCount'] = count($children);
                    if ($depth > 1 && $file['type'] == 'dir') {
                        $file['_children'] = $children;
                    }
                }
            }
        } else {
            $bundle = Kryn::getBundle($path);

            $directory = Kryn::resolvePath($path, 'Resources/views');
            $files = SystemFile::getFiles($directory);

            foreach ($files as $file) {
                if ($condition && $condition->hasRules() && !$condition->satisfy($file, 'core:file')) {
                    continue;
                }

                $c++;
                if ($offset && $offset >= $c) {
                    continue;
                }
                if ($limit && $limit < $c) {
                    continue;
                }

                $item = $file->toArray();

                $item = array(
                    'name' => $item['name'],
                    'path' => '@' . $bundle->getName() . substr($item['path'], strlen($directory))
                );

                if ($file->isDir()) {
                    $children = self::getBranch(array('path' => $item['path']), $condition, $depth - 1);
                    foreach ($children as $child) {
                        $child['name'] = $item['name'] . '/' . $child['name'];
                        $result[] = $child;
                    }
                }


                if ($file->isFile()) {
                    $result[] = $item;
                }
            }
        }

        return $result;
    }

}
