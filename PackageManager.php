<?php

namespace Kryn\CmsBundle;

use Kryn\CmsBundle\Model\Base\LanguageQuery;
use Kryn\CmsBundle\Model\Content;
use Kryn\CmsBundle\Model\Domain;
use Kryn\CmsBundle\Model\Language;
use Kryn\CmsBundle\Model\Node;
use Kryn\CmsBundle\Model\NodeQuery;
use Kryn\CmsBundle\Model\Workspace;
use Kryn\CmsBundle\Model\WorkspaceQuery;

use Kryn\CmsBundle\Model\AclQuery;
use Kryn\CmsBundle\Model\Group;
use Kryn\CmsBundle\Model\GroupQuery;
use Kryn\CmsBundle\Model\SessionQuery;
use Kryn\CmsBundle\Model\User;
use Kryn\CmsBundle\Model\UserGroupQuery;
use Kryn\CmsBundle\Model\UserQuery;use Propel\Runtime\Propel;

class PackageManager {

    /**
     * @var string
     */
    protected $domain = '127.0.0.1';

    /**
     * @var string
     */
    protected $path = '/';

    /**
     * @param string $domain
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    /**
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }



    public function installDatabase(Core $krynCore)
    {
        \Kryn\CmsBundle\Model\DomainQuery::create()->deleteAll();
        \Kryn\CmsBundle\Model\NodeQuery::create()->deleteAll();
        \Kryn\CmsBundle\Model\ContentQuery::create()->deleteAll();
        \Kryn\CmsBundle\Model\AppLockQuery::create()->deleteAll();

        $domainName = $this->getDomain();
        if (isset($_GET['domain'])) {
            $domainName = $_GET['domain'];
        } else if (isset($_SERVER['HTTP_HOST'])) {
            $domainName = $_SERVER['HTTP_HOST'];
        } else if (isset($_SERVER['SERVER_NAME'])) {
            $domainName = $_SERVER['SERVER_NAME'];
        }

        $domainName = explode(':', $domainName)[0];

        $domain = new Domain();
        $domain->setDomain($domainName);

        if (isset($_SERVER['REQUEST_URI'])) {
            $path = dirname($_SERVER['REQUEST_URI']);
            if (substr($path, 0, -1) != '/') {
                $path .= '/';
            }
            $path = str_replace("//", "/", $path);
            $path = str_replace('\\', '', $path);
        } else {
            $path = isset($_GET['path']) ? $_GET['path'] : $this->getPath();
        }

        $domain->setPath($path);
        $domain->setTitleFormat('%title | Node title - My Website - change me under domain settings.');
        $domain->setMaster(1);
        $domain->setLang('en');
        $domain->setResourcecompression(1);
        $domain->setSearchIndexKey(md5(time() . '-' . rand()));
        $domain->save();

        $root = new Node();
        $root->setDomainId($domain->getId());
        $root->makeRoot();
        $root->setTitle('root');
        $root->save();

        //setup live workspace
        WorkspaceQuery::create()->deleteAll();
        $workspace = new Workspace();
        $workspace->setTitle('LIVE');
        $workspace->setCreated(time());
        $workspace->save();
        $id = $workspace->getId();
        if ($id != 1) {
            WorkspaceQuery::create()
                ->filterById($id)
                ->update(array('Id' => 1));
        }

        $defaultLayout = '@KrynDemoThemeBundle.krynDemoTheme/layout_default.html.twig';
        $defaultContentTemplate = 'KrynCmsBundle:Default:content.html.twig';
        $Nodes = array(

            array(
                0,
                'Blog',
                $defaultLayout,
                'home',
                '',
                array(
                    '1' => array(
                        array(
                            'text',
                            'Kryn.cms has been installed!',
                            $defaultContentTemplate,
                            '<p>Kryn.cms has been installed correctly.</p><p>&nbsp;</p><p><a href="http://www.kryn.org">Kryn.cms Website</a></p><p>&nbsp;</p><p>&nbsp;</p><p>Go to <a href="kryn">administration</a> to manage your new website.</p><p>&nbsp;</p><p><strong>Default login:</strong></p><p><strong><br /></strong></p><p style="padding-left: 10px;">Username: admin</p><p style="padding-left: 10px;">Password: admin</p>'
                        ),
                        array(
                            'plugin',
                            '',
                            $defaultContentTemplate,
                            '{"bundle":"PublicationBundle","plugin":"listing","options":{"itemsPerPage":10,"maxPages":10,"detailPage":"","template":"default.html.twig","categoryId":[],"enableRss":false}}'
                        )
                    ),
                    '2' => array(
                        array(
                            'plugin',
                            '» CATEGORIES',
                            $defaultContentTemplate,
                            '{"bundle":"PublicationBundle","plugin":"categoryList","options":{"listNode":"1","template":"default.html.twig","category_rsn":[]}}'
                        ),
                    )
                ),
                array(
                    array(1, 'Article', $defaultLayout, 'article', '', array(), array(), 0)
                )
            ),
            array(
                0,
                'Links',
                $defaultLayout,
                'links',
                '',
                array(
                    '1' => array(
                        array(
                            'text',
                            'Links',
                            $defaultContentTemplate,
                            'Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi.'
                        )
                    ),
                    '2' => array(
                        array('text', '» About', $defaultContentTemplate, 'hoho'),
                    )
                ),
                array(
                    array(1, 'Kryn.cms Official Website', $defaultLayout, 'www-kryn-org', 'http://www.kryn.org/'),
                    array(1, 'Kryn.cms Documentation', $defaultLayout, 'docu-kryn-org', 'http://docu.kryn.org/'),
                    array(1, 'Kryn.cms Extensions', $defaultLayout, 'www-kryn-org-extensions', 'http://www.kryn.org/extensions')
                )
            ),
            array(
                0,
                'About me',
                $defaultLayout,
                'about-me',
                '',
                array(
                    '1' => array(
                        array(
                            'text',
                            'About me',
                            $defaultContentTemplate,
                            'Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi.'
                        )
                    ),
                    '2' => array(
                        array(
                            'text',
                            'Hi, my Name is',
                            $defaultContentTemplate,
                            'John Doe and I\'m a creative dude living in Springfield. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt...'
                        ),
                    )
                ),
                array(
                    array(
                        0,
                        'Sublink 1',
                        $defaultLayout,
                        'sublink-1',
                        '',
                        array(
                            '1' => array(
                                array(
                                    'text',
                                    'Sublink 1',
                                    $defaultContentTemplate,
                                    'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.'
                                )
                            )
                        )
                    ),
                    array(
                        0,
                        'Sublink 2',
                        $defaultLayout,
                        'sublink-2',
                        '',
                        array(
                            '1' => array(
                                array(
                                    'text',
                                    'Sublink 1',
                                    $defaultContentTemplate,
                                    'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.<br/><br/><h3>Lorem ...</h3>ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.'
                                )
                            )
                        )
                    ),
                )
            ),
            array(
                2,
                'Footer Navigation',
                '',
                '',
                '',
                array(),
                array(
                    array(
                        0,
                        'Sitemap',
                        $defaultLayout,
                        'sitemap',
                        '',
                        array(
                            '1' => array(
                                array('plugin', 'Sitemap', $defaultContentTemplate, 'todo')
                            )
                        )
                    ),
                    array(
                        0,
                        'Contact',
                        $defaultLayout,
                        'contact',
                        '',
                        array(
                            '1' => array(
                                array('plugin', 'Contact form', $defaultContentTemplate, 'todo')
                            )
                        )
                    ),
                    array(
                        0,
                        'Impress',
                        $defaultLayout,
                        'impress',
                        '',
                        array(
                            '1' => array(
                                array(
                                    'text',
                                    'Impress',
                                    $defaultContentTemplate,
                                    'Owner: <b>Name</b><br/>Street, Nr<br/>Country<br/>'
                                )
                            )
                        )
                    ),
                )
            ),
            array(
                3,
                'Footer text',
                '',
                '',
                '',
                array(
                    '1' => array(
                        array(
                            'text',
                            '',
                            $defaultContentTemplate,
                            '<p>&copy; my Node | <a href="http://www.kryn.org/">CMS</a> powered by Kryn.cms - simply different</p>'
                        )
                    )
                ),
                array()
            ),

        );

        if (!function_exists(__NAMESPACE__ . '\installNodes')) {
            /**
             * @static
             *
             * @param Node  $pNode
             * @param array $pChildren
             */
            function installNodes($pNode, $pChildren)
            {
                /*
                * 0: type
                * 1: Title
                * 2: layout
                * 3: url
                * 4: link target
                * 5: contents
                * 6: children
                * 7: visible
                */
                foreach ($pChildren as $Node) {
                    $oNode = new Node();
                    $oNode->setDomainId($pNode->getDomainId());
                    $oNode->setType($Node[0]);
                    $oNode->setTitle($Node[1]);
                    $oNode->setLayout($Node[2]);
                    $oNode->setUrn($Node[3]);
                    $oNode->setParentId($pNode->getId());
                    $oNode->insertAsLastChildOf($pNode);

                    if ($Node[4]) {
                        $oNode->setLink($Node[4]);
                    }

                    if (isset($Node[7])) {
                        $oNode->setVisible($Node[7]);
                    } else {
                        $oNode->setVisible(1);
                    }

                    $oNode->save();

                    if (isset($Node[5])) {
                        installContents($oNode, $Node[5]);
                    }

                    if (isset($Node[6])) {
                        installNodes($oNode, $Node[6]);
                    }
                }

            }
        }

        if (!function_exists(__NAMESPACE__ . '\installContents')) {
            /**
             * @static
             *
             * @param Node  $pNode
             * @param array $pBoxedContents
             */
            function installContents($pNode, $pBoxedContents)
            {
                if (!is_array($pBoxedContents)) {
                    return;
                }

                /**
                 * 0: type74
                 * 1: title
                 * 2: template
                 * 3: content
                 *
                 */
                foreach ($pBoxedContents as $boxId => $contents) {
                    foreach ($contents as $content) {

                        $oContent = new Content();

                        $oContent->setNodeId($pNode->getId());
                        $oContent->setBoxId($boxId);
                        $oContent->setType($content[0]);
                        $oContent->setTitle($content[1]);
                        $oContent->setTemplate($content[2]);
                        $oContent->setContent($content[3]);
                        $oContent->save();

                    }
                }

            }
        }

        /*
        * 0: type
        * 1: Title
        * 2: layout
        * 3: url
        * 4: link target
        * 5: contents
        * 6: children
        * 7: visible
        */
        foreach ($Nodes as $Node) {

            $oNode = new Node();

            $oNode->setDomainId($domain->getId());
            $oNode->setType($Node[0]);
            $oNode->setTitle($Node[1]);
            $oNode->setLayout($Node[2]);
            $oNode->setUrn($Node[3]);
            $oNode->insertAsLastChildOf($root);
            if (isset($Node[7])) {
                $oNode->setVisible($Node[7]);
            } else {
                $oNode->setVisible(1);
            }

            $oNode->save();

            if ($Node[4]) {
                $oNode->setLink($Node[4]);
            }

            if ($Node[5]) {
                installContents($oNode, $Node[5]);
            }

            if ($Node[6]) {
                installNodes($oNode, $Node[6]);
            }
        }

        $startNode = NodeQuery::create()->filterByDomainId($domain->getId())->findOneByLft(2);
        $domain->setStartNodeId($startNode->getId());
        $domain->save();

        LanguageQuery::create()
            ->deleteAll();

        $h = fopen(__DIR__ . '/Resources/package/ISO_639-1_codes.csv', 'r');
        if ($h) {
            while (($data = fgetcsv($h, 1000, ",")) !== false) {
                $lang = new Language();
                $lang->setCode($data[0]);
                $lang->setTitle($data[1]);
                $lang->setLangtitle($data[2]);
                $lang->save();
            }
        }

        LanguageQuery::create()
            ->filterByCode('en')
            ->update(array('Visible' => 1));

