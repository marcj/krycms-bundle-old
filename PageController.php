<?php

namespace Kryn\CmsBundle;

use Kryn\CmsBundle\Model\ContentQuery;
use Kryn\CmsBundle\Model\Node;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class PageController extends Controller
{
    /**
     * Cache for getPublicUrl().
     *
     * @var array
     */
    private static $cachedUrls = array();

    /**
     * Cache for the slot contents.
     *
     * @var array
     */
    private static $slotContents = array();

    /**
     * The current pageId which is in the render-process.
     *
     * @var integer
     */
    static $currentRenderPageId;

    /**
     * @param integer $pageId
     */
    public static function setCurrentRenderPage($pageId)
    {
        static::$currentRenderPageId = $pageId;
    }

    /**
     * @return integer
     */
    public static function getCurrentRenderPage()
    {
        return static::$currentRenderPageId;
    }

    /**
     * Build the page and return the Response of Core\Kryn::getResponse().
     *
     * @return Response
     */
    public function handle()
    {
        //is link
        if (Kryn::$page->getType() == 1) {
            $to = Kryn::$page->getLink();
            if (!$to) {
                Kryn::internalError(
                    t('Redirect failed'),
                    tf('Current page with title %s has no target link.', Kryn::$page->getTitle())
                );
            }

            if ($to + 0 > 0) {
                return new RedirectResponse(self::getPageUrl($to), 301);
            } else {
                header("HTTP/1.1 301 Moved Permanently");
                header("Location: $to");

                return new RedirectResponse($to, 301);
            }
        }

        return Kryn::getResponse();
    }

    /**
     * @param integer $pageId
     * @param integer $slotId
     * @return Model\Content[]
     */
    public static function getSlotContents($pageId, $slotId)
    {
        $cacheKey = 'core/contents/' . $pageId . '.' . $slotId;
        $cache = Kryn::getFastCache($cacheKey);
        $cacheCreated = Kryn::getCache($cacheKey . '.created');

        if (!$cache || $cache['created'] != $cacheCreated) {
            $contents = ContentQuery::create()
                ->filterByNodeId($pageId)
                ->filterByBoxId($slotId)
                ->orderByRank()
                ->find();

            $cache['data'] = serialize($contents);
            $cache['created'] = microtime();
            Kryn::setFastCache($cacheKey, $cache);
            Kryn::setCache($cacheKey . '.created', $cache['created']);
        }

        return $contents ? : unserialize($cache['data']);
    }

    /**
     * @param string $slotId
     * @param array $slotProperties
     * @return string
     */
    public static function getSlotHtml($slotId, $slotProperties)
    {
        return Render::getInstance(Kryn::$page->getId())->getRenderedSlot($slotId, $slotProperties);
    }

    /**
     * Returns the public url for the Core\Node object.
     *
     * @param  string $objectKey
     * @param  string $objectPk
     * @param  array  $plugin
     *
     * @return string
     */
    public static function getPublicUrl($objectKey, $objectPk, $plugin = null)
    {
        return Node::getUrl($objectPk['id'] + 0);
    }

    /**
     * Returns a permanent(301) redirectResponse object.
     *
     * @return RedirectResponse
     */
    public function redirectToStartPage()
    {
        $qs = $_SERVER['QUERY_STRING'];
        $response = new RedirectResponse(Kryn::getBaseUrl()  . ($qs ? '?'.$qs:''), 301);

        return $response;
    }
}
