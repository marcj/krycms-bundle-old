<?php

namespace Kryn\CmsBundle;

use Kryn\CmsBundle\Model\Content;
use Symfony\Component\HttpFoundation\Response;
use Kryn\CmsBundle\Controller\PageController;

/**
 * This is the response, we use to generate the basic html skeleton.
 * Ths actual body content comes from Core\PageController.
 */
class PageResponse extends Response
{
    /**
     * @var string
     */
    public $docType = 'KrynCmsBundle:Doctypes:html5.html.twig';

    /**
     * @var Core
     */
    protected $krynCore;

    /**
     * Use in <script and <link tags.
     *
     * @var string
     */
    protected $tagEndChar = '>';

    /**
     * @var string
     */
    protected $language = 'en';

    /**
     * All plugin responses. Mostly only one.
     *
     * @var array
     */
    private $pluginResponse = array();

    /**
     * CSS files.
     *
     * @var array
     */
    private $css = array(
        array('path' => '@KrynCmsBundle/css/normalize.css', 'type' => 'text/css'),
        array('path' => '@KrynCmsBundle/css/defaults.css', 'type' => 'text/css')
    );

    /**
     * Javascript files and scripts.
     *
     * @var array
     */
    private $js = array();

    /**
     * @var string
     */
    private $title;

    /**
     * All additional html>head elements.
     *
     * @var array
     */
    private $header = array();

    /**
     * @var bool
     */
    private $domainHandling = true;

    /**
     * @var string
     */
    private $favicon = '@KrynCmsBundle/images/favicon.ico';

    /**
     * @var bool
     */
    private $resourceCompression = false;

    /**
     * @var StopwatchHelper
     */
    private $stopwatch;

    /**
     * Constructor
     */
    public function __construct($content = '', $status = 200, $headers = array())
    {
        parent::__construct($content, $status, $headers);
    }

    /**
     * @param StopwatchHelper $stopwatch
     */
    public function setStopwatch($stopwatch)
    {
        $this->stopwatch = $stopwatch;
    }

    /**
     * @return StopwatchHelper
     */
    public function getStopwatch()
    {
        return $this->stopwatch;
    }

    /**
     * @param Core $krynCore
     */
    public function setKrynCore(Core $krynCore)
    {
        $this->krynCore = $krynCore;
        $this->getKrynCore()->getRequest(); //trigger loading of the current request, otherwise we're out of scope
    }

    /**
     * @return Core
     */
    public function getKrynCore()
    {
        return $this->krynCore;
    }

    /**
     * @param string $favicon
     */
    public function setFavicon($favicon)
    {
        $this->favicon = $favicon;
    }

    /**
     * @return string
     */
    public function getFavicon()
    {
        return $this->favicon;
    }

    /**
     * @param string $tagEndChar
     */
    public function setTagEndChar($tagEndChar)
    {
        $this->tagEndChar = $tagEndChar;
    }

    /**
     * @return string
     */
    public function getTagEndChar()
    {
        return $this->tagEndChar;
    }

    /**
     * @param boolean $domainHandling
     */
    public function setDomainHandling($domainHandling)
    {
        $this->domainHandling = $domainHandling;
    }

    /**
     * @return bool
     */
    public function getDomainHandling()
    {
        return $this->domainHandling;
    }

    /**
     * @param bool $resourceCompression
     */
    public function setResourceCompression($resourceCompression)
    {
        $this->resourceCompression = $resourceCompression;
    }

    /**
     * @return bool
     */
    public function getResourceCompression()
    {
        return $this->resourceCompression;
    }

    /**
     * Adds a css file to the page.
     *
     * @param string $path
     * @param string $type
     */
    public function addCssFile($path, $type = 'text/css')
    {
        $insert = array('path' => $path, 'type' => $type);
        if (array_search($insert, $this->css) === false) {
            $this->css[] = $insert;
        }
    }

    /**
     * Adds css source to the page.
     *
     * @param string $content
     * @param string $type
     */
    public function addCss($content, $type = 'text/css')
    {
        $insert = array('content' => $content, 'type' => $type);
        if (array_search($insert, $this->css) === false) {
            $this->css[] = $insert;
        }
    }

