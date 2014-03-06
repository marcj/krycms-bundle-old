<?php

namespace Kryn\CmsBundle\Configuration;

use Kryn\CmsBundle\AssetHandler\AssetInfo;
use Kryn\CmsBundle\Exceptions\BundleNotFoundException;
use Kryn\CmsBundle\Exceptions\FileNotWritableException;
use Kryn\CmsBundle\Exceptions\ObjectNotFoundException;
use Kryn\CmsBundle\Objects;
use Kryn\CmsBundle\Tools;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class Bundle extends Model
{
    protected $attributes = ['id'];

    public static $propertyToFile = [
        'objects' => 'kryn.objects.xml',
        'objectAttributes' => 'kryn.objects.xml',
        'entryPoints' => 'kryn.entryPoints.xml',
        'themes' => 'kryn.themes.xml',
        'plugins' => 'kryn.plugins.xml',
    ];

    protected $_excludeFromExport = ['bundleName'];

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
     * @var \Kryn\CmsBundle\Configuration\Object[]
     */
    protected $objects;

    /**
     * @var \Kryn\CmsBundle\Configuration\Field[]
     */
    protected $objectAttributes;

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
    protected $bundleName;

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

    private $triggeredReboot = [];

    /**
     * @param string|BundleInterface $bundleClass
     * @param \DOMElement $bundleDoc
     * @param null $krynCore
     */
    public function __construct($bundleClass, \DOMElement $bundleDoc = null, $krynCore = null)
    {
        $this->element = $bundleDoc;
        if (!$bundleClass) {
            throw new \InvalidArgumentException('$bundleClass needs to be set.');
        }

        if ($bundleClass instanceof BundleInterface) {
            $bundleClass = get_class($bundleClass);
        }

        $this->bundleClass = $bundleClass;
        $this->bundleName = $bundleClass;
        if (false !== $pos = strrpos($bundleClass, '\\')) {
            $this->bundleName = substr($bundleClass, $pos + 1);
        }
        $this->rootName = 'bundle';
        $this->setKrynCore($krynCore);
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        $vars = parent::__sleep();
        $vars[] = 'bundleClass';
        return $vars;
    }

    /**
     * All bundle configs have been loaded.
     *
     * @param Configs $configs
     *
     * @return array|boolean truthy means we need a reboot
     */
    public function boot(Configs $configs)
    {
        $this->triggeredReboot = [];
        $reboot = false;

        if ($this->objectAttributes) {
            foreach ($this->objectAttributes as $attribute){
                $key = $attribute->getId();
                try {
                    $targetObject = $configs->getObject($attribute->getTarget());
                } catch (BundleNotFoundException $e) {
                    continue;
                }
                if (!$attribute->getTarget() || !$targetObject) {
                    continue;
                }
                $field = $targetObject->getField($key);
                if (!$field) {
                    //does not exists, so attach it
                    $attribute->setAttribute(true);
                    $targetObject->addField($attribute);
                    $reboot = true;
                }
            }
        }

        if ($this->getObjects()) {
            foreach ($this->getObjects() as $key => $object) {
                if ($boots = $object->bootRunTime($configs)) {
                    $count = (isset($this->triggeredReboot[$key]) ? $this->triggeredReboot[$key]['count'] : 0) + 1;
                    $this->triggeredReboot[$key] = [
                        'count' => $count,
                        'triggeredReboots' => $boots
                    ];
                }
            }
        }

        return $this->triggeredReboot ?: $reboot;
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
     * @throws FileNotWritableException
     */
    public function saveConfig($path, $withDefaults = false)
    {
        $xml = $this->toXml($withDefaults);
        $doc = new \DOMDocument();
        $doc->formatOutput = true;
        $doc->loadXML("<config>$xml</config>");
        $xml = trim(substr($doc->saveXML(), strlen('<?xml version="1.0"?>') + 1));
        if ((!file_exists($path) && !is_writable(dirname($path))) || (file_exists($path) && !is_writable($path))) {
            throw new FileNotWritableException(sprintf('The file `%s` is not writable.', $path));
        }

        return file_put_contents($path, $xml);
    }

    public function getPropertyFilePath($property)
    {
        if (!isset($this->imported[$property])) {
            $path = $this->getBundleClass()->getPath() . '/Resources/config/' . (@static::$propertyToFile[$property] ? : 'kryn.xml');
            $root = realpath($this->getKrynCore()->getKernel()->getRootDir() . '/../');

            return substr($path, strlen($root) + 1);
        } else {
            return $this->imported[$property];
        }
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
        if (file_exists($path = $this->getKrynCore()->getKernel()->getRootDir() . '/../' . $xmlFile)) {
            $doc->load($path);
        } else {
            $configElement = $doc->createElement('config');
            $doc->appendChild($configElement);
            $bundleElement = $doc->createElement('bundle');
            $configElement->appendChild($bundleElement);
        }

        $xpath = new \DOMXPath($doc);

        $element = null;
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
    public function saveFileBased($property)
    {
        $xml = $this->exportFileBased($property);
        $xmlFile = $this->getPropertyFilePath($property);

        $emptyXml = '<config>
  <bundle/>
</config>';

        $fs = $this->getKrynCore()->getFilesystem();
        if ($xml == $emptyXml) {
            if ($fs->has($xmlFile)) {
                return $fs->delete($xmlFile);
            } else {
                return true;
            }
        } else {
            return $fs->write($xmlFile, $xml);
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
     * Example: `KrynCmsBundle`.
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
        return $this->getKrynCore()->getBundle($this->bundleClass);
    }

    public function getPath()
    {
        return $this->getBundleClass()->getPath();
    }

    /**
     * @param BundleInterface $class
     */
    public function setBundleClass(BundleInterface $class)
    {
        $this->bundleClass = get_class($class);
    }

    /**
     * Returns the bundle name without the 'Bundle' suffix, lowercased.
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
     * @return string
     */
    public function getNamespace()
    {
        return $this->getBundleClass()->getNamespace();
    }

    /**
     * @param \DOMNode $node
     * @param string $file
     */
    public function import(\DOMNode $node, $file = null)
    {
        if ('bundle' === $node->nodeName) {
            $imported = $this->importNode($node);

            $root = realpath($this->getKrynCore()->getKernel()->getRootDir() . '/../');
            foreach ($imported as $property) {
                $this->imported[$property] = Tools::getRelativePath($file, $root);
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
        if (!$filter) {
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
     * @return AssetInfo[]
     */
    public function collectAdminAssetsInfo()
    {
        $assetsInfo = array();

        if ($this->getAdminAssets()) {
            foreach ($this->getAdminAssets() as $asset) {
                if ($asset instanceof Asset) {
                    $assetsInfo[] = $asset->getAssetInfo();
                } else {
                    if ($asset instanceof Assets) {
                        foreach ($asset as $subAsset) {
                            $assetsInfo[] = $subAsset->getAssetInfo();
                        }
                    }
                }
            }
        }

        return $assetsInfo;
    }

    /**
     * @return AssetInfo[]
     */
    public function getAdminAssetsInfo()
    {
        $assets = $this->collectAdminAssetsInfo();
        $assetHandlerContainer = $this->getKrynCore()->getAssetCompilerContainer();
        $result = [];

        // collect assets and compile
        foreach ($assets as $asset) {
            if ($asset->getFile() && $compiler = $assetHandlerContainer->getCompileHandlerByFileExtension($asset->getFile())) {
                if ($assetInfo = $compiler->compileFile($asset->getFile())) {
                    $assetInfo->setAllowCompression($asset->getAllowCompression());
                    $result[] = $assetInfo;
                }
            } else {
                $result[] = $asset;
            }
        }

        return $result;
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
                if (strtolower($theme->getId()) == strtolower($id)) {
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
     * @return \Kryn\CmsBundle\Configuration\Object[]
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
     * @return array
     */
    public function getObjectAttributesArray()
    {
        if (null !== $this->objectAttributes) {
            $objects = array();
            foreach ($this->objectAttributes as $field) {
                $objects[strtolower($field->getId())] = $field->toArray();
            }

            return $objects;
        }
    }

    /**
     * @param \Kryn\CmsBundle\Configuration\Object[] $objects
     */
    public function setObjects(array $objects = null)
    {
        $this->objects = [];
        if ($objects) {
            foreach ($objects as $object) {
                $this->addObject($object);
            }
        }
    }

    /**
     * @param \Kryn\CmsBundle\Configuration\Object $object
     */
    public function addObject(Object $object)
    {
        $object->setBundle($this);
        $this->objects[strtolower($object->getId())] = $object;
    }

    /**
     * @param string $id
     *
     * @return \Kryn\CmsBundle\Configuration\Object|null
     */
    public function getObject($id)
    {
        if (null !== $this->objects) {
            $id = strtolower($id);

            return isset($this->objects[$id]) ? $this->objects[$id] : null;
        }
    }

    /**
     * Creates a new `Object` object and sets Object's Bundle to this instance.
     *
     * @param string $id
     *
     * @return Object
     */
    public function newObject($id){
        $object = new Object(null, $this->getKrynCore());
        $object->setId($id);
        $this->addObject($id);
        return $object;
    }

    /**
     * @param \Kryn\CmsBundle\Configuration\Field[] $objectAttributes
     */
    public function setObjectAttributes(array $objectAttributes = null)
    {
        $this->objectAttributes = $objectAttributes;
    }

    /**
     * @return \Kryn\CmsBundle\Configuration\Field[]
     */
    public function getObjectAttributes()
    {
        return $this->objectAttributes;
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
