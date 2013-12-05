<?php

namespace Kryn\CmsBundle\Configuration;

class SystemConfig extends Model {

    protected $rootName = 'config';

    protected $docBlocks = [
        'timezone' => '
    IMPORTANT: Set this to your php timezone.
    see: http://www.php.net/manual/en/timezones.php
    ',
        'systemTitle' => 'The system title of this installation.',
        'languages' => 'Comma separated list of supported languages. (systemwide)',
        'bundles' => '
    A list of installed bundles. Enter here the PHP FQDN (Will be resolved through PSR-0 and then loaded)

    Example:
        <bundle>Publication\PublicationBundle</bundle>
    ',
        'adminUrl' => 'Defines under which url the backend is. Default is http://<domain>/kryn. where `kryn` is the `adminUrl`.',
        'email' => 'Is displayed as the administrator\'s email in error messages etc.',
        'tempDir' => 'A directory path where the system stores temp files. Relative to web root. E.g `app/cache/` or `/tmp/`.',
        'id' => 'A installation id. If you have several kryn instances you should define a unique one. Gets defines through the installer.',
        'passwordHashKey' => 'This is a key generated through the installation process. You should not change it!
    The system needs this key to decrypt the passwords in the users database.'
    ];

    protected $arrayIndexNames = [
        'bundles' => 'bundle'
    ];

    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $systemTitle;

    /**
     * @var string
     */
    protected $adminUrl = 'kryn';

    /**
     * @var string
     */
    protected $languages = 'en';

    /**
     * @var string
     */
    protected $tempDir = 'app/cache';

    /**
     * @var string
     */
    protected $email;

    /**
     * @var string
     */
    protected $timezone;

    /**
     * @var string
     */
    protected $passwordHashKey;

    /**
     * @var string
     */
    protected $debug = false;

    /**
     * @var string[]
     */
    protected $bundles;

    /**
     * @var Database
     */
    protected $database;

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var Errors
     */
    protected $errors;

    /**
     * @var Logs
     */
    protected $logs;

    /**
     * @var SystemConfigClient
     */
    protected $client;

    /**
     * @var SystemMountPoint[]
     */
    protected $mountPoints;

    /**
     * @var FilePermission
     */
    protected $file;


    /**
     * {@inheritDocs}
     */
    public function save($path = 'app/config/config.xml', $withDefaults = true)
    {
        return parent::save($path, $withDefaults);
    }

    /**
     * @param string[] $bundles
     */
    public function setBundles(array $bundles = null)
    {
        $this->bundles = $bundles;
    }

    /**
     * @return string[]
     */
    public function getBundles()
    {
        return $this->bundles;
    }

    /**
     * @param string $bundleName
     */
    public function removeBundle($bundleName)
    {
        if (null !== $this->bundles) {
            $idx = array_search($bundleName, $this->bundles);
            if (false !== $idx) {
                unset($this->bundles[$idx]);
            }
        }
    }

    public function isSystemBundle($bundleName)
    {
        $bundleName = strtolower($bundleName);
        return in_array($bundleName, [
            'core',
            'corebundle',
            'core\corebundle',
            'admin',
            'adminbundle',
            'admin\adminbundle',
            'users',
            'usersbundle',
            'users\usersbundle'
        ]);

    }

    /**
     * @param string $bundleName
     */
    public function addBundle($bundleName)
    {
        if (!$this->isSystemBundle($bundleName) && !in_array($bundleName, $this->bundles)) {
            $this->bundles[] = $bundleName;
        }
    }

    public function getBundlesConfigsHash()
    {
        if (null !== $this->bundles) {
            $hash = '';
            foreach ($this->bundles as $bundleName) {
                $bundle = Kryn::getBundle($bundleName);
                if ($bundle) {
                    $files = $bundle->getConfigFiles();
                }
            }
        }
    }

    /**
     * @param string $languages
     */
    public function setLanguages($languages)
    {
        $this->languages = $languages;
    }

    /**
     * @return string
     */
    public function getLanguages()
    {
        return $this->languages;
    }

    /**
     * @param Cache $cache
     */
    public function setCache(Cache $cache = null)
    {
        $this->cache = $cache;
    }