    /**
     * Adds a javascript file to the page.
     *
     * @param string $path
     * @param string $position
     * @param string $type
     */
    public function addJsFile($path, $position = 'top', $type = 'text/javascript')
    {
        $insert = array('path' => $path, 'position' => $position, 'type' => $type);
        if (array_search($insert, $this->js) === false) {
            $this->js[] = $insert;
        }
    }

    /**
     * Adds javascript source to the page.
     *
     * @param string $content
     * @param string $position
     * @param string $type
     */
    public function addJs($content, $position = 'top', $type = 'text/javascript')
    {
        $insert = array('content' => $content, 'position' => $position, 'type' => $type);
        if (array_search($insert, $this->js) === false) {
            $this->js[] = $insert;
        }
    }

    /**
     * Adds a additionally HTML header element.
     *
     * @param string $content
     */
    public function addHeader($content)
    {
        $this->header[] = $content;
    }

    /**
     *
     */
    public function renderContent()
    {
        $page = $this->getKrynCore()->getCurrentPage();
        $this->setTitle($page->getTitle());

        $this->getStopwatch()->start("Render PageResponse");
        $html = $this->buildHtml();
        $this->setContent($html);
        $this->getStopwatch()->stop("Render PageResponse");
    }

    /**
     * Builds the HTML skeleton, sends all HTTP headers and the HTTP body.
     *
     * This handles the SearchEngine stuff as well.
     *
     * @return Response
     */
    public function send()
    {
        $this->prepare($this->getKrynCore()->getRequest());
        $this->setCharset('utf-8');
        $this->getKrynCore()->getEventDispatcher()->dispatch('core/page-response-send-pre');

        //search engine, todo
        if (false && Kryn::$disableSearchEngine == false) {
            SearchEngine::createPageIndex($this->getContent());
        }

        return parent::send();
    }

    public function getFaviconPath()
    {
        return $this->getKrynCore()->resolveWebPath($this->getFavicon());
    }

    public function buildHtml()
    {
        $body = $this->buildBody();

        $templating = $this->getKrynCore()->getTemplating();

        $html = $templating->render(
            $this->getDocType(),
            [
                'pageResponse' => $this,
                'body' => $body,
                'cssTags' => $this->getCssTags('top'),
                'jsTags' => $this->getScriptTags('top'),
                'jsTagsBottom' => $this->getScriptTags('bottom')
            ]
        );

//        $doctypeTemplate = $this->getDocType();
//        var_dump($templating);
//        exit;
//
//        $html = sprintf(
//            "%s
//            %s
//            <head>
//            %s
//            </head>
//            <body>
//            %s
//            %s
//            </body>
//            </html>",
//            $docType,
//            $htmlOpener,
//            $header,
//            $body,
//            $beforeBodyClose
//        );

        $html = preg_replace(
            '/href="#([^"]*)"/',
            'href="' . $this->getKrynCore()->getRequest()->getBaseUrl() . '/' . '#$1"',
            $html
        );

//        $html = Kryn::parseObjectUrls($html);
//        $html = Kryn::translate($html);
//        Kryn::removeSearchBlocks($html);

        return $html;
    }

    /**
     * Builds the html header tag for the favicon.
     *
     * @return string
     */
    public function getFaviconTag()
    {
        if ($this->getFavicon()) {
            return sprintf(
                '<link rel="shortcut icon" type="image/x-icon" href="%s">' . chr(10),
                $this->getKrynCore()->resolveWebPath($this->getFavicon())
            );
        }
    }

    /**
     * Builds the html body of the current page.
     *
     * @return string
     */
    public function buildBody()
    {
        $page = $this->getKrynCore()->getCurrentPage();
        if (!$page) {
            return '';
        }

//        Kryn::$themeProperties = array();
        $propertyPath = '';

        $layout = $layoutPath = $page->getLayout();
        if (false !== ($pos = strpos($layoutPath, '/'))) {
            $layout = substr($layoutPath, 0, $pos);
            $layoutPath = substr($layoutPath, $pos);
        }
        $layoutSplitted = explode('.', $layout);
        $bundleName = substr($layoutSplitted[0], 1);

        try {
            $layoutBundle = $this->getKrynCore()->getKernel()->getBundle($bundleName);
        } catch (\Exception $e) {
            throw new \LogicException(sprintf(
                'Could not found bundle `%s` for layout `%s`.',
                $bundleName,
                $layoutPath
            ), 0, $e);
        }

        $bundleConfig = $this->getKrynCore()->getConfig($layoutBundle->getName());

        $theme = $bundleConfig->getTheme($layoutSplitted[1]);
        if ($theme) {
            $propertyPath = '@' . $bundleName . '/' . $theme->getId();
        }

        if ($propertyPath) {
//            if ($themeProperties = kryn::$domain->getThemeProperties()) {
//                Kryn::$themeProperties = $themeProperties->getByPath($propertyPath);
//            }
        }

        $layoutPath = str_replace('/', ':', $layoutPath);

        $template = $this->getKrynCore()->getTemplating();

        PageController::setCurrentRenderPage($page->getId());

        return $template->render(
            $bundleName . ':' . $layoutPath,
            array(
                'baseUrl' => $this->getBaseHref(),
                'themeProperties' => [] //Kryn::$themeProperties
            )
        );
    }

