<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 09.12.13
 * Time: 22:06
 */

namespace Kryn\CmsBundle\Extractor;

use Nelmio\ApiDocBundle\Extractor\ApiDocExtractor as NelmioApiDocExtractor;
use Doctrine\Common\Annotations\Reader;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Parser\ParserInterface;
use Nelmio\ApiDocBundle\Parser\PostParserInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Nelmio\ApiDocBundle\Util\DocCommentExtractor;


class ApiDocExtractor extends NelmioApiDocExtractor
{
    /**
     * @var DocCommentExtractor
     */
    protected $commentExtractor;

    public function __construct(ContainerInterface $container, RouterInterface $router, Reader $reader, DocCommentExtractor $commentExtractor, array $handlers)
    {
        $this->container        = $container;
        $this->router           = $router;
        $this->reader           = $reader;
        $this->commentExtractor = $commentExtractor;
        $this->handlers         = $handlers;
    }

    protected function extractData(ApiDoc $annotation, Route $route, \ReflectionMethod $method)
    {
        // create a new annotation
        $annotation = clone $annotation;

        // doc
        $annotation->setDocumentation($this->commentExtractor->getDocCommentText($method));

        // parse annotations
        $this->parseAnnotations($annotation, $route, $method);

        // route
        $annotation->setRoute($route);

//        // description
//        if (null === $annotation->getDescription()) {
//            $comments = explode("\n", $annotation->getDocumentation());
//            // just set the first line
//            $comment = trim($comments[0]);
//            $comment = preg_replace("#\n+#", ' ', $comment);
//            $comment = preg_replace('#\s+#', ' ', $comment);
//            $comment = preg_replace('#[_`*]+#', '', $comment);
//
//            if ('@' !== substr($comment, 0, 1)) {
//                $annotation->setDescription($comment);
//            }
//        }

        // input (populates 'parameters' for the formatters)
        if (null !== $input = $annotation->getInput()) {
            $parameters = array();

            $normalizedInput = $this->normalizeClassParameter($input);

            $supportedParsers = array();
            $parameters = array();
            foreach ($this->parsers as $parser) {
                if ($parser->supports($normalizedInput)) {
                    $supportedParsers[] = $parser;
                    $parameters = $this->mergeParameters($parameters, $parser->parse($normalizedInput));
                }
            }

            foreach ($supportedParsers as $parser) {
                if ($parser instanceof PostParserInterface) {
                    $mp = $parser->postParse($normalizedInput, $parameters);
                    $parameters = $this->mergeParameters($parameters, $mp);
                }
            }

            $parameters = $this->clearClasses($parameters);

            if ('PUT' === $method) {
                // All parameters are optional with PUT (update)
                array_walk($parameters, function ($val, $key) use (&$data) {
                        $parameters[$key]['required'] = false;
                    });
            }

            $annotation->setParameters($parameters);
        }

        // output (populates 'response' for the formatters)
        if (null !== $output = $annotation->getOutput()) {
            $response = array();

            $normalizedOutput = $this->normalizeClassParameter($output);

            foreach ($this->parsers as $parser) {
                if ($parser->supports($normalizedOutput)) {
                    $response = $this->mergeParameters($response, $parser->parse($normalizedOutput));
                }
            }
            $response = $this->clearClasses($response);

            $annotation->setResponse($response);
        }

//        // requirements
//        $requirements = array();
//        foreach ($route->getRequirements() as $name => $value) {
//            if ('_method' !== $name) {
//                $requirements[$name] = array(
//                    'requirement'   => $value,
//                    'dataType'      => '',
//                    'description'   => '',
//                );
//            }
//            if ('_scheme' == $name) {
//                $https = ('https' == $value);
//                $annotation->setHttps($https);
//            }
//        }
//
//        $paramDocs = array();
//        foreach (explode("\n", $this->commentExtractor->getDocComment($method)) as $line) {
//            if (preg_match('{^@param (.+)}', trim($line), $matches)) {
//                $paramDocs[] = $matches[1];
//            }
//            if (preg_match('{^@deprecated\b(.*)}', trim($line), $matches)) {
//                $annotation->setDeprecated(true);
//            }
//            if (preg_match('{^@link\b(.*)}', trim($line), $matches)) {
//                $annotation->setLink($matches[1]);
//            }
//        }
//
//        $regexp = '{(\w*) *\$%s\b *(.*)}i';
//        foreach ($route->compile()->getVariables() as $var) {
//            $found = false;
//            foreach ($paramDocs as $paramDoc) {
//                if (preg_match(sprintf($regexp, preg_quote($var)), $paramDoc, $matches)) {
//                    $requirements[$var]['dataType']    = isset($matches[1]) ? $matches[1] : '';
//                    $requirements[$var]['description'] = $matches[2];
//
//                    if (!isset($requirements[$var]['requirement'])) {
//                        $requirements[$var]['requirement'] = '';
//                    }
//
//                    $found = true;
//                    break;
//                }
//            }
//
//            if (!isset($requirements[$var]) && false === $found) {
//                $requirements[$var] = array('requirement' => '', 'dataType' => '', 'description' => '');
//            }
//        }
//
//        $annotation->setRequirements($requirements);

        return $annotation;
    }
} 