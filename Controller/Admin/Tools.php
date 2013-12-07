<?php

namespace Kryn\CmsBundle\Controller\Admin;

use Kryn\CmsBundle\Model\Base\LogQuery;
use Kryn\CmsBundle\Model\LogRequestQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Map\TableMap;

class Tools
{

    public function getLogs($requestId, $level = 'all')
    {
        $query = LogQuery::create()
            ->filterByRequestId($requestId)
            ->orderByDate(Criteria::DESC);

        if ('all' !== $level) {
            $query->filterByLevel($level);
        }

//        $count = ceil($query->count() / 50) ? : 0;
//        $paginate = $query->paginate($page, 50);

        $items = $query
            ->find()
            ->toArray(null, null, TableMap::TYPE_STUDLYPHPNAME);

        return [
            'items' => $items,
//            'maxPages' => $count
        ];
    }

    public function getLogRequests($page = 1)
    {
        $query = LogRequestQuery::create()
            ->orderByDate(Criteria::DESC);

        $count = ceil($query->count() / 50) ? : 0;
        $paginate = $query->paginate($page, 50);

        $items = $paginate
            ->getResults()
            ->toArray(null, null, TableMap::TYPE_STUDLYPHPNAME);

        return [
            'items' => $items,
            'maxPages' => $count
        ];
    }

    public function clearLogs()
    {
        return LogQuery::create()->deleteAll() + LogRequestQuery::create()->deleteAll()
            + LogRequestQuery::create()->deleteAll() + LogRequestQuery::create()->deleteAll();
    }

}
