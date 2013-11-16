<?php

namespace Kryn\CmsBundle\Configuration;

class Errors extends Model
{
    protected $docBlock = 'error handling';
    protected $docBlocks = [
        'display' => 'If the system should print error messages to the client. DEACTIVATE THIS IN PRODUCTIVE SYSTEMS!',
        'log' => 'If the system should log messages.',
        'displayRest' => 'If the system should print error message from the RESTful JSON API to the client. DEACTIVATE THIS IN PRODUCTIVE SYSTEMS!',
        'stackTrace' => '
    If the system should print a prettified stackTrace with codeHighlighting in the error message.
    This included the stackTrace in the RESTful JSON API (displayRest).
    ',
    ];

    /**
     * @var bool
     */
    protected $display = false;

    /**
     * @var bool
     */
    protected $log = false;

    /**
     * @var bool
     */
    protected $displayRest = false;

    /**
     * @var bool
     */
    protected $stackTrace = false;

    /**
     * @param boolean $display
     */
    public function setDisplay($display)
    {
        $this->display = $this->bool($display);
    }

    /**
     * @return boolean
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * @param boolean $displayRest
     */
    public function setDisplayRest($displayRest)
    {
        $this->displayRest = $this->bool($displayRest);
    }

    /**
     * @return boolean
     */
    public function getDisplayRest()
    {
        return $this->displayRest;
    }

    /**
     * @param boolean $log
     */
    public function setLog($log)
    {
        $this->log = $this->bool($log);
    }

    /**
     * @return boolean
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * @param boolean $stackTrace
     */
    public function setStackTrace($stackTrace)
    {
        $this->stackTrace = $this->bool($stackTrace);
    }

    /**
     * @return boolean
     */
    public function getStackTrace()
    {
        return $this->stackTrace;
    }


}