    /**
     * Returns `<meta http-equiv="content-type" content="text/html; charset=%s">` based on $this->getCharset().
     *
     * @return string
     */
    public function getContentTypeTag()
    {
        return sprintf(
            '<meta http-equiv="content-type" content="text/html; charset=%s">' . chr(10),
            $this->getCharset()
        );
    }

    /**
     * Returns all additional html header elements.
     */
    public function getAdditionalHeaderTags()
    {
        return implode("\n    ", $this->header) . "\n";
    }

    /**
     * @return string
     */
    public function getDocType()
    {
        return $this->docType;
    }

    /**
     * The template path to the main html skeleton.
     *
     * Default is @KrynCmsBundle:Doctypes:html5.html.twig.
     *
     * @param string $docType
     */
    public function setDocType($docType)
    {
        $this->docType = $docType;
    }

    /**
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @return string
     */
    public function getBaseHref()
    {
        return $this->getKrynCore()->getRequest()->getBasePath() . '/';
    }

//    /**
//     * Returns the `<base href="%s"` based on Core\Kryn::getBaseUrl().
//     *
//     * @return string
//     */
//    public function getBaseHrefTag()
//    {
//        return sprintf('<base href="%s" %s', $this->getKrynCore()->getRequest()->getBasePath().'/', $this->getTagEndChar());
//    }

//    /**
//     * Returns `<meta name="DC.language" content="%s">` filled with the language of the current domain.
//     *
//     * @return string
//     */
//    public function getMetaLanguageTag()
//    {
//        if ($this->getDomainHandling() && $this->getKrynCore()->getCurrentDomain()) {
//            return sprintf(
//                '<meta name="DC.language" content="%s" %s',
//                Kryn::$domain->getLang(),
//                $this->getTagEndChar()
//            );
//        }
//    }

//    /**
//     * Returns the title as html tag.
//     *
//     * @return string
//     */
//    public function getTitleTag()
//    {
//        if ($this->getDomainHandling() && $this->getKrynCore()->getCurrentDomain()) {
//            $title = Kryn::$domain->getTitleFormat();
//
//            if (Kryn::$page) {
//                $title = str_replace(
//                    array(
//                         '%title'
//                    ),
//                    array(
//                         Kryn::$page->getAlternativeTitle() ? : Kryn::$page->getTitle()
//                    )
//                    ,
//                    $title
//                );
//            }
//        } else {
//            $title = $this->getTitle();
//        }
//
//        return sprintf("<title>%s</title>\n", $title);
//    }

    /**
     * Sets the html title.
     *
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Gets the html title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Compares two PageResponses and returns the difference as array/
     *
     * @param  PageResponse $response
     *
     * @return array
     */
    public function diff(PageResponse $response)
    {
        $diff = array();

        $blacklist = array('pluginResponse');

        foreach ($this as $key => $value) {
            if (in_array($key, $blacklist)) {
                continue;
            }
            $getter = 'get' . ucfirst($key);

            if (!is_callable(array($this, $getter))) {
                continue;
            }

            $particular = null;
            $other = $response->$getter();

            if (is_array($value)) {
                $particular = $this->arrayDiff($value, $other);
            } elseif ($value != $other) {
                $particular = $other;
            }

            if ($particular) {
                $diff[$key] = $particular;
            }
        }

        return $diff;
    }

    /**
     * @param  array $p1
     * @param  arry $p2
     *
     * @return array
     */
    public function arrayDiff($p1, $p2)
    {
        $diff = array();
        foreach ($p2 as $v) {
            if (array_search($v, $p1) === false) {
                $diff[] = $v;
            }
        }

        return $diff;
    }

