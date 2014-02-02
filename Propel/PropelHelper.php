<?php

namespace Kryn\CmsBundle\Propel;

use Kryn\CmsBundle\Configuration\Connection;
use Kryn\CmsBundle\Core;
use Kryn\CmsBundle\Exceptions\FileNotWritableException;
use Propel\Generator\Command\MigrationDiffCommand;
use Propel\Generator\Command\ModelBuildCommand;
use Propel\Runtime\Connection\ConnectionManagerMasterSlave;
use Propel\Runtime\Connection\ConnectionManagerSingle;
use Propel\Runtime\Propel;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Finder\Finder;

/**
 * Class PropelHelper
 *
 * @package Core
 */
class PropelHelper
{
    /**
     * @var array
     */
    public $objectsToExtension = array();
    /**
     * @var array
     */
    public $classDefinition = array();

    /**
     * @var Core
     */
    protected $krynCore;

    /**
     * @param Core $krynCore
     */
    function __construct(Core $krynCore)
    {
        $this->krynCore = $krynCore;
    }

    /**
     * @param Core $krynCore
     */
    public function setKrynCore($krynCore)
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
     * @return string
     */
    public function init()
    {
        try {
            $result = $this->fullGenerator();
        } catch (\Exception $e) {
//            self::cleanup();
            throw new \Exception('Propel initialization Error.', 0, $e);
        }

//        self::cleanup();

        return $result;
    }

    /**
     * @return string
     */
    public function getTempFolder()
    {
        $kernel = $this->getKrynCore()->getKernel();

        return $kernel->getCacheDir() . '/propel/';
    }

    /**
     *
     */
    public function cleanup()
    {
        $fs = $this->getKrynCore()->getCacheFileSystem();
        if ($fs->has('propel')) {
            $fs->delete('propel');
        }
    }

    /**
     * @return array
     */
    public function checkModelXml()
    {
        $bundles = $this->getKrynCore()->getKernel()->getBundles();
        $errors = [];
        foreach ($bundles as $bundleName => $bundle) {

            if (file_exists($schema = $bundle->getPath() . '/Resources/config/kryn.propel.schema.xml')) {
                simplexml_load_file($schema);
                if ($errors = libxml_get_errors()) {
                    $errors[$schema] = $errors;
                }

            }
        }

        return $errors;
    }