    /**
     * @param bool $orCreate creates the value of not exists.
     *
     * @return Cache
     */
    public function getCache($orCreate = false)
    {
        if ($orCreate && null === $this->cache) {
            $this->cache = new Cache(null, $this->getKrynCore());
        }
        return $this->cache;
    }

    /**
     * @param Client $client
     */
    public function setClient(Client $client = null)
    {
        $this->client = $client;
    }

    /**
     * @param bool $orCreate creates the value of not exists.
     *
     * @return Client
     */
    public function getClient($orCreate = false)
    {
        if ($orCreate && null === $this->client) {
            $this->client = new Client();
        }
        return $this->client;
    }

    /**
     * @param Errors $errors
     */
    public function setErrors(Errors $errors = null)
    {
        $this->errors = $errors;
    }

    /**
     * @param bool $orCreate creates the value of not exists.
     *
     * @return Errors
     */
    public function getErrors($orCreate = false)
    {
        if ($orCreate && null === $this->errors) {
            $this->errors = new Errors();
        }
        return $this->errors;
    }

    /**
     * @param FilePermission $file
     */
    public function setFile(FilePermission $file = null)
    {
        $this->file = $file;
    }

    /**
     * @param bool $orCreate creates the value of not exists.
     *
     * @return FilePermission
     */
    public function getFile($orCreate = false)
    {
        if ($orCreate && null === $this->file) {
            $this->file = new FilePermission();
        }
        return $this->file;
    }

    /**
     * @param MountPoint[] $mountPoints
     */
    public function setMountPoints(array $mountPoints = null)
    {
        $this->mountPoints = $mountPoints;
    }

    /**
     * @param bool $orCreate creates the value of not exists.
     *
     * @return MountPoint[]
     */
    public function getMountPoints($orCreate = false)
    {
        if ($orCreate && null === $this->mountPoints) {
            $this->mountPoints = [];
        }

        return $this->mountPoints;
    }

    /**
     * @param string $systemTitle
     */
    public function setSystemTitle($systemTitle)
    {
        $this->systemTitle = $systemTitle;
    }

    /**
     * @return string
     */
    public function getSystemTitle()
    {
        return $this->systemTitle;
    }

    /**
     * @param string $timezone
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;
    }

    /**
     * @return string
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * @param Database $database
     */
    public function setDatabase(Database $database = null)
    {
        $this->database = $database;
    }

    /**
     * @param bool $orCreate creates the value of not exists.
     *
     * @return Database
     */
    public function getDatabase($orCreate = false)
    {
        if ($orCreate && null === $this->database) {
            $this->database = new Database();
        }
        return $this->database;
    }

    /**
     * @param string $passwordHashKey
     */
    public function setPasswordHashKey($passwordHashKey)
    {
        $this->passwordHashKey = $passwordHashKey;
    }

    /**
     * @return string
     */
    public function getPasswordHashKey()
    {
        return $this->passwordHashKey;
    }

    /**
     * @param boolean $debug
     */
    public function setDebug($debug)
    {
        $this->debug = $this->bool($debug);
    }

    /**
     * @return string
     */
    public function getDebug()
    {
        return $this->debug;
    }

    /**
     * @return boolean
     */
    public function isDebug()
    {
        return true == $this->debug;
    }


    /**
     * @param string $tempDir
     */
    public function setTempDir($tempDir)
    {
        $this->tempDir = $tempDir;
    }

    /**
     * @return string
     */
    public function getTempDir()
    {
        return $this->tempDir;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $adminUrl
     */
    public function setAdminUrl($adminUrl)
    {
        $this->adminUrl = $adminUrl;
    }

    /**
     *
     * @return string with starting and trailing slash
     */
    public function getAdminUrl()
    {
        $url = $this->adminUrl;
        if ('/' !== substr($url, 0, 1)){
            $url = '/' . $url;
        }
        if ('/' !== substr($url, -1)){
            $url .= '/';
        }
        return $url;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param Logs $logs
     */
    public function setLogs($logs)
    {
        $this->logs = $logs;
    }

    /**
     * @param bool $orCreate
     * @return Logs
     */
    public function getLogs($orCreate = false)
    {
        if (!$this->logs && $orCreate) {
            $this->logs = new Logs();
        }
        return $this->logs;
    }

}