<?php

namespace Kryn\CmsBundle\Controller\Admin\BundleManager;

use Composer\Autoload\AutoloadGenerator;
use Composer\Composer;
use Composer\DependencyResolver\DefaultPolicy;
use Composer\DependencyResolver\Pool;
use Composer\EventDispatcher\EventDispatcher;
use Composer\Factory;
use Composer\Installer;
use Composer\IO\BufferIO;
use Composer\Package\Version\VersionParser;
use Composer\Repository\CompositeRepository;
use Composer\Repository\PlatformRepository;
use Composer\Repository\RepositoryInterface;
use Core\Exceptions\BundleNotFoundException;
use Core\Exceptions\FileNotWritableException;
use Core\Exceptions\InvalidArgumentException;
use Core\Exceptions\PackageNotFoundException;
use Core\Kryn;
use Core\SystemFile;

class Manager
{
    /**
     * @var BufferIO
     */
    private $composerIO;

    /**
     * @var Composer
     */
    private $composer;

    public function __construct()
    {
        define('KRYN_MANAGER', true);
    }

    /**
     * Filters any special char out of the name.
     *
     * @static
     *
     * @param $name Reference
     */
    public static function prepareName(&$name)
    {
        $name = preg_replace('/[^a-zA-Z0-9-_\\\\]/', '', $name);
    }

    /**
     * Deactivates a bundle in the system config.
     *
     * @param string $bundle
     * @param bool   $reloadConfig
     * @return int
     */
    public function deactivate($bundle, $reloadConfig = false)
    {
        Manager::prepareName($bundle);

        Kryn::getSystemConfig()->removeBundle($bundle);

        if ($reloadConfig) {
            Kryn::loadModuleConfigs();
        }
        \Admin\Utils::clearModuleCache($bundle);

        return Kryn::getSystemConfig()->save();
    }

    public function createBundle($package, $namespace, $directoryStructure = false)
    {
        if (!preg_match('/^([a-zA-Z0-9_-]+)\\/([a-zA-Z0-9_\\.-]+)$/', $package)) {
            throw new InvalidArgumentException('`package` is not a correct composer name.');
        }
        if (!preg_match('/^(([a-zA-Z0-9_-]+)\\\\)*([a-zA-Z0-9_-]*)Bundle/', $namespace)) {
            throw new InvalidArgumentException('`package` is not a correct composer name.');
        }

        $directoryStructure = filter_var($directoryStructure, FILTER_VALIDATE_BOOLEAN);

        $classDir = './src/' . str_replace('\\', '/', $namespace);
        mkdirr($classDir);

        $bundleClassName = str_replace('\\Bundle\\', '\\', $namespace);
        $bundleClassName = str_replace('\\', '', $namespace);
        $classFile = $classDir . '/' . $bundleClassName . '.php';
        $composerFile = $classDir . '/composer.json';

        $classPhp = sprintf('<?php

namespace %s;

use \Core\Bundle;

class %s extends Bundle {

}
',
            $namespace,
            $bundleClassName
        );

        SystemFile::setContent($classFile, $classPhp);

        $composer = array(
            'repositories' => array(
                array(
                    'type' => 'composer',
                    'url' =>  'http:\/\/packages.kryn.org\/'
                )
            ),
            'name' => $package,
            'target-dir' => str_replace('\\', '/', $namespace),
            'autoload' => array(
                'psr-0' => array(
                    $namespace => './'
                )
            )
        );

        SystemFile::setContent($composerFile, json_format($composer));

        if ($directoryStructure) {
            SystemFile::createFolder($classDir . '/Controller');
            SystemFile::createFolder($classDir . '/Resources');
            SystemFile::createFolder($classDir . '/Resources/views');
            SystemFile::createFolder($classDir . '/Resources/screenshots');
            SystemFile::createFolder($classDir . '/Resources/doc');
            SystemFile::createFolder($classDir . '/Resources/doc/images');
            SystemFile::createFolder($classDir . '/Resources/public');
            SystemFile::createFolder($classDir . '/Resources/config');

            $krynXml = "<config>
  <bundle>
    <label>$bundleClassName</label>
  </bundle>
</config>";
            SystemFile::createFile($classDir . '/Resources/config/kryn.xml', $krynXml);

            $line = str_repeat('=', strlen($bundleClassName));
            $docuIndex = "$bundleClassName
$line

This is the bundle $bundleClassName.
";

            SystemFile::createFile($classDir . '/Resources/doc/index.md', $docuIndex);

        }

        return true;
    }

