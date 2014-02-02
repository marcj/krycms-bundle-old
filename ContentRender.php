<?php

/*
 * This file is part of Kryn.cms.
 *
 * (c) Kryn.labs, MArc Schmidt <marc@Kryn.org>
 *
 * To get the full copyright and license information, please view the
 * LICENSE file, that was distributed with this source code.
 *
 */

namespace Kryn\CmsBundle;

use Kryn\CmsBundle\Model\Base\ContentQuery;
use Kryn\CmsBundle\Model\Content;
use Kryn\CmsBundle\ContentTypes\TypeNotFoundException;
use Propel\Runtime\Map\TableMap;
use Symfony\Component\EventDispatcher\GenericEvent;

class ContentRender
{
    /**
     * Cache of the current contents stage.
     *
     * @var array
     */
    public $contents;

    /**
     * @var Core
     */
    private $krynCore;

    /**
     * @var StopwatchHelper
     */
    private $stopwatch;

    private $cachedSlotContents = array();

    /**
     * @param Core $krynCore
     * @param StopwatchHelper $stopwatch
     */
    function __construct(Core $krynCore, StopwatchHelper $stopwatch)
    {
        $this->krynCore = $krynCore;
        $this->stopwatch = $stopwatch;
    }

    /**
     * @param Core $krynCore
     */
    public function setKrynCore(Core $krynCore)
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
     * @param integer $nodeId
     * @param integer $slotId
     * @param array   $params
     *
     * @return string
     */
    public function renderSlot($nodeId, $slotId = 1, $params = array())
    {
        if ($this->getKrynCore()->isEditMode()) {
            return '<div class="ka-slot" params="' . htmlspecialchars(json_encode($params)) . '"></div>';
        }

        $contents =& $this->getSlotContents($nodeId, $slotId);
        return $this->renderContents($contents, $params);
    }

    /**
     * @param integer $nodeId
     * @param integer $slotId
     * @return Model\Content[]
     */
    public function &getSlotContents($nodeId, $slotId)
    {
        if (!isset($this->cachedSlotContents[$nodeId.'.'.$slotId])){

            $cacheKey = 'core/contents/' . $nodeId . '.' . $slotId;
            $cache = $this->getKrynCore()->getDistributedCache($cacheKey);
            $contents = null;

            if (!$cache) {
                $contents = ContentQuery::create()
                    ->filterByNodeId($nodeId)
                    ->filterByBoxId($slotId)
                    ->orderByRank()
                    ->find();

                $this->getKrynCore()->setDistributedCache($cacheKey, serialize($contents));
            }

            $this->cachedSlotContents[$nodeId.'.'.$slotId] = $contents ? : unserialize($cache);
        }

        return $this->cachedSlotContents[$nodeId.'.'.$slotId];
    }

    public function renderView(&$contents, $view)
    {
        return json_encode(iterator_to_array($contents));
    }

