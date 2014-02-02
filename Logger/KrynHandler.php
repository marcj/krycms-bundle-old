<?php

namespace Kryn\CmsBundle\Logger;

use Kryn\CmsBundle\Core;
use Kryn\CmsBundle\Model\Log;
use Kryn\CmsBundle\Model\LogRequest;
use Monolog\Handler\AbstractProcessingHandler;

class KrynHandler extends AbstractProcessingHandler
{

    /**
     * @var Core
     */
    protected $krynCore;

    /**
     * @var bool
     */
    protected $inSaving = false;

    /**
     * @var array
     */
    protected $counts = [];

    /**
     * @var array
     */
    protected $logs = [];

    protected $logRequest;

    /**
     * @param Core $krynCore
     */
    function __construct(Core $krynCore)
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
     * @param array $logs
     */
    public function setLogs($logs)
    {
        $this->logs = $logs;
    }

    /**
     * @return array
     */
    public function getLogs()
    {
        return $this->logs;
    }

    /**
     * @param array $counts
     */
    public function setCounts($counts)
    {
        $this->counts = $counts;
    }

    /**
     * @return array
     */
    public function getCounts()
    {
        return $this->counts;
    }

    /**
     * Writes the record down to the log of the implementing handler
     *
     * @param  array $record
     * @return void
     */
    protected function write(array $record)
    {
        if ($this->inSaving) {
            //there was a log between our ->save() method
            return;
        }

        if (!isset($this->counts[$record['level']])) {
            $this->counts[$record['level']] = 0;
        }

        $this->counts[$record['level']+0]++;

        if ($record['level'] < 300) {
            return;
        }

        $this->inSaving = true;
        $log = new Log();
//
//        if ($record['level'] == 100 && $this->getKrynCore()->getSystemConfig()->getLogs(true)->getPerformance()) {
//            global $_start;
//            static $lastDebugPoint;
//            $timeUsed = round((microtime(true) - $_start) * 1000, 2);
//            $bytes = convertSize(memory_get_usage(true));
//            $last = $lastDebugPoint ? 'diff ' . round(
//                    (microtime(true) - $lastDebugPoint) * 1000,
//                    2
//                ) . 'ms' : '';
//            $lastDebugPoint = microtime(true);
//            $log->setPerformance("memory: $bytes, {$timeUsed}ms, last: $last");
//        }
//
        $log->setDate(microtime(true));
        $log->setLevel($record['level']);
        $log->setMessage($record['message']);
//        $this->getKrynCore()->getRequest()->get
//        $log->setUsername(
//            $userName = $this->getKrynCore()->getClient()->hasSession() && $this->getKrynCore()->getClient()->getUser(
//            ) ? $this->getKrynCore()->getClient()->getUser()->getUsername() : 'Guest'
//        );
        $log->setLogRequest($this->getLogRequest());
//
//        if ($record['level'] >= 400 || $record['level'] == 100) {
//            if ($this->getKrynCore()->getSystemConfig()->getLogs(true)->getStackTrace()) {
//                $stackTrace = debug_backtrace();
//                $log->setStackTrace(json_encode($stackTrace));
//            }
//        }
//
        $log->save();
        $this->inSaving = false;
    }

    /**
     * @return LogRequest
     */
    public function getLogRequest()
    {
        if (!$this->logRequest && $this->krynCore->getRequest()) {
//            if (!$this->krynCore->has('profiler')) {
//                $id = md5(mt_rand() . ':' . uniqid());
//            } else {
//                /** @var $profiler \Symfony\Component\HttpKernel\Profiler\Profiler */
//                $profiler = $this->krynCore->get('profiler');
//
////                var_dump($profiler->loadProfileFromResponse());
////                exit;
//                //$id = $profiler->ge
//            }
//
//            return $this->kernel->getContainer()->get('profiler')->loadProfileFromResponse($this->response);

            $this->logRequest = new LogRequest();
            $this->logRequest->setId(md5(mt_rand() . ':' . uniqid()));
            $this->logRequest->setDate(microtime(true));
            $this->logRequest->setIp($this->krynCore->getRequest()->getClientIp());
            $this->logRequest->setPath(substr($this->krynCore->getRequest()->getPathInfo(), 0, 254));
            $this->logRequest->setUsername(
                $this->krynCore->getClient() && $this->krynCore->getClient()->hasSession()
                && $this->krynCore->getClient()->getUser()
                    ? $this->krynCore->getClient()->getUser()->getUsername()
                    : 'Guest'
            );

//            if ($this->krynCore->getSystemConfig()->getLogs()->getClientInfo()) {
//                $this->logRequest->setRequestInformation((string)$this->krynCore->getRequest());
//            }

        }

        return $this->logRequest;
    }
}