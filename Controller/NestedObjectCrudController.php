<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 09.12.13
 * Time: 16:42
 */

namespace Kryn\CmsBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use FOS\RestBundle\Request\ParamFetcher;

class NestedObjectCrudController extends ObjectCrudController
{
    /**
     * @ApiDoc(
     *    description="Adds a new item (nested set)"
     * )
     *
     * @Rest\QueryParam(name="targetPk", requirements=".*", description="The target object item's primaryKey as url encoded string. Only for nested sets.")
     * @Rest\QueryParam(name="limit", requirements="[0-9]+", description="Limits the result")
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

        return $obj->add($data, $paramFetcher->get('targetPk'));
    }

    /**
     * @ApiDoc(
     *    description="Delete a object root"
     * )
     *
     * @Rest\View()
     * @Rest\Delete("/")
     *
     * @return boolean
     */
    public function removeRootAction()
    {
        $obj = $this->getObj();

        return '#todo';
        return $obj->removeRoot();
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
     *    description="Returns all the root branch (nested set)"
     * )
     *
     * @Rest\QueryParam(name="fields", requirements=".+", description="Comma separated list of field names")
     * @Rest\QueryParam(name="filter", array=true, requirements=".*", description="Simple filtering per field")
     * @Rest\QueryParam(name="limit", requirements="[0-9]+", description="Limits the result")
     * @Rest\QueryParam(name="offset", requirements="[0-9]+", description="Offsets the result")
     * @Rest\QueryParam(name="scope", requirements=".*", description="Nested set scope")
     * @Rest\QueryParam(name="depth", requirements="[0-9]+", default=1, description="Max depth")
     *
     * @Rest\View()
     * @Rest\Get("/:branch")
     *
     * @param string $fields
     * @param string $scope
     * @param integer $depth
     * @param string $limit
     * @param string $offset
     * @param string $filter
     *
     * @return array
     */
    public function getRootBranchItemsAction(
        $scope = null,
        $fields = null,
        $depth = null,
        $limit = null,
        $offset = null,
        $filter = null
    ) {
        $obj = $this->getObj();

        return $obj->getBranchItems(null, $filter, $fields, $scope, $depth, $limit, $offset);
    }

    /**
     * @ApiDoc(
     *    description="Returns a branch (nested set)"
     * )
     *
     * @Rest\QueryParam(name="fields", requirements=".+", description="Comma separated list of field names")
     * @Rest\QueryParam(name="filter", array=true, requirements=".*", description="Simple filtering per field")
     * @Rest\QueryParam(name="limit", requirements="[0-9]+", description="Limits the result")
     * @Rest\QueryParam(name="offset", requirements="[0-9]+", description="Offsets the result")
     * @Rest\QueryParam(name="scope", requirements=".*", description="Nested set scope")
     * @Rest\QueryParam(name="depth", requirements="[0-9]+", default=1, description="Max depth")
     *
     * @Rest\View()
     * @Rest\Get("/{pk}/:branch")
     *
     * @param string $pk
     * @param string $fields
     * @param string $scope
     * @param integer $depth
     * @param string $limit
     * @param string $offset
     * @param string $filter
     *
     * @return array
     */
    public function getBranchItemsAction(
        $pk = null,
        $fields = null,
        $scope = null,
        $depth = null,
        $limit = null,
        $offset = null,
        $filter = null
    ) {
        $obj = $this->getObj();

        $primaryKeys = $this->getKrynCore()->getObjects()->normalizePkString($obj->getObject(), $pk);

        return $obj->getBranchItems($primaryKeys, $filter, $fields, $scope, $depth, $limit, $offset);
    }

    /**
     * @ApiDoc(
     *    description="Returns a branch direct children count (nested set)"
     * )
     *
     * @Rest\QueryParam(name="filter", array=true, requirements=".*", description="Simple filtering per field")
     * @Rest\QueryParam(name="scope", requirements=".*", description="Nested set scope")
     *
     * @Rest\View()
     * @Rest\Get("/{pk}/:children-count")
     * @Rest\Get("/:children-count")
     *
     * @param string $pk
     * @param string $scope
     * @param string $filter
     *
     * @return array
     */
    public function getBranchChildrenCountAction($pk = null, $scope = null, $filter = null)
    {
        $obj = $this->getObj();

        if ($pk) {
            $primaryKeys = $this->getKrynCore()->getObjects()->normalizePkString($obj->getObject(), $pk);

            return $obj->getBranchChildrenCount($primaryKeys[0], $scope, $filter);
        } else {
            return $obj->getBranchChildrenCount(null, $scope, $filter);
        }

    }

    /**
     * @ApiDoc(
     *    description="Moves a item (nested set)"
     * )
     *
     * @Rest\QueryParam(name="target", requirements=".+", description="The target PK. Same structure as pk.")
     * @Rest\QueryParam(name="position", requirements="first|last|insert", default="first", description="The position")
     * @Rest\QueryParam(name="overwrite", requirements="true|false", default=false, description="If the target should be replaced when exist")
     *
     * @Rest\View()
     * @Rest\Post("/{pk}/:move")
     *
     * @param string $source
     * @param string $target
     * @param string $position
     * @param string $targetObjectKey
     * @param bool $overwrite
     *
     * @return boolean
     */
    public function moveItemAction($source, $target, $position = 'first', $targetObjectKey = '', $overwrite = false)
    {
        $obj = $this->getObj();

        return $obj->moveItem(
            $source,
            $target,
            $position,
            $targetObjectKey,
            filter_var($overwrite, FILTER_VALIDATE_BOOLEAN)
        );

    }

    /**
     * @ApiDoc(
     *    description="Returns all roots items (nested set)"
     * )
     *
     * @Rest\View()
     * @Rest\Get("/:roots")
     *
     * @return mixed
     */
    public function getRootsAction()
    {
        $obj = $this->getObj();

        return $obj->getRoots();
    }

    /**
     * @ApiDoc(
     *    description="Returns the root item (for a scope) (nested set)"
     * )
     *
     * @Rest\QueryParam(name="scope", requirements=".+", description="The scope of the root item, if available.")
     *
     * @Rest\View()
     * @Rest\Get("/:root")
     *
     * @param string $scope
     *
     * @return mixed
     */
    public function getRootAction($scope = null)
    {
        $obj = $this->getObj();

        return $obj->getRoot($scope);
    }

    /**
     * @ApiDoc(
     *    description="Returns the parent (nested set)"
     * )
     *
     * @Rest\View()
     * @Rest\Get("/{pk}/:parent")
     *
     * @param string $pk
     *
     * @return boolean
     */
    public function getParentAction($pk)
    {
        $obj = $this->getObj();

        $primaryKeys = $this->getKrynCore()->getObjects()->parsePk($obj->getObject(), $pk);

        return $obj->getParent($primaryKeys[0]);
    }

    /**
     * @ApiDoc(
     *    description="Returns all parents (nested set)"
     * )
     *
     * @Rest\View()
     * @Rest\Get("/{pk}/:parents")
     *
     * @param string $pk
     *
     * @return boolean
     */
    public function getParentsAction($pk)
    {
        $obj = $this->getObj();

        $primaryKeys = $this->getKrynCore()->getObjects()->parsePk($obj->getObject(), $pk);

        return $obj->getParents($primaryKeys[0]);
    }

} 