<?php

namespace Kryn\CmsBundle\ORM;

use Kryn\CmsBundle\Configuration\Condition;
use Kryn\CmsBundle\Tools;

class ObjectView extends Propel
{
    /**
     * {@inheritDoc}
     */
    public function getItem($pk, $options = null)
    {
        $path = $pk['path'];

        $file = $this->getKrynCore()->resolvePath($path, 'Resources/views/', true);
        $fs = $this->getKrynCore()->getFileSystem();
        $fileObj = $fs->getFile($file);

        return $fileObj->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function getItems(Condition $condition = null, $options = null)
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

        $path = '@' . trim($path, '/@');
        $path = str_replace(':', '/', $path);

        $c = 0;
        $offset = $options['offset'];
        $limit = $options['limit'];
        $result = array();

        if (!$path) {

            $result = array();
            $bundles = array_keys($this->getKrynCore()->getBundles());
            foreach ($bundles as $bundleName) {
                $directory = $this->getKrynCore()->resolvePath('@' . $bundleName, 'Resources/views', true);
                $file = $this->getKrynCore()->getFilesystem()->getFile($directory);
                if (!$file) {
                    continue;
                }
                $file['name'] = $bundleName;
                $file['path'] = $bundleName;
                if ($offset && $offset > $c) {
                    continue;
                }
                if ($limit && $limit < $c) {
                    continue;
                }
                if ($condition && !$condition->satisfy($file)) {
                    continue;
                }
                $c++;

                if ($depth > 0) {
                    $children = self::getBranch(array('path' => $bundleName), $condition, $depth - 1);
                    $file['_childrenCount'] = count($children);
                    if ($depth > 1 && $file['type'] == 'dir') {
                        $file['_children'] = $children;
                    }
                }
            }
        } else {

            $directory = $this->getKrynCore()->resolvePath($path, 'Resources/views', true) . '/';
            try {
                $files = $this->getKrynCore()->getFilesystem()->getFiles($directory);
            } catch(\Exception $e) {
                throw new \Exception(sprintf('Can not find `%s` (in %s)', $path, $directory));
            }

            foreach ($files as $file) {
                if ($condition && $condition->hasRules() && !$condition->satisfy($file, 'KrynCmsBundle:file')) {
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
                    'path' => $this->buildPath($path . '/' . Tools::getRelativePath($item['path'], $directory))
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

    public function buildPath($path)
    {
        if ('@' === substr($path, 0, 1)) {
            $path = substr($path, 1);
        }

        $path = str_replace('//', '/', $path);
        $path = preg_replace('/\/|:/', ':', $path, 2);

        if (1 === substr_count($path, ':')) {
            $path = str_replace(':', '::', $path);
        }

        return $path;
    }

}
