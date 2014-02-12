<?php

namespace Kryn\CmsBundle\AssetHandler;

class AssetInfo
{
    /**
     * @var string
     */
    public $file;

    /**
     * @var string
     */
    public $content;

    /**
     * Mimetype
     *
     * @var string
     */
    public $contentType;

    /**
     * Additional information
     *
     * @var array
     */
    protected $additionalData;

    /**
     * @param string $key
     * @param $data
     */
    public function set($key, $data)
    {
        $this->additionalData[$key] = $data;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        return @$this->additionalData($key);
    }

    /**
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $contentType
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * @param string $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

}