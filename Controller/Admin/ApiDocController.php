<?php

namespace Kryn\CmsBundle\Controller\Admin;

use Kryn\CmsBundle\Extractor\ApiDocExtractor;
use Kryn\CmsBundle\Formatter\ApiDocFormatter;
use Nelmio\ApiDocBundle\Formatter\HtmlFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class ApiDocController extends Controller
{
    /**
     * @ApiDoc(
     *  section="Administration",
     *  description="REST API documentation"
     * )
     *
     * @Rest\Get("/doc", name="kryn_cms_api_doc_index")
     *
     * @return Response
     */
    public function indexAction()
    {
        $commentExtractor = new \Nelmio\ApiDocBundle\Util\DocCommentExtractor;

        $handlers = [
            new \Kryn\CmsBundle\Extractor\Handler\ObjectCrudHandler($this->get('kryn_cms')),
            new \Nelmio\ApiDocBundle\Extractor\Handler\FosRestHandler,
            new \Nelmio\ApiDocBundle\Extractor\Handler\JmsSecurityExtraHandler,
            new \Nelmio\ApiDocBundle\Extractor\Handler\SensioFrameworkExtraHandler,
//            new \Nelmio\ApiDocBundle\Extractor\Handler\ClassicPhpDocHandler($commentExtractor),
        ];

//        var_dump(iterator_to_array($this->container->get('router')->getRouteCollection()));
//        exit;

        //$extractor = new ApiDocExtractor(
        $extractor = new \Nelmio\ApiDocBundle\Extractor\ApiDocExtractor(
            $this->container,
            $this->container->get('router'),
            $this->container->get('annotation_reader'),
            $commentExtractor,
            $handlers
        );

//        var_dump($this->container->get('annotation_reader'));
//        exit;

        $extractedDoc = $extractor->all();

//        var_dump($extractedDoc);
//        exit;

        $formatter = new ApiDocFormatter();
        $formatter->setTemplatingEngine($this->get('templating'));

        $htmlContent = $formatter
            ->format($extractedDoc);

        return new Response($htmlContent, 200, array('Content-Type' => 'text/html'));
    }
}