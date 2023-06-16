<?php

namespace App\Controller;

use App\Entity\DocumentaryStructures;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use App\Entity\DocumentaryStructureRelations;
use App\Controller\AbstractController\RelationsController;

/**
 * Class DocumentaryStructureRelationsController
 * @package App\Controller
 * @SWG\Tag(name="Documentary structure relations")
 */
class DocumentaryStructureRelationsController extends RelationsController
{

    /**
     * Show documentary structure relations by id.
     * @SWG\Response(
     *     response="200",
     *     description="Return documentary structure relations select by id.",
     *     @SWG\Schema(type="array", @SWG\Items(ref=@Model(type=DocumentaryStructureRelations::class)))
     * )
     * @SWG\Response(response="404", description="No documentary structure found.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="id",type="integer", in="path", description="Documentary structure id.")
     * @SWG\Parameter(name="origin",type="string", in="query",
     *                description="If documentary structure is the origin of relation.")
     * @Rest\QueryParam(name="origin", strict=true, requirements="true|false", allowBlank=false)
     * @Rest\Get(
     *      path="/documentary-structure-relations/{id}",
     *      name="app_documentary_structure_relations_show",
     *      requirements={"id"="\d+"}
     * )
     * @Rest\View
     * @param int $id Documentary structure id.
     * @param string $origin Documentary structure is the origin of relation.
     * @return View Documentary structure array related with this documentary structure.
     */
    public function showAction(int $id, string $origin) : View
    {
        return $this->commonShowAction(DocumentaryStructures::class, $id, $origin);
    }


    /**
     * Create new relation between two documentary structure.
     * @SWG\Response(
     *     response="200",
     *     description="Create an relation between two documentary structure.",
     *     @Model(type=DocumentaryStructureRelations::class)
     * )
     * @SWG\Response(response="404", description="No documentary structure or relation type not found.")
     * @SWG\Response(response="400", description="Bad request. Date not valid.")
     * @SWG\Response(response="409", description="Relation already exist.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="body", in="body", description="Documentary structure informations.",
     *     @SWG\Schema(type="object",
     *     @SWG\Property(property="originId", type="integer"),
     *     @SWG\Property(property="resultId", type="integer"),
     *     @SWG\Property(property="typeId", type="integer"),
     *     @SWG\Property(property="date", type="string")))
     *
     * @Rest\Post(path="/documentary-structure-relations",
     *     name="app_documentary_structure_relations_create")
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
     * @return View Documentary structure has just been created.
     */
    public function createAction(int $originId, int $resultId, int $typeId, string $startDate, ?string $endDate) : View
    {
        return $this->commonCreateAction(DocumentaryStructures::class,
            $originId, $resultId, $typeId, $startDate, $endDate);
    }

    /**
     * Delete documentary structure relation.
     * @SWG\Response(response="204", description="Documentary structure relation deleted.")
     * @SWG\Response(response="404", description="No documentary structure, relation or relation type found.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="originId",type="integer", in="path", description="Origin documentary structure id.")
     * @SWG\Parameter(name="resultId",type="integer", in="path", description="Result documentary structure id.")
     * @SWG\Parameter(name="typeId",type="integer", in="path", description="Relation type id.")
     * @Rest\Delete(
     *      path="/documentary-structure-relations/{originId}/{resultId}/{typeId}",
     *      name="app_documentary_structure_relations_delete",
     *      requirements={"originId"="\d+", "resultId"="\d+", "typeId"="\d+"}
     * )
     * @Rest\View
     * @param int $originId Documentary structure origin id of relation.
     * @param int $resultId Documentary structure result id of relation.
     * @param int $typeId Id of relation type.
     * @return View Information about action.
     */
    public function deleteAction(int $originId, int $resultId, int $typeId) : View
    {
        return $this->commonDeleteAction(DocumentaryStructures::class,
            $originId, $resultId, $typeId);
    }

}