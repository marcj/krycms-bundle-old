<?php

namespace Kryn\CmsBundle\Configuration;

use Kryn\CmsBundle\Core;
use Kryn\CmsBundle\Exceptions\BundleNotFoundException;
use Kryn\CmsBundle\Objects;

class Configs implements \IteratorAggregate
{
    /**
     * @var Bundle[]
     */
    private $configElements = array();

    /**
     * @var Core
     */
    protected $core;

    protected $triggeredReboot = [];

    /**
     * @param Core $core
     * @param array $bundles
     */
    public function __construct(Core $core, array $bundles = null)
    {
        $this->setCore($core);
        if ($bundles) {
            foreach ($bundles as $bundleName) {
                $configs = $this->getXmlConfigsForBundle($bundleName);
                $this->configElements = array_merge($this->configElements, $configs);
            }

            $this->configElements = $this->parseConfig($this->configElements);
        }
    }

    /**
     * @param \Kryn\CmsBundle\Core $core
     */
    public function setCore($core)
    {
        $this->core = $core;
    }

    /**
     * @return \Kryn\CmsBundle\Core
     */
    public function getCore()
    {
        return $this->core;
    }

    /**
     * Returns a md5 hash of all kryn config files (Resources/config/kryn.*.xml).
     *
     * @param string $bundleName
     * @return string
     */
    public function getConfigHash($bundleName)
    {
        $hash = [];
        foreach ($this->getConfigFiles($bundleName) as $file) {
            $hash[] = filemtime($file);
        }

        return md5(implode('.', $hash));
    }

    /**
     * @param string $bundleName
     * @return string[]
     */
    public function getConfigFiles($bundleName)
    {
        try {
            $configDir = $this->getCore()->getKernel()->locateResource("@$bundleName/Resources/config/");
        } catch (\InvalidArgumentException $e) {
            return [];
        }
        $baseFile = $configDir . 'kryn.xml';

        $files = [];
        if (file_exists($baseFile)) {
            $files = [$configDir . 'kryn.xml'];
        }

        if (file_exists($configDir)) {
            $files = array_merge($files, glob($configDir . 'kryn.*.xml'));
        }

        return $files;
    }

    /**
     * Returns a array with following structure:
     *
     *   array[$bundleName][$priority][$file] = $bundle
     *
     * @param string $bundleName
     * @return array
     */
    public function getXmlConfigsForBundle($bundleName)
    {
        $configs = array();
        $rename = [];
        foreach ($this->getConfigFiles($bundleName) as $file) {
            if (file_exists($file) && file_get_contents($file)) {
                $doc = new \DOMDocument();
                $doc->load($file);

                $rename[strtolower($bundleName)] = $bundleName;

                $bundles = $doc->getElementsByTagName('bundle');
                foreach ($bundles as $bundle) {
                    if ($bundle->attributes->getNamedItem('name')) {
                        $bundleName = $bundle->attributes->getNamedItem('name')->nodeValue;
                    }
                    $priority = 0;
                    if ($bundle->attributes->getNamedItem('priority')) {
                        $priority = (int)$bundle->attributes->getNamedItem('priority')->nodeValue;
                    }

                    $configs[strtolower($bundleName)][$priority][$file] = $bundle;
                }
            }
        }

        foreach ($configs as $key => $val) {
            if (isset($rename[$key]) && $rename[$key] !== $key) {
                $configs[$rename[$key]] = $val;
                unset($configs[$key]);
            }
        }

        return $configs;
    }

//    /**
//     * Handles the <modify> element.
//     * Calls on each config object the setup method.
//     */
//    public function setup()
//    {
//        foreach ($this->configElements as $config) {
//
//            //todo, handle modify tag.
//            $config->setup($this);
//        }
//    }

    /**
     * @return bool
     */
    public function boot()
    {
        $changed = false;
        foreach ($this->configElements as $key => $config) {
            if ($boots = $config->boot($this)) {
                $changed = true;
                $count = (isset($this->triggeredReboot[$key]) ? $this->triggeredReboot[$key]['count'] : 0) + 1;
                $this->triggeredReboot[$key] = [
                    'count' => $count,
                    'triggeredReboots' => $boots
                ];
            }
        }
        return $changed;
    }

    /**
     * @return array
     */
    public function getTriggeredReboots()
    {
        return $this->triggeredReboot;
    }

    /**
     * $configs = $configs[$bundleName][$priority][] = $bundleDomElement;
     *
     * @param array $configs
     *
     * @return \Kryn\CmsBundle\Configuration\Bundle[]
     */
    public function parseConfig(array $configs)
    {
        $bundleConfigs = array();
        foreach ($configs as $bundleName => $priorities) {
            ksort($priorities); //sort by priority

            foreach ($priorities as $configs) {
                foreach ($configs as $file => $bundleElement) {
                    if (!isset($bundleConfigs[strtolower($bundleName)])) {
                        $bundleConfigs[strtolower($bundleName)] = new Bundle($bundleName, null, $this->getCore());
                    }

                    $bundleConfigs[strtolower($bundleName)]->import($bundleElement, $file);
                }
            }

            if ($bundleConfigs[strtolower($bundleName)]) {
//                $bundleConfigs[strtolower($bundleName)]->setupObject($this->getCore());
            }
        }

        return $bundleConfigs;
    }

    /**
     * @param string $bundleName
     *
     * @return \Kryn\CmsBundle\Configuration\Bundle
     */
    public function getConfig($bundleName)
    {
        $bundleName = preg_replace('/bundle$/', '', strtolower($bundleName));
        $bundleName .= 'bundle';

        return isset($this->configElements[$bundleName]) ? $this->configElements[$bundleName] : null;
    }

    /**
     * @param string $objectKey
     * @return \Kryn\CmsBundle\Configuration\Object
     */
    public function getObject($objectKey)
    {
        list($bundleName, $objectName) = explode('/', Objects::normalizeObjectKey($objectKey));

        $bundleName .= 'bundle';

        if (!$config = $this->getConfig($bundleName)) {
            throw new BundleNotFoundException(sprintf('Bundle `%s` not found. [%s]', $bundleName, $objectKey));
        }

        return $config->getObject($objectName);
    }

    /**
     * @return Bundle[]
     */
    public function getConfigs()
    {
        return $this->configElements;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $result = array();
        foreach ($this->configElements as $config) {
            $value = $config->toArray();
            $value['composer'] = $config->getComposer() ? : [];
            $result[$config->getBundleName()] = $value;
        }

        return $result;
    }

    /**
     * @return \Kryn\CmsBundle\Configuration\Bundle[]
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->configElements);
    }

    public function __sleep()
    {
        return ['configElements'];
    }

}
