<?php

namespace Kryn\CmsBundle\Controller;

use FOS\RestBundle\Request\ParamFetcher;
use Kryn\CmsBundle\Admin\ObjectCrud;
use Kryn\CmsBundle\Configuration\EntryPoint;
use Kryn\CmsBundle\Core;
use Kryn\CmsBundle\Exceptions\InvalidArgumentException;
use RestService\Server;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * RestController for the entry points which are from type store or framework window.
 *
 */
class ObjectCrudController extends ObjectCrud
{
    /**
     * @var EntryPoint
     */
    public $entryPoint;


    protected $obj;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    public function detectObjectKeyFromPathInfo()
    {
        $path =$this->getRequest()->getPathInfo();
        preg_match('|/object/([^/]+)/([^/]+)|', $path, $matches);
        if (!isset($matches[1]) || !$matches[2]) {
            throw new InvalidArgumentException(sprintf('Object key not detectable in uri `%s`.', $path));
        }
        $bundleName = $matches[1];
        $objectName = $matches[2];
        return $bundleName.'/'.$objectName;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return \Kryn\CmsBundle\Core
     */
    public function getKrynCore()
    {
        return $this->container->get('kryn_cms');
    }

    public function setEntryPoint(EntryPoint $entryPoint)
    {
        $this->entryPoint = $entryPoint;
    }

    public function getVersion($pk, $id)
    {
        //todo
    }

    public function getVersions($pk)
    {
        //todo
    }

    /**
     * @ApiDoc(
     *    description="Returns a single object item"
     * )
     *
     * @Rest\QueryParam(name="fields", requirements=".+", description="Fields to select")
     * @Rest\QueryParam(name="withAcl", requirements=".+", default=false, description="With ACL information")
     *
     * @Rest\View()
     * @Rest\Get("/{pk}", requirements={"pk" = ".+"})
     *
     * @param string $pk
     * @param string $fields
     * @param boolean $withAcl
     *
     * @return array
     */
    public function getItemAction($pk, $fields = null, $withAcl = null)
    {
        $obj = $this->getObj();

        $primaryKeys = $this->getKrynCore()->getObjects()->parsePk($obj->getObject(), $pk);
        $withAcl = filter_var($withAcl, FILTER_VALIDATE_BOOLEAN);

        if (count($primaryKeys) == 1) {
            return $obj->getItem($primaryKeys[0], $fields, $withAcl);
        } else {
            $items = [];
            foreach ($primaryKeys as $primaryKey) {
                if ($item = $obj->getItem($primaryKey, $fields, $withAcl)) {
                    $items[] = $item;
                }
            }

            return $items;
        }
    }

    /**
     * @ApiDoc(
     *    description="Returns object items with additional information"
     * )
     *
     * @Rest\QueryParam(name="fields", requirements=".+", description="Comma separated list of field names")
     * @Rest\QueryParam(name="filter", array=true, requirements=".*", description="Simple filtering per field")
     * @Rest\QueryParam(name="limit", requirements="[0-9]+", description="Limits the result")
     * @Rest\QueryParam(name="offset", requirements="[0-9]+", description="Offsets the result")
     * @Rest\QueryParam(name="order", array=true, requirements=".+", description="Ordering. ?order[title]=asc")
     * @Rest\QueryParam(name="q", requirements=".+", description="Search query")
     * @Rest\QueryParam(name="withAcl", default=false, requirements=".+", description="With ACL information")
     *
     * @Rest\View()
     * @Rest\Get("/")
     *
     * @param ParamFetcher $paramFetcher
     *
     * @return mixed
     */
    public function getItemsAction(ParamFetcher $paramFetcher)
    {
        $obj = $this->getObj();

        return $obj->getItems($paramFetcher->get('filter'),
            $paramFetcher->get('limit'),
            $paramFetcher->get('offset'),
            $paramFetcher->get('q'),
            $paramFetcher->get('fields'),
            $paramFetcher->get('order'),
            $paramFetcher->get('withAcl')
        );
    }

    /**
     * @ApiDoc(
     *    description="Updates a object item"
     * )
     *
     * @Rest\View()
     * @Rest\Put("/{pk}")
     *
     * @param string $pk
     *
     * @return mixed
     */
    public function updateItemAction($pk)
    {
        $obj = $this->getObj();

        $primaryKeys = $this->getKrynCore()->getObjects()->parsePk($obj->getObject(), $pk);

        return $obj->update($primaryKeys[0]);
    }

    /**
     * @ApiDoc(
     *    description="Updates/Patches a object item"
     * )
     *
     * @Rest\View()
     * @Rest\Patch("/{pk}")
     *
     * @param string $pk
     *
     * @return mixed
     */
    public function patchItemAction($pk)
    {
        $obj = $this->getObj();

        $primaryKeys = $this->getKrynCore()->getObjects()->parsePk($obj->getObject(), $pk);

        return $obj->patch($primaryKeys[0]);
    }

    /**
     * @ApiDoc(
     *    description="Returns object items count"
     * )
     *
     * @Rest\QueryParam(name="filter", array=true, requirements=".*", description="Simple filtering per field")
     * @Rest\QueryParam(name="q", requirements=".+", description="Search query")
     *
     * @Rest\View()
     * @Rest\Get("/:count")
     *
     * @param array $filter
     * @param string $q
     *
     * @return integer
     */
    public function getCountAction($filter = null, $q = null)
    {
        $obj = $this->getObj();

        return $obj->getCount($filter, $q);
    }

    /**
     * @ApiDoc(
     *    description="Delete a object item"
     * )
     *
     * @Rest\View()
     * @Rest\Delete("/{pk}")
     *
     * @param string $pk
     *
     * @return boolean
     */
    public function removeItemAction($pk = null)
    {
        $obj = $this->getObj();

        if ($pk) {
            $primaryKeys = $this->getKrynCore()->getObjects()->parsePk($obj->getObject(), $pk);
            return $obj->remove($primaryKeys[0]);
        } else {
            return $obj->removeRoot();
        }
//
//        if (count($primaryKeys) > 0) {
//            $result = false;
//            foreach ($pk as $item) {
//                $result |= $this->removeItem($item);
//            }
//
//            return (boolean)$result;
//        }
    }

    /**
     * @ApiDoc(
     *    description="Adds a new item"
     * )
     *
     * @Rest\View()
     * @Rest\Post("/")
     *
     * @param ParamFetcher $paramFetcher
     * Proxy method for REST POST to add().
     *
     * @return mixed
     */
    public function addItemAction(ParamFetcher $paramFetcher)
    {
        $obj = $this->getObj();

        $data = null;
        return $obj->add($data);
    }

    /**
     * Proxy method for REST POST to add().
     *
     * @return mixed
     */

    /**
     * @ApiDoc(
     *    description="Adds multiple items #todo-doc"
     * )
     *
     * @Rest\View()
     * @Rest\Post("/:multiple")
     *
     * @return mixed
     */
    public function addMultipleItemAction()
    {
        $obj = $this->getObj();

        return $obj->addMultiple();
    }

    /**
     * @ApiDoc(
     *    description="Returns the position in the object items list of $pk"
     * )
     *
     * @Rest\View()
     * @Rest\Get("/{pk}/:position")
     *
     * @param string $pk
     * @return array
     */
    public function getItemPositionAction($pk)
    {
        $obj = $this->getObj();
        $primaryKeys = $this->getKrynCore()->getObjects()->parsePk($obj->getObject(), $pk);

        return $obj->getPosition($primaryKeys[0]);
    }

    /**
     * Returns the class object, depended on the current entryPoint.
     *
     * @return ObjectCrudController
     * @throws \Exception
     */
    public function getObj()
    {
        $obj = $this;
        $obj->setKrynCore($this->container->get('kryn_cms'));
        $obj->setRequest($this->container->get('request'));
        $obj->initialize();

        return $obj;

    }

    /**
     * @param ObjectCrudController $obj
     */
    public function setObj($obj)
    {
        $this->obj = $obj;
    }

}