    /**
     * Build HTML for given contents.
     *
     * @param array $contents
     * @param array $slotProperties
     *
     * @return string
     * @internal
     */
    public function renderContents(&$contents, $slotProperties)
    {
        $title = sprintf('Slot %s [%d]', $slotProperties['name'], $slotProperties['id']);
        $this->stopwatch->start($title, 'Kryn');

        $filteredContents = array();
        if (!($contents instanceof \Traversable)) {
            return;
        }

        /** @var $content Content */
        foreach ($contents as $content) {
            $access = true;

            if (
                ($content->getAccessFrom() + 0 > 0 && $content->getAccessFrom() > time()) ||
                ($content->getAccessTo() + 0 > 0 && $content->getAccessTo() < time())
            ) {
                $access = false;
            }

            if ($content->getHide()) {
                $access = false;
            }

            if ($access && $content->getAccessFromGroups()) {

                $access = false;
                $groups = ',' . $content->getAccessFromGroups() . ',';

                $userGroups = $this->getKrynCore()->getClient()->getUser()->getUserGroups();

                foreach ($userGroups as $group) {
                    if (strpos($groups, ',' . $group->getGroupId() . ',') !== false) {
                        $access = true;
                        break;
                    }
                }

                if (!$access) {
                    $adminGroups = $this->getKrynCore()->getClient()->getUser()->getUserGroups();
                    foreach ($adminGroups as $group) {
                        if (strpos($groups, ',' . $group->getGroupId() . ',') !== false) {
                            $access = true;
                            break;
                        }
                    }
                }
            }

            if ($access) {
                $filteredContents[] = $content;
            }
        }

        $count = count($filteredContents);
        /*
         * Compatibility
         */
        $data['layoutContentsMax'] = $count;
        $data['layoutContentsIsFirst'] = true;
        $data['layoutContentsIsLast'] = false;
        $data['layoutContentsId'] = $slotProperties['id'];
        $data['layoutContentsName'] = $slotProperties['name'];

        $i = 0;

        //$oldContent = $tpl->getTemplateVars('content');
        $this->getKrynCore()->getEventDispatcher()->dispatch('core/render/slot/pre', new GenericEvent($data));

        $html = '';

        if ($count > 0) {
            foreach ($filteredContents as &$content) {
                if ($i == $count) {
                    $data['layoutContentsIsLast'] = true;
                }

                if ($i > 0) {
                    $data['layoutContentsIsFirst'] = false;
                }

                $i++;
                $data['layoutContentsIndex'] = $i;

                $html .= $this->renderContent($content, $data);

            }
        }

        $argument = array($data, &$html);
        $this->getKrynCore()->getEventDispatcher()->dispatch('core/render/slot', new GenericEvent($argument));

//        if ($slotProperties['assign'] != "") {
//            //Kryn::getInstance()->assign($slotProperties['assign'], $html);
//            return '';
//        }

        $this->stopwatch->stop($title, 'Kryn');

        return $html;
    }

    /**
     * @param string $type
     * @return ContentTypes\TypeInterface
     */
    public function getTypeRenderer($type)
    {
        $contentTypes = $this->getKrynCore()->getContentTypes();
        return $contentTypes->getType($type);
    }

    /**
     * Build HTML for given content.
     *
     * @param Content $content
     * @param array   $parameters
     *
     * @return string
     * @throws ContentTypes\TypeNotFoundException
     */
    public function renderContent(Content $content, $parameters = array())
    {
        $type = $content->getType();
        $title = sprintf('Content %d [%s]', $content->getId(), $type);
        $this->stopwatch->start($title, 'Kryn');

        $typeRenderer = $this->getTypeRenderer($type);
        if (!$typeRenderer) {
            $this->stopwatch->stop($title);
            throw new TypeNotFoundException(sprintf(
                'Type renderer for `%s` not found. [%s]',
                $content->getType(),
                json_encode($content)
            ));
        }
        $typeRenderer->setContent($content);
        $typeRenderer->setParameters($parameters);

        $html = $typeRenderer->render();

        $data['content'] = $content->toArray(TableMap::TYPE_STUDLYPHPNAME);
        $data['parameter'] = $parameters;
        $data['html'] = $html;

        $this->getKrynCore()->getEventDispatcher()->dispatch('core/render/content/pre', new GenericEvent($data));

        $unsearchable = false;
        if ((!is_array($content->getAccessFromGroups()) && $content->getAccessFromGroups() != '') ||
            (is_array($content->getAccessFromGroups()) && count($content->getAccessFromGroups()) > 0) ||
            ($content->getAccessFrom() > 0 && $content->getAccessFrom() > time()) ||
            ($content->getAccessTo() > 0 && $content->getAccessTo() < time()) ||
            $content->getUnsearchable()
        ) {
            $unsearchable = true;
        }

        if ($content->getTemplate() == '' || $content->getTemplate() == '-') {
            if ($unsearchable) {
                $result = '<!--unsearchable-begin-->' . $data['html'] . '<!--unsearchable-end-->';
            }
        } else {
            $template = $this->getKrynCore()->getTemplating();
            $result = $template->render($content->getTemplate(), $data);

            if ($unsearchable) {
                $result = '<!--unsearchable-begin-->' . $result . '<!--unsearchable-end-->';
            }
        }

        $argument = array(&$result, $data);
        $this->getKrynCore()->getEventDispatcher()->dispatch('core/render/content', new GenericEvent($argument));

        $this->stopwatch->stop($title);
        return $result;
    }

}