    /**
     * @return string
     */
    public function fullGenerator()
    {
        $this->writeXmlConfig();
        $this->writeBuildProperties();
        $this->collectSchemas();

        $content = '';

        $content .= $this->generateClasses();
        $content .= $this->updateSchema();

//        self::cleanup();

        $content .= "\n\n<b style='color: green'>Done.</b>";

        return $content;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function generateClasses()
    {
        $tmp = $this->getKrynCore()->getKernel()->getCacheDir() . '/';

        if (!file_exists($tmp . 'propel')) {
            self::writeXmlConfig();
            self::writeBuildProperties();
            self::collectSchemas();
        }

        $platform = $this->getKrynCore()->getSystemConfig()->getDatabase()->getMainConnection()->getType();
        $platform = ucfirst($platform) . 'Platform';

        $input = new ArrayInput(array(
            '--input-dir' => $tmp . 'propel/',
            '--output-dir' => $tmp . 'propel/build/classes/',
            '--platform' => $platform,
            '--verbose' => 'vvv'
        ));
        $command = new ModelBuildCommand();
        $command->getDefinition()->addOption(
            new InputOption('--verbose', '-v|vv|vvv', InputOption::VALUE_NONE, '') //because migrationDiffCommand access this option
        );

        $output = new StreamOutput(fopen('php://memory', 'rw'));
        $command->run($input, $output);
        $content = stream_get_contents($output->getStream());
        $content .= self::moveClasses();

        return $content;
    }

    /**
     * @return string
     * @throws \FileNotWritableException
     */
    public function moveClasses()
    {
        $fs = $this->getKrynCore()->getCacheFileSystem();
        $tmp = $this->getKrynCore()->getKernel()->getCacheDir() . '/';
        $result = '';

        if ($fs->has('propel-classes')) {
            $fs->delete('propel-classes');
        }

        $fs->rename('propel/build/classes', 'propel-classes');

        $bundles = $this->getKrynCore()->getKernel()->getBundles();

        foreach ($bundles as $bundleName => $bundle) {
            $source = $tmp
                . 'propel-classes/'
                . str_replace('\\', '/', ucfirst($bundle->getNamespace()))
                . '/Model';

            if (!is_dir($source)) {
                continue;
            }

            $files = Finder::create()
                ->files()
                ->in($source)
                ->depth(0)
                ->name('*.php');

            $result .= "$source" . "\n";

            foreach ($files as $file) {
                $target = $bundle->getPath() . '/Model/' . basename($file->getPathname());

                //$result .= "$file => " . (file_exists($target) + 0) . "\n";
                if (!file_exists($target)) {
                    try {
                        if (!is_dir(dirname($target))) {
                            mkdir(dirname($target));
                        }
                    } catch (\Exception $e) {
                        throw new \Exception(sprintf('Can not create directory `%s`.', dirname($target)), 0, $e);
                    }
                    if (!copy($file->getPathname(), $target)) {
                        throw new FileNotWritableException(tf('Can not move file %s to %s', $source, $target));
                    }
                }
                unlink($file->getPathname());
            }
        }

        return $result;

    }

    public function getDsn(Connection $connection)
    {
        $type = strtolower($connection->getType());
        $dsn = $type;

        if ('sqlite' === $dsn) {
            $file = $connection->getServer();
            if (substr($file, 0, 1) != '/') {
                $file = PATH . $file;
            }
            $dsn .= ':' . $file;
        } else {
            $dsn .= ':host=' . $connection->getServer();
            $dsn .= ';dbname=' . $connection->getName();
        }

        return $dsn;
    }

    /**
     * @param Connection $connection
     *
     * @return string
     */
    public function getConnectionXml(Connection $connection)
    {
        $dsn = $this->getDsn($connection);

        $user = htmlspecialchars($connection->getUsername(), ENT_XML1);
        $password = htmlspecialchars($connection->getPassword(), ENT_XML1);
        $dsn = htmlspecialchars($dsn, ENT_XML1);

        $persistent = $connection->getPersistent() ? 'true' : 'false';

        $xml = "
    <connection>
        <dsn>$dsn</dsn>
        <user>$user</user>
        <password>$password</password>

        <options>
            <option id=\"ATTR_PERSISTENT\">$persistent</option>
        </options>";

//        if ('mysql' === $type) {
//            $xml .= '
//        <attributes>
//            <option id="ATTR_EMULATE_PREPARES">true</option>
//        </attributes>
//            ';
//        }

        $xml .= '
        <settings>
            <setting id="charset">utf8</setting>
        </settings>
    </connection>';

        return $xml;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function writeXmlConfig()
    {
        $fs = $this->getKrynCore()->getCacheFileSystem();
        $path = $this->getKrynCore()->getKernel()->getCacheDir();

        try {
            $fs->mkdir('propel');
        } catch (\Exception $e) {
            throw new \Exception(sprintf('Can not create propel folder `%s`.', $path . '/propel'), 0, $e);
        }

        $config = $this->getKrynCore()->getSystemConfig();
        $adapter = $config->getDatabase()->getMainConnection()->getType();

        $xml = '<?xml version="1.0"?>
<config>
    <propel>
        <datasources default="default">
            <datasource id="default">
                <adapter>' . $adapter . '</adapter>
                ';

        foreach ($config->getDatabase()->getConnections() as $connection) {
            if (!$connection->isSlave()) {
                $xml .= self::getConnectionXml($connection) . "\n";
            }
        }

        $slaves = '';
        foreach ($config->getDatabase()->getConnections() as $connection) {
            if ($connection->isSlave()) {
                $slaves .= self::getConnectionXml($connection) . "\n";
            }
        }

        if ($slaves) {
            $xml .= "<slaves>$slaves</slaves>";
        }

        $xml .= '
            </datasource>
        </datasources>
    </propel>
</config>';

        $fs->write('propel/runtime-conf.xml', $xml);
        $fs->write('propel/buildtime-conf.xml', $xml);

        return true;
    }

    public function loadConfig()
    {
        $serviceContainer = Propel::getServiceContainer();
        $database = $this->getKrynCore()->getSystemConfig()->getDatabase();

        if ($database->hasSlaveConnection()) {
            $manager = new ConnectionManagerMasterSlave();

            $config = $this->getManagerConfig($database->getMainConnection());
            $manager->setWriteConfiguration($config);

            $slaves = [];
            foreach ($database->getConnections() as $connection) {
                if ($connection->isSlave()) {
                    $slaves[] = $this->getManagerConfig($connection);
                }
            }
            $manager->setReadConfiguration($slaves);
        } else {
            $manager = new ConnectionManagerSingle();
            $config = $this->getManagerConfig($database->getMainConnection());
            $manager->setConfiguration($config);
        }

        $manager->setName('default');

        $serviceContainer->setAdapterClass('default', $database->getMainConnection()->getType());
        $serviceContainer->setConnectionManager('default', $manager);
        $serviceContainer->setDefaultDatasource('default');
    }

    public function getManagerConfig(Connection $connection)
    {
        $config = [];
        $config['dsn'] = $this->getDsn($connection);
        $config['user'] = (string)$connection->getUsername();
        $config['password'] = (string)$connection->getPassword();

        $config['options']['ATTR_PERSISTENT'] = (boolean)$connection->getPersistent();
        $config['settings']['charset'] = $connection->getCharset();

        return $config;
    }

    /**
     * Updates database's Schema.
     *
     * This function creates whatever is needed to do the job.
     * (means, calls writeXmlConfig() etc if necessary).
     *
     * This function inits the Propel class.
     *
     * @param  bool $withDrop
     *
     * @return string
     * @throws \Exception
     */
    public function updateSchema($withDrop = false)
    {
        $sql = self::getSqlDiff($withDrop);

        if (is_array($sql)) {
            throw new \Exception("Propel updateSchema failed: \n" . $sql[0]);
        }

        if (!$sql) {
            return "Schema up 2 date.";
        }

        $sql = explode(";\n", $sql);

        $this->loadConfig();
        $con = Propel::getWriteConnection('default');
        $tablePrefix = $this->getKrynCore()->getSystemConfig()->getDatabase()->getPrefix();
        $con->beginTransaction();
        try {
            foreach ($sql as $query) {
                if ($tablePrefix && 0 === strpos($query, 'DROP')) {
                    preg_match('/DROP ([^\s]*) /', $query, $match);
                    if (isset($match[1]) && $match[1]) {
                        $tableName = preg_replace('/[^a-zA-Z0-9_]\.\$/', '', $match[1]);
                        if (false !== $pos = strpos($tableName, '.')) {
                            $tableName = substr($tableName, $pos + 1);
                        }

                        if (0 === strpos($tableName, $tablePrefix)) {
                            //tablePrefix is in this table name at the beginning, so jump over
                            continue;
                        }
                    }
                }
                $con->exec($query);
            }
        } catch (\PDOException $e) {
            $con->rollBack();
            throw new \PDOException($e->getMessage() . ' in SQL: ' . $query);
        }
        $con->commit();

        return 'ok';
    }


    /**
     * @return bool
     */
    public function collectSchemas()
    {
        $cacheDir = $this->getKrynCore()->getKernel()->getCacheDir() . '/propel/';

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir);
        }

        $finder = Finder::create()
            ->in($cacheDir)
            ->files()
            ->depth(0)
            ->name('*.schema.xml');

        foreach ($finder as $file) {
            unlink($file->getPathname());
        }

        $prefix = $this->getKrynCore()->getSystemConfig()->getDatabase()->getPrefix();
        $schemeData = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n  <database name=\"default\" tablePrefix=\"$prefix\" defaultIdMethod=\"native\"\n";

        $krynBehavior = '<behavior name="\\Kryn\\CmsBundle\\Propel\\KrynBehavior" />';

        $bundles = $this->getKrynCore()->getKernel()->getBundles();

        foreach ($bundles as $bundleName => $bundle) {
            if (file_exists($schema = $bundle->getPath() . '/Resources/config/kryn.propel.schema.built.xml')) {

                $extension = $bundle->getNamespace();
                $tables = simplexml_load_file($schema);
                $newSchema = $schemeData . ' namespace="' . ucfirst($extension) . '\\Model">';

                foreach ($tables->table as $table) {
                    $newSchema .= $table->asXML() . "\n    ";
                }

                $newSchema .= "$krynBehavior</database>";

                $file = $bundleName . '.schema.xml';
                file_put_contents($cacheDir . $file, $newSchema);
            }

        }

        return true;
    }

