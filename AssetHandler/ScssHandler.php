<?php

namespace Kryn\CmsBundle\AssetHandler;

use Kryn\CmsBundle\Tools;

class ScssHandler extends AbstractHandler implements CompileHandlerInterface
{
    public function compileFile($assetPath)
    {
        $localPath = $this->getAssetPath($assetPath);
        $publicPath = $this->getPublicAssetPath($assetPath);

        $scss = new \scssc();
        $scss->addImportPath(realpath(dirname($localPath)));

//        $content = file_get_contents($localPath);
//        try {
//            $compiled = $scss->compile($content);
//        } catch (\Exception $e) {
//            throw new \Exception(sprintf('Parse error in file `%s`', $assetPath), 0, $e);
//        }
        $options = [

        ];
        $parser = new \SassParser($options);
        $compiled = $parser->toCss($localPath);

        $targetPath = 'cache/scss/' . substr($publicPath, 0, strrpos($publicPath, '.'));

        $compiled = $this->replaceRelativePaths($publicPath, $targetPath, $compiled);
        $this->getKrynCore()->getWebFileSystem()->write($targetPath, $compiled);

        $assetInfo = new AssetInfo();
        $assetInfo->setFile($targetPath);
        $assetInfo->setContentType('text/css');
        return $assetInfo;
    }

    /**
     * @param string $from scss path
     * @param string $to css path
     * @param string $content
     * @return string
     */
    protected function replaceRelativePaths($from, $to, $content)
    {
        $relative = Tools::getRelativePath(dirname($from), dirname($to)) . '/';

        $content = preg_replace('/@import \'([^\/].*)\'/', '@import \'' . $relative . '$1\'', $content);
        $content = preg_replace('/@import "([^\/].*)"/', '@import "' . $relative . '$1"', $content);
        $content = preg_replace('/url\(\'([^\/][^\)]*)\'\)/', 'url(\'' . $relative . '$1\')', $content);
        $content = preg_replace('/url\(\"([^\/][^\)]*)\"\)/', 'url(\"' . $relative . '$1\")', $content);
        $content = preg_replace('/url\((?!data:image)([^\/\'].*)\)/', 'url(' . $relative . '$1)', $content);

        return $content;
    }
}