    /**
     * Activates a bundle in the system config.
     *
     * @param $bundle
     * @param bool $reloadConfig
     * @return bool|int
     */
    public function activate($bundle, $reloadConfig = false)
    {
        Manager::prepareName($bundle);

        Kryn::getSystemConfig()->addBundle($bundle);

        if ($reloadConfig) {
            Kryn::loadModuleConfigs();
        }
        \Admin\Utils::clearModuleCache($bundle);

        return Kryn::getSystemConfig()->save();
    }

    public function getInfo($bundle)
    {
        $bundle = Kryn::getBundle($bundle);

        $info = $bundle->getComposer();
        $info['_installed'] = $bundle->getInstalledInfo();
        return $info;
    }

    public static function getInstalledInfo($name)
    {
        if (SystemFile::exists('composer.lock')) {
            $composerLock = SystemFile::getContent('composer.lock');
            if ($composerLock) {
                $composerLock = json_decode($composerLock, true);

                foreach ($composerLock['packages'] as $package) {
                    if (strtolower($package['name']) == strtolower($name)) {
                        return $package;
                    }
                }
            }
        }

        return [];
    }

    public function getInstalled()
    {
        $packages = [];
        $bundles = [];
        if (SystemFile::exists('composer.json')) {
            $composer = SystemFile::getContent('composer.json');
            if ($composer) {
                $composer = json_decode($composer, true);

                $installedVersions = [];
                if (SystemFile::exists('composer.lock')) {
                    $locker = SystemFile::getContent('composer.lock');
                    $locker = json_decode($locker, true);
                    if ($locker) {
                        foreach ($locker['packages'] as $package) {
                            $version = $package['version'];
                            $ref = false;
                            if ('dev-master' === $version) {
                                if ($package['source']) {
                                    $ref = substr($package['source']['reference'], 0, 7);
                                } else if ($package['dist']) {
                                    $ref = substr($package['source']['reference'], 0, 7);
                                }
                            }
                            if ($ref) {
                                $version = [
                                    'version' => $version,
                                    'reference' => $ref
                                ];
                            }

                            $installedVersions[strtolower($package['name'])] = $version;
                        }
                    }
                }

                $packages = [];

                foreach ((array)$composer['require'] as $name => $version) {
                    $package = [
                        'name' => $name,
                        'version' => $version,
                        'installed' => $installedVersions[strtolower($name)]
                    ];
                    $packages[] = $package;
                }
            }
        }

        $bundleClasses = array_merge(
            static::getBundlesFromPath('vendor'),
            static::getBundlesFromPath('src')
        );

        if ($bundleClasses) {
            foreach ($bundleClasses as $bundle) {
                $bundleObj = new $bundle;
                $path = $bundleObj->getPath();
                if (0 === strpos($path, 'vendor/')) {
                    $expl = explode('/', $path);
                    $package = $expl[1] . '/' . $expl[2];
                } else {
                    $package = 'local ./src/';
                }
                $bundleInfo = [
                    'class' => $bundle,
                    'package' => $package,
                    'active' => Kryn::isActiveBundle($bundle)
                ];
                $bundles[] = $bundleInfo;
            }
        }

        return [
            'packages' => $packages,
            'bundles' => $bundles
        ];
    }