    /**
     * @return array|string
     */
    public function getSqlDiff()
    {
        $tmp = $this->getKrynCore()->getKernel()->getCacheDir() . '/';

        if (!file_exists($tmp . 'propel/runtime-conf.xml')) {
            self::writeXmlConfig();
            self::writeBuildProperties();
            self::collectSchemas();
        }

        //remove all migration files
        if (is_dir($tmp . 'propel/build/')) {
            $files = Finder::create()
                ->in($tmp . 'propel/build/')
                ->depth(0)
                ->name('PropelMigration_*.php');

            foreach ($files as $file) {
                unlink($file->getPathname());
            }
        }

        $platform = $this->getKrynCore()->getSystemConfig()->getDatabase()->getMainConnection()->getType();
        $platform = ucfirst($platform) . 'Platform';

        $input = new ArrayInput(array(
            '--input-dir' => $tmp . 'propel/',
            '--output-dir' => $tmp . 'propel/build/',
            '--platform' => $platform,
            '--verbose' => 'vvv'
        ));
        $command = new MigrationDiffCommand();
        $command->getDefinition()->addOption(
            new InputOption('--verbose', '-v|vv|vvv', InputOption::VALUE_NONE, '') //because migrationDiffCommand access this option
        );

        $output = new StreamOutput(fopen('php://memory', 'rw'));
        $command->run($input, $output);

        if (is_dir($tmp . 'propel/build/')) {
            $files = Finder::create()
                ->in($tmp . 'propel/build/')
                ->depth(0)
                ->name('PropelMigration_*.php');
            foreach ($files as $file) {
                $lastMigrationFile = $file->getPathname();
                break;
            }
        }

        if (!isset($lastMigrationFile) || !$lastMigrationFile) {
            return '';
        }

        preg_match('/(.*)\/PropelMigration_([0-9]*)\.php/', $lastMigrationFile, $matches);
        $clazz = 'PropelMigration_' . $matches[2];
        $uid = str_replace('.', '_', uniqid('', true));
        $newClazz = 'PropelMigration__' . $uid;

        $content = file_get_contents($lastMigrationFile);
        $content = str_replace('class ' . $clazz, 'class PropelMigration__' . $uid, $content);
        file_put_contents($lastMigrationFile, $content);

        include($lastMigrationFile);
        $obj = new $newClazz;

        $sql = $obj->getUpSQL();

        $sql = $sql['default'];
//        unlink($lastMigrationFile);

        // todo
//        if (is_array($protectTables = $this->getKrynCore()->getSystemConfig()->getDatabase()->getProtectTables())) {
//            foreach ($protectTables as $table) {
//                $table = str_replace('%pfx%', pfx, $table);
//                $sql = preg_replace('/^DROP TABLE (IF EXISTS|) ' . $table . '(\n|\s)(.*)\n+/im', '', $sql);
//            }
//        }
        $sql = preg_replace('/^#.*$/im', '', $sql);

        return trim($sql);
    }

    /**
     * @throws Exception
     */
    public function writeBuildProperties()
    {
        $fs = $this->getKrynCore()->getCacheFileSystem();

        $platform = $this->getKrynCore()->getSystemConfig()->getDatabase()->getMainConnection()->getType();
        $platform = ucfirst($platform) . 'Platform';

        $properties = '
propel.mysql.tableType = InnoDB

propel.platform = ' . $platform . '
propel.database.encoding = utf8
propel.project = kryn

propel.namespace.autoPackage = true
propel.packageObjectModel = true
propel.behavior.workspace.class = lib.WorkspaceBehavior
';

        return $fs->write('propel/build.properties', $properties);

    }

}
