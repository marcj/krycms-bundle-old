<?php

namespace Kryn\CmsBundle\Configuration;

use Kryn\CmsBundle\Core;

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
        foreach ($this->getConfigFiles($bundleName) as $file) {
            if (file_exists($file)) {
                $doc = new \DOMDocument();
                $doc->load($file);

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

        return $configs;
    }

    /**
     * Handles the <modify> element.
     * Calls on each config object the setup method.
     */
    public function setup()
    {
        foreach ($this->configElements as $config) {

            //todo, handle modify tag.
            $config->setup($this);
        }
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
     * @return Config
     */
    public function getConfig($bundleName)
    {
        $bundleName = strtolower($bundleName);

        return isset($this->configElements[$bundleName]) ? $this->configElements[$bundleName] : null;
    }

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
            $result[strtolower($config->getBundleName())] = $value;
        }

        return $result;
    }

    /**
     * @return Config[]
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
