<?php

namespace Kryn\CmsBundle\Controller\Admin;

use RestService\InternalClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SymfonyClient extends InternalClient
{
    /**
     * @var Response
     */
    protected $response;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @param Response $response
     *
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Detect the method.
     *
     * @return string
     */
    public function getMethod()
    {
        if (isset($_GET['_method']))
            return $_GET['_method'];
        else if (isset($_POST['_method']))
            return $_POST['_method'];
        else
            return strtolower($this->request->getMethod());
    }

    public function sendResponse($pHttpCode = 200, $pMessage)
    {
        $res = parent::sendResponse($pHttpCode, $pMessage);
        $this->response->setStatusCode($pHttpCode+0);
        $this->response->setContent($res);
        $this->response->headers->set('Content-Type', 'application/json; charset=utf-8');
        return $this->response;
    }

}