    /**
     * @param string $path
     * @return array
     */
    public function getBundlesFromPath($path)
    {
        $bundles = [];
        if (SystemFile::exists($path)) {

            $finder = new \Symfony\Component\Finder\Finder();
            $finder
                ->files()
                ->name('*Bundle.php')
                ->notPath('/Tests/')
                ->notPath('/Test/')
                ->in($path);

            /** @var \Symfony\Component\Finder\SplFileInfo $file */
            foreach ($finder as $file) {

                $file = $file->getRealPath();
                $content = file_get_contents($file);
                preg_match('/^\s*\t*class ([a-z0-9_]+)/mi', $content, $className);
                if (isset($className[1]) && $className[1]){
                    preg_match('/\s*\t*namespace ([a-zA-Z0-9_\\\\]+)/', $content, $namespace);
                    $class = (count($namespace) > 1 ? $namespace[1] . '\\' : '' ) . $className[1];

                    if ('Bundle' === $className[1]) {
                        continue;
                    }

                    $bundles[] = $class;
                }
            }
        }

        return $bundles;
    }

    private static function versionCompareToServer($local, $server)
    {
        list($major, $minor, $patch) = explode(".", $local);
        $lversion = $major * 1000 * 1000 + $minor * 1000 + $patch;

        list($major, $minor, $patch) = explode(".", $server);
        $sversion = $major * 1000 * 1000 + $minor * 1000 + $patch;

        if ($lversion == $sversion) {
            return '=';
        } // Same version
        else if ($lversion < $sversion) {
            return '<';
        } // Local older
        else {
            return '>';
        } // Local newer
    }

    public function getLocal()
    {
        $finder = new \Symfony\Component\Finder\Finder();
        $finder
            ->files()
            ->name('*Bundle.php')
            ->notPath('/Tests/')
            ->notPath('/Test/')
            ->in('vendor')
            ->in('src');

        return $this->getBundles($finder);
    }

    public function getBundles($finder)
    {
        $bundles = array();
        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($finder as $file) {

            $file = $file->getRealPath();
            $content = file_get_contents($file);
            preg_match('/^\s*\t*class ([a-z0-9_]+)/mi', $content, $className);
            if (isset($className[1]) && $className[1]) {
                preg_match('/\s*\t*namespace ([a-zA-Z0-9_\\\\]+)/', $content, $namespace);
                $class = (count($namespace) > 1 ? $namespace[1] . '\\' : '') . $className[1];

                if ('Bundle' === $className[1] || false !== strpos($class, '\\Test\\') ||
                    false !== strpos($class, '\\Tests\\')
                ) {
                    continue;
                }

                $bundles[] = $class;
            }
        }
        $bundles = array_unique($bundles);

        foreach ($bundles as $bundleClass) {
            $bundle = new $bundleClass();
            if (!($bundle instanceof \Core\Bundle)) {
                continue;
            }

            if ($composer = $bundle->getComposer()) {
                $composer['_path'] = $bundle->getPath();
                $composer['_installed'] = $bundle->getInstalledInfo();
                $res[$bundle->getClassName()] = $composer;
                if (null === $res[$bundle->getClassName()]['activated']) {
                    $res[$bundle->getClassName()]['activated'] = array_search(
                        $bundle->getClassName(),
                        Kryn::$config['bundles']
                    ) !== false ? true : false;
                }
            }
        }
        return $res;
    }

    public function check4Updates()
    {
        $res['found'] = false;

        # add kryn-core

        foreach (Kryn::$configs as $key => $config) {
            $version = '0';
            $name = $key;
            //$version = wget(Kryn::$config['repoServer'] . "/?version=$name");
            if ($version && $version != '' && self::versionCompareToServer(
                    $config->getVersion(),
                    $version['content']
                ) == '<'
            ) {
                $res['found'] = true;
                $temp = array();
                $temp['newVersion'] = $version;
                $temp['name'] = $name;
                $res['modules'][] = $temp;
            }
        }

        json($res);

    }

