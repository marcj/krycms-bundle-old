<?php

namespace Kryn\CmsBundle\AssetHandler;

interface CompileHandlerInterface
{
    /**
     * Compiles a file path to an AssetInfo object.
     *
     * Use $this->resolvePath or $this->resolvePublicPath from AbstractHandler to resolve
     * $assetPath.
     *
     * @param string $assetPath might be a symfony path with @ (e.g. @KrynDemoTheme/base.css.scss)
     * @return AssetInfo
     */
    public function compileFile($assetPath);
}