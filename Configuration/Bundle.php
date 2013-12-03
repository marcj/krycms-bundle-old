<?php

namespace Kryn\CmsBundle\Configuration;

use Kryn\CmsBundle\Objects;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class Bundle extends Model
{
    protected $attributes = ['id'];

    public static $propertyToFile = [
        'objects' => 'kryn.objects.xml',
        'entryPoints' => 'kryn.entryPoints.xml',
        'themes' => 'kryn.themes.xml',
        'plugins' => 'kryn.plugins.xml',
    ];

    protected $id;

    /**
     * @var
     */
    private $bundleClass;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var Plugin[]
     */
    protected $plugins;

    /**
     * @var Theme[]
     */
    protected $themes;

    /**
     * @var Object[]
     */
    protected $objects;

    /**
     * @var EntryPoint[]
     */
    protected $entryPoints;

    /**
     * @var Asset[]
     */
    protected $adminAssets;

    /**
     * @var string
     */
    private $bundleName;

    /**
     * @var string
     */
    protected $version;

    /**
     * @var BundleCache[]
     */
    protected $caches;

    /**
     * @var Event[]
     */
    protected $events;

    /**
     * @var Event[]
     */
    protected $listeners;

    /**
     * @var Stream[]
     */
    protected $streams;

    /**
     * @var array
     */
    private $imported = [];

    /**
     * @param string $bundleName
     * @param \DOMElement $bundleDoc
     */
    public function __construct($bundleName = '', \DOMElement $bundleDoc = null, $krynCore = null)
    {
        $this->element = $bundleDoc;
        $this->bundleName = $bundleName;
        $this->rootName = 'bundle';
        $this->setKrynCore($krynCore);
    }

    /**
     *  All bundle configs have been loaded. Do whatever is needed with it.
     */
    public function setup(Configs $configs)
    {
        if ($this->getObjects()) {
            foreach ($this->getObjects() as $object) {
                $object->syncRelations();
            }
        }
    }

    /**
     * @param bool $printDefaults
     * @return array
     */
    public function toArray($printDefaults = false)
    {
        $value['name'] = $this->getBundleName();
        $value['class'] = get_class($this->getBundleClass());
        $value = array_merge($value, parent::toArray($printDefaults));

        return $value;
    }


    /**
     * Returns the path to the composer.json
     *
     * @return string
     */
    public function getComposerPath()
    {


        return $this->getBundleClass()->getPath() . 'composer.json';
    }

    /**
     * Returns the composer configuration as array.
     *
     * @return array
     */
    public function getComposer()
    {
        $path = $this->getComposerPath();
        if (file_exists($file = $path)) {
            return json_decode(file_get_contents($file), true);
        }
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $path
     * @param bool $withDefaults
     *
     * @return bool
     *
     * @throws \FileNotWritableException
     */
    public function saveConfig($path, $withDefaults = false)
    {
        $xml = $this->toXml($withDefaults);
        $doc = new \DOMDocument();
        $doc->formatOutput = true;
        $doc->loadXML("<config>$xml</config>");
        $xml = trim(substr($doc->saveXML(), strlen('<?xml version="1.0"?>') + 1));
        if ((!file_exists($path) && !is_writable(dirname($path))) || (file_exists($path) && !is_writable($path))) {
            throw new \FileNotWritableException(tf('The file `%s` is not writable.', $path));
        }
        return SystemFile::setContent($path, $xml);
    }

    public function getPropertyFilePath($property)
    {
        return $this->imported[$property] ?: $this->getBundleClass()->getPath() . 'Resources/config/' . (static::$propertyToFile[$property] ?: 'kryn.xml');
    }

    /**
     * @param string $property
     * @return string
     */
    public function exportFileBased($property)
    {
        $xmlFile = $this->getPropertyFilePath($property);

        $doc = new \DOMDocument();
        $doc->formatOutput = true;
        $doc->preserveWhiteSpace = false;
        if (file_exists($xmlFile)) {
            $doc->load($xmlFile);
        } else {
            $configElement = $doc->createElement('config');
            $doc->appendChild($configElement);
            $bundleElement = $doc->createElement('bundle');
            $configElement->appendChild($bundleElement);
        }

        $xpath = new \DOMXPath($doc);

        $elements = $xpath->query('/config/bundle/' . $property);
        if (0 < $elements->length) {
            $element = $elements->item(0);
        }

        if ($element) {
            $newNode = $this->appendXmlProperty($property, $element->parentNode, false);

            if ($newNode instanceof \DOMElement) {
                $element->parentNode->replaceChild($newNode, $element);
            }
        } else {
            $elements = $xpath->query('/config/bundle');
            if (0 < $elements->length) {
                $element = $elements->item(0);
            }
            $this->appendXmlProperty($property, $element, false);
        }

        $xml = $doc->saveXML();
        $xml = substr($xml, strlen('<?xml version="1.0"?>') + 1);
        $xml = trim($xml);
        return $xml;
    }

    /**
     * @param $property
     * @return bool
     */
    public function saveFileBased($property) {
        $xml = $this->exportFileBased($property);
        $xmlFile = $this->getPropertyFilePath($property);

        $emptyXml = '<config>
  <bundle/>
</config>';

        if ($xml == $emptyXml) {
            if (SystemFile::exists($xmlFile)) {
                return SystemFile::remove($xmlFile);
            } else {
                return true;
            }
        } else {
            return SystemFile::setContent($xmlFile, $xml);
        }
    }


    /**
     * @param string $bundleName
     */
    public function setBundleName($bundleName)
    {
        $this->bundleName = $bundleName;
    }

    /**
     * Returns the bundle name with the 'Bundle' suffix.
     *
     * Example: `CoreBundle`.
     *
     * @return string
     */
    public function getBundleName()
    {
        return $this->bundleName;
    }

    /**
     * Returns the Bundle class object.
     *
     * @return BundleInterface
     */
    public function getBundleClass()
    {
        if (!$this->bundleClass) {
            $this->bundleClass = $this->getKrynCore()->getBundle($this->getBundleName());
        }
        return $this->bundleClass;
    }

    /**
     * @param BundleInterface $class
     */
    public function setBundleClass(BundleInterface $class)
    {
        $this->bundleClass = $class;
    }

    /**
     * Returns the bundle name without the 'Bundle' suffix.
     *
     * Example: `Core`.
     *
     * @return string
     */
    public function getName()
    {
        return $this->getKrynCore()->getShortBundleName($this->getBundleClass()->getName());
    }

    /**
     * @param \DOMNode $node
     * @param string $file
     */
    public function import(\DOMNode $node, $file = null)
    {
        if ('bundle' === $node->nodeName) {
            $imported = $this->importNode($node);

            foreach ($imported as $property) {
                $this->imported[$property] = $file;
            }
        }
    }

    /**
     * @return Plugin[]
     */
    public function getPlugins()
    {
        return $this->plugins;
    }

    /**
     * @return Plugin[]
     */
    public function getPluginsArray()
    {
        if ($this->plugins) {
            $plugins = [];
            foreach ($this->plugins as $plugin) {
                $plugins[$plugin->getId()] = $plugin->toArray();
            }
            return $plugins;
        }
    }

    /**
     * @return Theme[]
     */
    public function getThemesArray()
    {
        if ($this->themes) {
            $themes = [];
            foreach ($this->themes as $theme) {
                $themes[$theme->getId()] = $theme->toArray();
            }
            return $themes;
        }
    }

    /**
     * @param string $id
     *
     * @return Plugin
     */
    public function getPlugin($id)
    {
        if (null !== $this->plugins) {
            foreach ($this->plugins as $plugin) {
                if ($plugin->getId() == $id) {
                    return $plugin;
                }
            }
        }
    }

    /**
     * @param Plugin[] $plugins
     */
    public function setPlugins(array $plugins = null)
    {
        $this->plugins = $plugins;
    }


    /**
     * @param Stream[] $streams
     */
    public function setStreams(array $streams = null)
    {
        $this->streams = $streams;
    }

    /**
     * @return Stream[]
     */
    public function getStreams()
    {
        return $this->streams;
    }

    /**
     * @param string $filter
     * @param bool $regex
     *
     * @return Asset[]|Assets[]
     */
    public function getAdminAssets($filter = '', $regex = false)
    {
        if ('' === $filter) {
            return $this->adminAssets;
        } else {
            $result = array();
            if ($regex) {
                $filter = addcslashes($filter, '/');
            } else {
                $filter = preg_quote($filter, '/');
            }

            if (null !== $this->adminAssets) {
                foreach ($this->adminAssets as $asset) {
                    if (preg_match('/' . $filter . '/', $asset->getPath())) {
                        $result[] = $asset;
                    }
                }
            }
            return $result;
        }
    }

    /**
     * @param Asset[]|Assets[] $adminAssets
     */
    public function setAdminAssets(array $adminAssets = null)
    {
        $this->adminAssets = $adminAssets;
    }

    /**
     *
     * @param bool $localPath   Return the real local accessible path or the defined.
     * @param string $filter      a filter value
     * @param bool $regex       if you pass a own regex as $filter set this to true
     * @param bool $compression if true or false it returns only assets with this compression value. null returns all
     *
     * @return string[]
     */
    public function getAdminAssetsPaths($localPath = false, $filter = '', $regex = false, $compression = null)
    {
        $files = array();
        $method = $localPath ? 'getLocalPath' : 'getPath';
        foreach ($this->getAdminAssets($filter, $regex) as $asset) {
            if ($asset instanceof Asset) {
                if (null !== $compression && $compression !== $asset->getCompression()) {
                    continue;
                }
                $files[] = $asset->$method();
            } else if ($asset instanceof Assets) {
                foreach ($asset as $subAsset) {
                    if (null !== $compression && $compression !== $subAsset->getCompression()) {
                        continue;
                    }
                    $files[] = $subAsset->$method();
                }
            }
        }
        return array_unique($files);
    }

    /**
     * @param string $id
     *
     * @return Theme
     */
    public function getTheme($id)
    {
        if (null !== $this->themes) {
            foreach ($this->themes as $theme) {
                if ($theme->getId() == $id) {
                    return $theme;
                }
            }
        }
    }

    /**
     * @param EntryPoint[] $entryPoints
     */
    public function setEntryPoints(array $entryPoints = null)
    {
        $this->entryPoints = $entryPoints;
    }

    /**
     * @return EntryPoint[]
     */
    public function getEntryPoints()
    {
        return $this->entryPoints;
    }

    /**
     * @return array
     */
    public function getEntryPointsArray()
    {
        if (null !== $this->entryPoints) {
            $entryPoints = array();
            foreach ($this->entryPoints as $entryPoint) {
                $entryPoints[$entryPoint->getPath()] = $entryPoint->toArray();
            }
            return $entryPoints;
        }
    }

    /**
     * @param EntryPoint $entryPoint
     * @return EntryPoint[]
     */
    public function getAllEntryPoints(EntryPoint $entryPoint = null)
    {
        $entryPoints = array();

        if ($entryPoint) {
            $subEntryPoints = $entryPoint->getChildren();
        } else {
            $subEntryPoints = $this->getEntryPoints();
        }

        if (null !== $subEntryPoints) {
            foreach ($subEntryPoints as $subEntryPoint) {
                $entryPoints[$this->getBundleName() . '/' . $subEntryPoint->getFullPath()] = $subEntryPoint;
                $entryPoints = array_merge(
                    $entryPoints,
                    $this->getAllEntryPoints($subEntryPoint)
                );
            }
        }

        return $entryPoints;
    }

    /**
     * @param string $path Full path, delimited with `/`;
     *
     * @return EntryPoint
     */
    public function getEntryPoint($path)
    {
        $first = (false === ($pos = strpos($path, '/'))) ? $path : substr($path, 0, $pos);

        if (null !== $this->entryPoints) {
            foreach ($this->entryPoints as $entryPoint) {
                if ($first == $entryPoint->getPath()) {
                    if (false !== strpos($path, '/')) {
                        return $entryPoint->getChild(substr($path, $pos + 1));
                    } else {
                        return $entryPoint;
                    }
                }
            }
        }
    }

    /**
     * @return Object[]
     */
    public function getObjects()
    {
        return $this->objects;
    }

    /**
     * @return array
     */
    public function getObjectsArray()
    {
        if (null !== $this->objects) {
            $objects = array();
            foreach ($this->objects as $object) {
                $objects[strtolower($object->getId())] = $object->toArray();
            }
            return $objects;
        }
    }

    /**
     * @param Object[] $objects
     */
    public function setObjects(array $objects = null)
    {
        $this->objects = [];
        foreach ($objects as $object) {
            $object->setBundle($this);
            $this->objects[$this->getKrynCore()->getObjects()->normalizeObjectKey($object->getId())] = $object;
        }
    }

    /**
     * @param string $id
     *
     * @return Object
     */
    public function getObject($id)
    {
        if (null !== $this->objects) {
            $id = Objects::normalizeObjectKey($id);
            return isset($this->objects[$id]) ? $this->objects[$id] : null;
        }
    }

    /**
     * @return Theme[]
     */
    public function getThemes()
    {
        return $this->themes;
    }

    /**
     * @param Theme[] $themes
     */
    public function setThemes(array $themes = null)
    {
        $this->themes = $themes;
    }

    /**
     * @param string $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param Event[] $events
     */
    public function setEvents(array $events = null)
    {
        $this->events = $events;
    }

    /**
     * @return Event[]
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * @param Event[] $listeners
     */
    public function setListeners(array $listeners = null)
    {
        $this->listeners = $listeners;
    }

    /**
     * @return Event[]
     */
    public function getListeners()
    {
        return $this->listeners;
    }

    /**
     * @param BundleCache[] $caches
     */
    public function setCaches(array $caches = null)
    {
        $this->caches = $caches;
    }

    /**
     * @return BundleCache[]
     */
    public function getCaches()
    {
        return $this->caches;
    }


}