    /**
     * Returns true if all dependencies are fine.
     *
     * @param $name
     *
     * @return boolean
     */
    public function hasOpenDependencies($name)
    {
    }

    /**
     * Returns a list of open dependencies.
     *
     * @param $name
     */
    public function getOpenDependencies($name)
    {
    }

    /**
     *
     * Installs a bundle.
     * Activates a bundle, fires his package scripts
     * and updates the propel ORM, if the bundle has a model.xml.
     *
     * @param  string $bundle
     * @param  bool   $ormUpdate
     *
     * @return bool
     */
    public function install($bundle, $ormUpdate = false)
    {
        Manager::prepareName($bundle);

        $hasPropelModels = SystemFile::exists(Kryn::getBundleDir($bundle) . 'Resources/config/models.xml');
        $this->fireScript($bundle, 'install');

        //fire update propel orm
        if ($ormUpdate && $hasPropelModels) {
            //update propel
            \Core\PropelHelper::updateSchema();
            \Core\PropelHelper::cleanup();
        }

        $this->activate($bundle, true);

        return true;
    }

    /**
     * Fires the database package script.
     *
     * @param  string $name
     *
     * @return bool
     */
    public function installDatabase($name)
    {
        $this->fireScript($name, 'install.database');

        return true;
    }

    /**
     * Removes relevant data and object's data. Executes also the uninstall script.
     * Removes database values, some files etc.
     *
     * @param $bundle
     * @param bool $removeFiles
     * @param bool $ormUpdate
     *
     * @return bool
     *
     * @throws \Core\Exceptions\BundleNotFoundException
     */
    public function uninstall($bundle, $removeFiles = true, $ormUpdate = false)
    {
        Manager::prepareName($bundle);

        $bundleObject = \Core\Kryn::getBundle($bundle, false);
        if (!$bundleObject) {
            throw new BundleNotFoundException(tf('Bundle `%s` not found.', $bundle));
        }

        $hasPropelModels = SystemFile::exists(\Core\Kryn::resolvePath($bundle, 'Resources/config') . 'model.xml');

        \Core\Event::fire('admin/module/manager/uninstall/pre', $bundle);

        $this->fireScript($bundle, 'uninstall');

        \Core\Event::fire('admin/module/manager/uninstall/post', $bundle);

        $this->deactivate($bundle, true);

        //fire update propel orm
        if ($ormUpdate && $hasPropelModels) {
            //remove propel classes in temp
            \Core\TempFile::remove('propel-classes/' . $bundleObject->getRootNamespace());

            //update propel
            if ($ormUpdate) {
                \Core\PropelHelper::updateSchema();
                \Core\PropelHelper::cleanup();
            }
        }

        //remove files
        if (filter_var($removeFiles, FILTER_VALIDATE_BOOLEAN)) {
            delDir($bundleObject->getPath());
            if (0 === strpos($bundleObject->getPath(), $this->getComposerVendorDir())) {
                $path = explode('/', $bundleObject->getPath());
                $composerName = $path[1].'/'.$path[2];
                $this->uninstallComposer($composerName);
            }
        }

        return true;
    }

    public function getComposerVendorDir()
    {
        return './vendor/';
    }

    /**
     * @param string $name
     * @return bool
     */
    public function uninstallComposer($name)
    {
        if (SystemFile::exists('composer.json') && is_string($name)) {
            $composer = SystemFile::getContent('composer.json');
            if ($composer) {
                $composer = json_decode($composer, true);
                if (is_array($composer)) {
                    $pathToDelete = false;
                    foreach ($composer['require'] as $key => $value) {
                        if (strtolower($key) == strtolower($name)) {
                            unset($composer['require'][$key]);
                            $pathToDelete = $key;
                        }
                    }
                    SystemFile::setContent('composer.json', json_format($composer));

                    if ($pathToDelete) {
                        $this->searchAndUninstallBundles($this->getComposerVendorDir() . $pathToDelete);
                        delDir($this->getComposerVendorDir() . $pathToDelete);
                        if (file_exists($pathToDelete)) {
                            Kryn::getLogger()->addWarning(sprintf('[UninstallComposer] Can not delete folder `%s`.', $pathToDelete));
                        }
                    }
                    $this->updateAutoloader();

                    return true;
                }
            }
        }
        return false;
    }