    /**
     * Patches a diff from $this->diff().
     *
     * @param array $diff
     */
    public function patch(array $diff)
    {
        $refClass = new \ReflectionClass($this);
        $defaults = $refClass->getDefaultProperties();
        foreach ($diff as $key => $value) {
            if (is_array($value) && is_array($this->$key)) {
                $this->$key = array_merge($this->$key, $value);
            } else if (isset($defaults[$key]) && $value != $defaults[$key]) {
                $this->$key = $value;
            }
        }
    }

    /**
     * @param \Symfony\Component\HttpFoundation\ResponseHeaderBag $headers
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\ResponseHeaderBag
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param array $css
     */
    public function setCss($css)
    {
        $this->css = $css;
    }

    /**
     * @return array
     */
    public function getCss()
    {
        return $this->css;
    }

    /**
     * @param array $js
     */
    public function setJs($js)
    {
        $this->js = $js;
    }

    /**
     * @return array
     */
    public function getJs()
    {
        return $this->js;
    }

    /**
     *
     *
     * @param  PluginResponse $response
     *
     * @return PageResponse
     */
    public function setPluginResponse(PluginResponse $response)
    {
        /** @var $content Content */
        $content = $response->getControllerRequest()->attributes->get('_content');
        $this->pluginResponse[$content->getId()] = $response;

        return $this;
    }

    /**
     *
     * @param Content $content
     *
     * @return string
     */
    public function getPluginResponse($content)
    {
        $id = $content;
        if ($content instanceof Content) {
            $id = $content->getId();
        }

        return isset($this->pluginResponse[$id]) ? $this->pluginResponse[$id] : '';
    }

    /**
     * Returns all <link> tags based on the attached css files.
     *
     * @return string
     * @throws \FileNotFoundException
     */
    public function getCssTags()
    {
        $result = '';

        if ($this->getResourceCompression()) {

            $cssCode = '';
            foreach ($this->css as $css) {
                if ($css['path']) {
                    if (false !== strpos($css['path'], "://")) {
                        $result .= sprintf(
                            '<link rel="stylesheet" type="text/css" href="%s" %s',
                            $css['path'],
                            $this->getTagEndChar()
                        );
                    } else {
                        //local
                        $file = $this->getKrynCore()->resolvePath($css['path'], 'Resources/public');

                        if (file_exists($file) && $modifiedTime = filemtime($file)) {
                            $cssCode .= $file . '_' . $modifiedTime;
                        } else {
                            Kryn::getLogger()->addError(tf('CSS file `%s` [%s] not found.', $file, $css['path']));
                        }
                    }
                } else {
                    $cssCode .= '_' . $css['content'] . '_';
                }
            }

            $cssMd5 = md5($cssCode);
            $cssCachedFile = 'cachedCss_' . $cssMd5 . '.css';

            if (!file_exists($this->getKrynCore()->getWebCacheDir() . $cssCachedFile)) {
                $compressFiles = array();
                foreach ($this->css as $css) {
                    if ($css['path']) {
                        if (false !== strpos($css['path'], "://")) {
                            $result .= sprintf(
                                PHP_EOL.'    <link rel="stylesheet" type="text/css" href="%s" %s',
                                $css['path'],
                                $this->getTagEndChar()
                            );
                        } else {
                            $compressFiles[] = $css['path'];
                        }
                    } else {
                        $result .= sprintf(
                            PHP_EOL.'    <style type="text/css">' . chr(10) . '%s' . chr(10) . '</style>' . chr(10),
                            $css['content']
                        );
                    }
                }
                $cssContent = $this->getKrynCore()->getUtils()->compressCss($compressFiles, 'cache/');
                $this->getKrynCore()->getWebFilesystem()->put('cache/' . $cssCachedFile, $cssContent);
            }
            $result .= sprintf(
                '<link rel="stylesheet" type="text/css" href="cache/%s" %s',
                $cssCachedFile,
                $this->getTagEndChar()
            );
        } else {
            foreach ($this->css as $css) {
                if ($css['path']) {
                    if (false !== strpos($css['path'], "://")) {
                        $result .= sprintf(
                            PHP_EOL.'    <link rel="stylesheet" type="text/css" href="%s" %s',
                            $css['path'],
                            $this->getTagEndChar()
                        );
                    } else {
                        $file = $this->getKrynCore()->resolvePath($css['path'], 'Resources/public');
                        $public = $this->getKrynCore()->resolveWebPath($css['path']);

                        if (!file_exists($file)) {
                            continue;
                        }
                        $modifiedTime = filemtime($file);

                        $result .= sprintf(
                            PHP_EOL.'    <link rel="stylesheet" type="%s" href="%s" %s',
                            $css['type'],
                            $public . ($modifiedTime ? '?c=' . $modifiedTime : ''),
                            $this->getTagEndChar()
                        );
                    }
                } else {
                    $result .= sprintf(
                        '<style type="text/css">' . chr(10) . '%s' . chr(10) . '</style>' . chr(10),
                        $css['content']
                    );
                }
            }
        }

        return $result;

    }

