<?php

namespace App\Controller;

use App\Entity\EstablishmentRelations;
use FOS\RestBundle\View\View;
use App\Entity\Establishments;
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use App\Controller\AbstractController\RelationsController;

/**
 * Class EstablishmentRelationController
 * @package App\Controller
 * @SWG\Tag(name="Establishment relations")
 */
class EstablishmentRelationsController extends RelationsController
{

    /**
     * Show establishment relations by id.
     * @SWG\Response(
     *     response="200",
     *     description="Return establishment relations select by id.",
     *     @SWG\Schema(type="array", @SWG\Items(ref=@Model(type=EstablishmentRelations::class)))
     * )
     * @SWG\Response(response="404", description="No establishment found.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="id",type="integer", in="path", description="Establishment id.")
     * @SWG\Parameter(name="origin",type="string", in="query",
     *                description="If establishment is the origin of relation.")
     * @Rest\QueryParam(name="origin", strict=true, requirements="true|false", allowBlank=false)
     * @Rest\Get(
     *      path="/establishment-relations/{id}",
     *      name="app_establishment_relations_show",
     *      requirements={"id"="\d+"}
     * )
     * @Rest\View
     * @param int $id Establishment id.
     * @param string $origin Establishment is the origin of relation.
     * @return View Establishment array related with this establishment.
     */
    public function showAction(int $id, string $origin) : View
    {
        return $this->commonShowAction(Establishments::class, $id, $origin);
    }


    /**
     * Create new relation between two establishment.
     * @SWG\Response(
     *     response="200",
     *     description="Create an relation between two establishment.",
     *     @Model(type=EstablishmentRelations::class)
     * )
     * @SWG\Response(response="404", description="No establishment or relation type not found.")
     * @SWG\Response(response="400", description="Bad request. Date not valid.")
     * @SWG\Response(response="409", description="Relation already exists.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="body", in="body", description="Establishment informations.",
     *     @SWG\Schema(type="object",
     *     @SWG\Property(property="originId", type="integer"),
     *     @SWG\Property(property="resultId", type="integer"),
     *     @SWG\Property(property="typeId", type="integer"),
     *     @SWG\Property(property="date", type="string")))
     *
     * @Rest\Post(path="/establishment-relations", name="app_establishment_relations_create")
     * @Rest\RequestParam(name="originId", requirements="[0-9]*", nullable=false)
     * @Rest\RequestParam(name="resultId", requirements="[0-9]*", nullable=false)
     * @Rest\RequestParam(name="typeId", requirements="[0-9]*", nullable=false)
     * @Rest\RequestParam(name="startDate", requirements="^\d{4}\-(0?[1-9]|1[012])\-(0?[1-9]|[12][0-9]|3[01])$",
     *     nullable=false)
     * @Rest\RequestParam(name="endDate", requirements="^\d{4}\-(0?[1-9]|1[012])\-(0?[1-9]|[12][0-9]|3[01])$",
     *     nullable=true)
     * @Rest\View
     * @param int $originId Origin establishment id.
     * @param int $resultId Result establishment id.
     * @param int $typeId Id of type assigned to establishment relation.
     * @param string $startDate Date of relation creation.
     * @param string|null $endDate Date of relation end.
     * @return View Establishment has just been created.
     */
    public function createAction(int $originId, int $resultId, int $typeId, string $startDate, ?string $endDate) : View
    {
        return $this->commonCreateAction(Establishments::class,
            $originId, $resultId, $typeId, $startDate, $endDate);
    }

    /**
     * Delete establishment relation.
     * @SWG\Response(response="204", description="Establishment relation deleted.")
     * @SWG\Response(response="404", description="No establishement, relation or relation type found.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="originId",type="integer", in="path", description="Origin establishment id.")
     * @SWG\Parameter(name="resultId",type="integer", in="path", description="Result establishment id.")
     * @SWG\Parameter(name="typeId",type="integer", in="path", description="Relation type id.")
     * @Rest\Delete(
     *      path="/establishment-relations/{originId}/{resultId}/{typeId}",
     *      name="app_establishment_relations_delete",
     *     requirements={"originId"="\d+", "resultId"="\d+", "typeId"="\d+"}
     * )
     * @Rest\View
     * @param int $originId Establishment origin id of relation.
     * @param int $resultId Establishment result id of relation.
     * @param int $typeId Id of relation type.
     * @return View Information about action.
     */
    public function deleteAction(int $originId, int $resultId, int $typeId) : View
    {
        return $this->commonDeleteAction(Establishments::class, $originId, $resultId, $typeId);
    }

}