    protected function getPackage(RepositoryInterface $installedRepo, RepositoryInterface $repos, $name, $version = null)
    {
        $name = strtolower($name);
        $constraint = null;
        if ($version) {
            $constraint = $this->versionParser->parseConstraints($version);
        }

        $policy = new DefaultPolicy();
        $pool = new Pool('dev');
        $pool->addRepository($repos);

        $matchedPackage = null;
        $versions = array();
        $matches = $pool->whatProvides($name, $constraint);
        foreach ($matches as $index => $package) {
            // skip providers/replacers
            if ($package->getName() !== $name) {
                unset($matches[$index]);
                continue;
            }

            // select an exact match if it is in the installed repo and no specific version was required
            if (null === $version && $installedRepo->hasPackage($package)) {
                $matchedPackage = $package;
            }

            $versions[$package->getPrettyVersion()] = $package->getVersion();
            $matches[$index] = $package->getId();
        }

        // select prefered package according to policy rules
        if (!$matchedPackage && $matches && $prefered = $policy->selectPreferedPackages($pool, array(), $matches)) {
            $matchedPackage = $pool->literalToPackage($prefered[0]);
        }

        return array($matchedPackage, $versions);
    }

//    public function getComposerPackages()
//    {
//        $composer = $this->getComposer();
//
//        $platformRepo = new PlatformRepository();
//        $localRepo = $composer->getRepositoryManager()->getLocalRepository();
//        $installedRepo = new CompositeRepository(array($localRepo, $platformRepo));
//        $repos = new CompositeRepository(array_merge(array($installedRepo), $composer->getRepositoryManager()->getRepositories()));
//
//        /** @var \Composer\Repository\ComposerRepository $repo */
//        $repo = $composer->getRepositoryManager()->getRepositories()[1];
//        //return $repo->findPackage();
//        var_dump($repo->getMinimalPackages());
//        die();
//        foreach ($repo->getPackages() as $package) {
//            echo $package->getName();
//        }
//
//    }

    public function installComposer($name, $version, $withBundles = false)
    {
        if (!is_writeable($vendorDir = $this->getComposerVendorDir())) {
            throw new FileNotWritableException(sprintf('Directory `%s` is not writable.', $vendorDir));
        }

        $composer = $this->getComposer();

        //check if bundle exist
        $this->versionParser = new VersionParser();
        $platformRepo = new PlatformRepository();
        $localRepo = $composer->getRepositoryManager()->getLocalRepository();
        $installedRepo = new CompositeRepository(array($localRepo, $platformRepo));
        $repos = new CompositeRepository(array_merge(array($installedRepo), $composer->getRepositoryManager()->getRepositories()));
        list($package, $versions) = $this->getPackage($installedRepo, $repos, $name, $version);

        if (!$package) {
            throw new PackageNotFoundException(sprintf('Can not find package `%s` with version `%s`.', $name, $version));
        }

        if (SystemFile::exists('composer.json') && is_string($name)) {
            $composerJson = SystemFile::getContent('composer.json');
            if ($composerJson) {
                $composerJson = json_decode($composerJson, true);
                if (is_array($composerJson)) {
                    $found = false;
                    foreach ($composerJson['require'] as $key => $value) {
                        if (strtolower($key) == strtolower($name)) {
                            unset($composerJson['require'][$key]);
                            $found = $key;
                        }
                    }
                    if (!$found) {
                        $composerJson['require'][$name] = $version;
                        SystemFile::setContent('composer.json', json_format($composerJson));
                    } else {
                        $name = $found;
                    }
                }
            }
        }

        $install = Installer::create($this->composerIO, $composer);
        $install
            ->setVerbose(true)
            ->setPreferDist(true)
            ->setDevMode(true)
            ->setUpdate(true)
            ->setUpdateWhitelist([$name]);
        ;

        if (filter_var($withBundles, FILTER_VALIDATE_BOOLEAN)) {
            $this->searchAndInstallBundles($this->getComposerVendorDir() . $name);
        }

        $this->updateAutoloader();

        return $this->composerIO->getOutput();
    }