    /**
     * Generates the <script> tags based on all attached js files/scripts.
     *
     * @param string $position
     *
     * @return string
     * @throws \FileNotFoundException
     */
    public function getScriptTags($position = 'top')
    {
        $result = '';

        if ($this->getResourceCompression()) {
            $jsCode = '';
            foreach ($this->js as $js) {
                if ($js['position'] != $position) {
                    continue;
                }

                if ($js['path']) {
                    if (false !== strpos($js['path'], "http://")) {
                        $result .= '<script type="text/javascript" src="' . $js['path'] . '" ></script>' . "\n";
                    } else {
                        //local
                        $file = $this->getKrynCore()->resolvePath($js['path'], 'Resources/public');
                        if (file_exists($file) && $modifiedTime = filemtime($file)) {
                            $jsCode .= $file . '_' . $modifiedTime;
                        } else {
                            Kryn::getLogger()->addError(tf('JavaScript file `%s` [%s] not found.', $file, $js['path']));
                        }
                    }
                } else {
                    $jsCode .= '_' . $js['content'] . '_';
                }
            }

            $jsMd5 = md5($jsCode);
            $jsCachedFile = 'cachedJs_' . $jsMd5 . '.js';
            $jsContent = '';

            if (!file_exists($this->getKrynCore()->getWebCacheDir() . $jsCachedFile)) {
                foreach ($this->js as $js) {
                    if ($js['position'] != $position) {
                        continue;
                    }

                    if ($js['path']) {
                        if (false !== strpos($js['path'], "://")) {
                            $result .= sprintf(
                                '<script type="%s" src="%s"></script>' . chr(10),
                                $js['type'],
                                $js['path']
                            );
                        } else {
                            $file = $this->getKrynCore()->resolvePath($js['path'], 'Resources/public');
                            $public = $this->getKrynCore()->resolveWebPath($js['path']);

                            if (file_exists($file)) {
                                $jsContent .= "/* ($public, {$js['path']}) - $file */\n\n";
                                $jsContent .= file_get_contents($file) . "\n\n\n";
                            }
                        }
                    } else {
                        $jsContent .= "/* javascript block */\n\n";
                        $jsContent .= $js['content'] . "\n\n\n";
                    }
                }
                $this->getKrynCore()->getWebFileSystem()->put('cache/' . $jsCachedFile, $jsContent);
            }

            $result .= '<script type="text/javascript" src="cache/' . $jsCachedFile . '" ></script>' . "\n";
        } else {
            foreach ($this->js as $js) {
                if ($js['position'] != $position) {
                    continue;
                }

                if (isset($js['path'])) {
                    if (false !== strpos($js['path'], "://")) {
                        $result .= sprintf('<script type="%s" src="%s"></script>' . chr(10), $js['type'], $js['path']);
                    } else {
                        $file = $this->getKrynCore()->resolvePath($js['path'], 'Resources/public');
                        $public = $this->getKrynCore()->resolveWebPath($js['path']);

                        $modifiedTime = file_exists($file) ? filemtime($file) : null;

                        $result .= sprintf(
                            '<script type="%s" src="%s"></script>' . chr(10),
                            $js['type'],
                            $public . ($modifiedTime ? '?c=' . $modifiedTime : '')
                        );
                    }
                } else {
                    $result .= sprintf(
                        '<script type="%s">' . chr(10) . '%s' . chr(10) . '</script>' . chr(10),
                        $js['type'],
                        $js['content']
                    );
                }
            }
        }

        return $result;

    }

}
