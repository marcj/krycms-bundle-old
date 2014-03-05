<?php

namespace Kryn\CmsBundle\Controller\Admin;
use Kryn\CmsBundle\Controller;
use Kryn\CmsBundle\Model\NewsFeedQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Map\TableMap;

class DashboardWidgets extends Controller
{
    public function newsFeed(&$response, $params)
    {
        $items = NewsFeedQuery::create()
            ->orderByCreated(Criteria::ASC);

        if ($lastTime = @$params['newsFeed/lastTime']) {
            $items->filterByCreated($lastTime, Criteria::GREATER_THAN);
        }

        $result = [
            'time' => time(),
            'items' => $items->find()->toArray(null, null, TableMap::TYPE_STUDLYPHPNAME)
        ];

        $response['kryncms/newsFeed'] = $result;
    }

    public function load(&$response)
    {
        $load = function_exists('sys_getloadavg') ? \sys_getloadavg() : '';

        $response['KrynCmsBundle/load'] = array(
            'load' => $load,
            'os' => PHP_OS,
            'ram' => array(
                'used' => self::getRamUsed(),
                'size' => self::getRamSize()
            ),
            'cpu' => self::getCpuUsage()
        );
    }

    public function analytics(&$response)
    {
        //todo
    }

    public function space(&$response)
    {
        $response['KrynCmsBundle/space'] = self::getSpace();
    }

    public function apc(&$response)
    {
        $res = function_exists('apc_sma_info') ? apc_sma_info(true) : false;
        $response['KrynCmsBundle/apc'] = $res;
    }

    public function uptime(&$response)
    {
        $uptime = `uptime`;
        $matches = array();
        preg_match('/up ([^,]*),/', $uptime, $matches);
        $response['KrynCmsBundle/uptime'] = $matches[1];
    }

    public function getSpace()
    {

        $matches = array();
        if ('darwin' == strtolower(PHP_OS)) {
            $sysctl = `df -kl`;
            preg_match_all(
                '/([a-zA-Z0-9\/]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9%]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9%]+)\s+(.*)/',
                $sysctl,
                $matches,
                PREG_SET_ORDER
            );

            $availIdx = 4;
            $usedIdx = 3;
            $nameIdx = 9;
        } else if ('linux' === strtolower(PHP_OS)) {
            $sysctl = `df -l --block-size=1K`;
            preg_match_all(
                '/([a-zA-Z0-9\/]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9%]+)\s+(.*)/',
                $sysctl,
                $matches,
                PREG_SET_ORDER
            );

            $availIdx = 4;
            $usedIdx = 3;
            $nameIdx = 6;
        }

        $result = array();
        $blacklist = array('/boot', '/dev', '/run', '/run/lock', '/run/shm');
        foreach ($matches as $match) {

            if (count($result) > 2) {
                break;
            }

            $avail = $match[$availIdx] + 0;
            $user = $match[$usedIdx] + 0;
            $name = $match[$nameIdx];
            if (in_array($name, $blacklist)) {
                continue;
            }

            //anything under 1gb
            if (1000 * 1024 > $avail) {
                continue;
            }

            $result[$name] = array(
                'name' => '/' === $name ? '/' : basename($name),
                'used' => $user,
                'available' => $avail,
                'size' => $user + $avail
            );
        }
        return array_values($result) ? : array();
    }

    /**
     * @return integer kB
     */
    public function getRamUsed()
    {
        $cpuUsage = str_replace(' ', '', `ps -A -o rss`);
        $processes = explode("\n", $cpuUsage);
        $ramSize = array_sum($processes);
        return $ramSize;
    }

    public function latency(&$response)
    {
        $lastLatency = $this->getFastCache()->get('core/latency');

        $result = array(
            'frontend' => 0,
            'backend' => 0,
            'database' => 0,
            'session' => 0,
            'cache' => 0
        );
        foreach ($result as $key => &$value) {
            if (isset($lastLatency[$key])) {
                $value = round((array_sum($lastLatency[$key]) / count($lastLatency[$key])) * 1000);
            }
        }
        $response['KrynCmsBundle/latency'] = $result;
    }

    public function latencies(&$response)
    {
        $lastLatency = $this->getFastCache()->get('core/latency');
        $result = array(
            'frontend' => 0,
            'backend' => 0,
            'database' => 0,
            'session' => 0,
            'cache' => 0
        );
        foreach ($result as $key => &$value) {
            $value = isset($lastLatency[$key]) ? $lastLatency[$key] : array();
        }
        $response['KrynCmsBundle/latencies'] = $result;
    }

    /**
     * @return int kB
     */
    public function getRamSize()
    {
        if ('darwin' == strtolower(PHP_OS)) {
            $sysctl = `sysctl hw.memsize`;
            $matches = array();
            preg_match('/hw.memsize: ([0-9\.]*)/', $sysctl, $matches);
            return ($matches[1] + 0) / 1024;
        } else if ('linux' === strtolower(PHP_OS)) {
            $sysctl = `free`;
            $matches = array();
            preg_match('/Mem:\s+([0-9\.]*)/', $sysctl, $matches);
            return ($matches[1] + 0);
        }

        return 1;
    }

    public function getCpuCoreCount()
    {
        if ('darwin' === strtolower(PHP_OS)) {
            $sysctl = `sysctl hw.ncpu`;
            $matches = array();
            preg_match('/hw.ncpu: ([0-9\.]*)/', $sysctl, $matches);
            return $matches[1] + 0;
        } else if ('linux' === strtolower(PHP_OS)) {
            return (`nproc`) + 0;
        }

        return 1;
    }


    public function getCpuUsage()
    {
        $cpuUsage = str_replace(' ', '', `ps -A -o %cpu`);
        $processes = explode("\n", $cpuUsage);
        $cpuUsage = array_sum($processes);
        return $cpuUsage / self::getCpuCoreCount();
    }

}