    /**
     * @param string $path
     * @param bool $removeFiles
     */
    public function searchAndUninstallBundles($path, $removeFiles = false)
    {
        $bundles = $this->getBundlesFromPath($path);
        foreach ($bundles as $bundle) {
            if (Kryn::isActiveBundle($bundle)) {
                $this->uninstall($bundle, $removeFiles, true);
            }
        }
    }

    /**
     * @param string $path
     */
    public function searchAndInstallBundles($path)
    {
        $bundles = $this->getBundlesFromPath($path);
        foreach ($bundles as $bundle) {
            $this->install($bundle, true);
        }
    }

    /**
     * @return Composer
     */
    private function getComposer()
    {
        putenv('COMPOSER_HOME=./');
        putenv('COMPOSER_CACHE_DIR=' . Kryn::getTempFolder());

        $this->composerIO = new BufferIO();
        $this->composer = Factory::create($this->composerIO);

        return $this->composer;
    }

    /**
     * @throws \Core\Exceptions\FileNotWritableException
     */
    public function updateAutoloader()
    {
        if (!is_writeable($composerDir = $this->getComposerVendorDir() . 'composer/')) {
            throw new FileNotWritableException(sprintf('Directory `%s` is not writable.', $composerDir));
        }

        if (!is_writeable($autoload = $this->getComposerVendorDir() . 'autoload.php')) {
            throw new FileNotWritableException(sprintf('File `%s` is not writable.', $autoload));
        }

        $composer = $this->getComposer();
        $eventDispatcher = new EventDispatcher($composer, $this->composerIO);
        $autoloadGenerator = new AutoloadGenerator($eventDispatcher);
        $localRepo = $composer->getRepositoryManager()->getLocalRepository();

        $autoloadGenerator->dump(
            $composer->getConfig(),
            $localRepo,
            $composer->getPackage(),
            $composer->getInstallationManager(),
            'composer',
            true
        );
    }

    /**
     * Fires the script in module/$module/package/$script.php and its events.
     *
     * @ events admin/module/manager/<$script>/pre
     * @ events admin/module/manager/<$script>/failed
     * @ events admin/module/manager/<$script>
     *
     * @param  string $module
     * @param  string $script
     *
     * @throws \SecurityException
     * @throws \Exception
     * @return bool
     */
    public function fireScript($module, $script)
    {
        \Core\Event::fire('admin/module/manager/' . $script . '/pre', $module);

        $file = $this->getScriptFile($module, $script);

        if (file_exists($file)) {

            $content = file_get_contents($file);
            if (strpos($content, 'KRYN_MANAGER') === false) {
                throw new \SecurityException('!It is not safe, if your script can be external executed!');
            }

            try {
                include($file);
            } catch (\Exception $ex) {
                //\Core\Event::fire('admin/module/manager/' . $script . '/failed', $arg = array($module, $ex));
                throw $ex;
            }

            \Core\Event::fire('admin/module/manager/' . $script, $module);
        }

        return true;
    }


    private function getScriptFile($module, $name)
    {
        self::prepareName($module);

        try {
            return \Core\Kryn::getBundleDir($module) . 'Resources/package/' . $name . '.php';
        } catch (\Core\Exceptions\ModuleDirNotFoundException $e) {
        }

    }
}
