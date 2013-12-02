<?php

namespace Kryn\CmsBundle\Controller\Admin;

use Core\Models\Base\LogQuery;
use Core\Models\Base\LogRequestQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Map\TableMap;

class Tools
{

    public function getRequest($request)
    {
        $request = LogRequestQuery::create()->findPk($request);

        if ($request) {
            $request = $request->toArray(TableMap::TYPE_STUDLYPHPNAME);
            if (is_resource($request['exceptions'])) {
                $request['exceptions'] = stream_get_contents($request['exceptions']);
            }
            if (is_resource($request['requestInformation'])) {
                $request['requestInformation'] = stream_get_contents($request['requestInformation']);
            }
            if (is_resource($request['responseInformation'])) {
                $request['responseInformation'] = stream_get_contents($request['responseInformation']);
            }
            if (is_resource($request['queries'])) {
                $request['queries'] = stream_get_contents($request['queries']);
            }
            return $request;
        }

        return null;
    }

    public function getLogs($page = 1, $level = 'all')
    {
        $query = LogQuery::create()
            ->orderByDate(Criteria::DESC);

        if ('all' !== $level) {
            $query->filterByLevel($level);
        }

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
        return LogQuery::create()->deleteAll() + LogRequestQuery::create()->deleteAll();
    }

}