//search footer id
        $footerNavi = NodeQuery::create()->findOneByTitle('Footer Navigation');
        $footerText = NodeQuery::create()->findOneByTitle('Footer text');

        $domainThemeProperties = new \Kryn\CmsBundle\Properties('{"@KrynDemoThemeBundle":{"krynDemoTheme":{"logo":"@KrynDemoThemeBundle/images/logo.png","title":"BUSINESSNAME","slogan":"Business Slogan comes here!","footer_deposit":"' . $footerText->getId(
            ) . '","search_Node":"12","footer_navi":"' . $footerNavi->getId() . '"}}}');
        $domain->setThemeProperties($domainThemeProperties);
        $domain->save();


        SessionQuery::create()->deleteAll();
        UserGroupQuery::create()->deleteAll();
        UserQuery::create()->deleteAll();
        GroupQuery::create()->deleteAll();
        AclQuery::create()->deleteAll();

        $groupGuest = new Group();
        $groupGuest->setName('Guest');
        $groupGuest->setDescription('All anonymous user');
        $groupGuest->save();

        $id = $groupGuest->getId(0);
        GroupQuery::create()
            ->filterById($id)
            ->update(array('Id' => 0));

        $groupAdmin = new Group();
        $groupAdmin->setName('Admin');
        $groupAdmin->setDescription('Super user');
        $groupAdmin->save();
        $id = $groupAdmin->getId();
        GroupQuery::create()
            ->filterById($id)
            ->update(array('Id' => 1));
        $groupAdmin->setId(1);

        $groupUsers = new Group();
        $groupUsers->setName('Users');
        $groupUsers->setDescription('Registered user');
        $groupUsers->save();

        $admin = new User();
        $admin->setUsername('admin');
        $admin->setFirstName('Admini');
        $admin->setLastName('strator');
        $admin->setEmail('admin@localhost');
        $admin->setActivate(1);
        $admin->setPassword('admin', $krynCore);
        $liveWorkspace = WorkspaceQuery::create()->findOneById(1);
        $admin->addWorkspace($liveWorkspace);

        $settings = new \Kryn\CmsBundle\Properties(array(
            'userBg' => '/admin/images/userBgs/defaultImages/color-blue.jpg',
            'adminLanguage' => 'en'
        ));

        $admin->setSettings($settings);
        $admin->save();

        $id = $admin->getId();
        UserQuery::create()
            ->filterById($id)
            ->update(array('Id' => 1));
        $admin->setId(1);

        $admin->addGroupMembership($groupAdmin);
        $admin->save();

    }

}