<?php

namespace App\Controller;

use App\Common\Traits\RelationTypesTrait;
use App\Entity\RelationTypes;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;
use Swagger\Annotations as SWG;
use App\Controller\AbstractController\ESGBUController;
use Nelmio\ApiDocBundle\Annotation\Model;

/**
 * Class RelationTypesController
 * @package App\Controller
 * @SWG\Tag(name="Relation types")
 */
class RelationTypesController extends ESGBUController
{
    use RelationTypesTrait;

    /**
     * Show all relation types.
     * @SWG\Response(
     *     response="200",
     *     description="No relation type found.",
     *     @SWG\Schema(type="array", @SWG\Items(ref=@Model(type=RelationTypes::class)))
     * )
     * @SWG\Response(
     *     response="404",
     *     description="No relation type found.",
     * )
     * @Rest\Get(
     *      path = "/relation-types",
     *      name = "app_relation_types_list"
     * )
     * @Rest\View
     * @return View Array with all relation types.
     */
    public function listAction() : View
    {
        try
        {
            $relationTypes = $this->getAllRelationType();
            return $this->createView($relationTypes, Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

    /** Show relation type by id.
     * @SWG\Response(
     *     response="200",
     *     description="Return relation type select by id.",
     *     @Model(type=RelationTypes::class)
     * )
     * @SWG\Response(
     *     response="404",
     *     description="No relation type found.",
     * )
     * @SWG\Parameter(name="id",type="integer", in="path", description="Relation type id.")
     * @Rest\Get(
     *      path = "/relation-types/{id}",
     *      name = "app_relation_types_show",
     *      requirements = {"id"="\d+"}
     * )
     * @Rest\View
     * @param int $id Relation type id.
     * @return View Relation information.
     */
    public function showAction(int $id) : View
    {
        try
        {
            $relationType = $this->getRelationTypeById($id);
            return $this->createView($relationType, Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

}