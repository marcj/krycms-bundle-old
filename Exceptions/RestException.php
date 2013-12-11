<?php

namespace Kryn\CmsBundle\Exceptions;


class RestException extends \Exception
{

    /**
     * @var mixed
     */
    protected $data;

    /**
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

} 