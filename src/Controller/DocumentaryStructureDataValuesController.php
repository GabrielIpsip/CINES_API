<?php

namespace App\Controller;

use App\Entity\DocumentaryStructures;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use App\Controller\AbstractController\DataValuesController;

/**
 * Class DocumentaryStructureDataValuesController
 * @package App\Controller
 * @SWG\Tag(name="Documentary structure data values")
 */
class DocumentaryStructureDataValuesController extends DataValuesController
{
    /**
     * Show all data value between documentary structure and survey.
     *
     * @SWG\Response(
     *     response="200",
     *     description="Return all data values of survey for a documentary structure.",
     *     @SWG\Schema(type="object",
     *          @SWG\Property(property="value", type="string"),
     *          @SWG\Property(property="docStructId", type="integer"),
     *          @SWG\Property(property="surveyId", type="integer"),
     *          @SWG\Property(property="dataTypeId", type="integer"))
     * )
     * @SWG\Response(response="404", description="No survey, data type or documentary structure found.")
     * @SWG\Parameter(name="surveyId",type="integer", in="query", description="Survey id")
     * @SWG\Parameter(name="docStructId",type="integer", in="query", description="Documentary structure id")
     * @SWG\Parameter(name="type", type="string", in="query", description="Filter by type")
     * @SWG\Parameter(name="format", type="string", in="query",
     *     description="Format to export data for this documentary structure. (CSV)")
     * @SWG\Parameter(name="encoding", type="string", in="query",
     *     description="Encoding for export. Default UTF-8. (CP1252, UTF-8)")
     * @SWG\Parameter(name="lang",type="string", in="query", description="Language code. (ex: fr)")
     * @Rest\Get(
     *      path = "/documentary-structure-data-values",
     *      name = "app_documentary_structure_data_values_list"
     * )
     * @Rest\QueryParam(name="surveyId", requirements="[0-9]*", nullable=true)
     * @Rest\QueryParam(name="docStructId", requirements="[0-9]*", nullable=true)
     * @Rest\QueryParam(name="type", requirements="operation|number|text|boolean", nullable=true)
     * @Rest\QueryParam(name="format", requirements="csv|CSV|pdf|PDF", nullable=true)
     * @Rest\QueryParam(name="encoding", requirements="UTF-8|utf-8|cp1252|CP1252", nullable=true)
     * @Rest\QueryParam(name="lang", requirements="[a-z]{2}", default="fr")
     * @Rest\View
     * @param int|null $surveyId Survey id to filter result.
     * @param int|null $docStructId Documentary structure id to filter result.
     * @param string|null $type To filter result.
     * @param string|null $format Export format. ex: CSV
     * @param string|null $encoding Encoding of result.
     * @param string $lang Lang of export.
     * @return View Array with all values.
     */
    public function listAction(?int $surveyId, ?int $docStructId, ?string $type, ?string $format, ?string $encoding,
                               string $lang): View
    {
        return $this->commonListAction(DocumentaryStructures::class,
            $surveyId, $docStructId, $type, $format, $encoding, $lang);
    }

    /**
     * Insert new value between survey, data type and documentary structure or update it if already exists.
     *
     * @SWG\Response(
     *     response="200",
     *     description="Return value information created or updated.",
     *     @SWG\Schema(type="object",
     *          @SWG\Property(property="value", type="string"),
     *          @SWG\Property(property="docStructId", type="integer"),
     *          @SWG\Property(property="surveyId", type="integer"),
     *          @SWG\Property(property="dataTypeId", type="integer"))
     * )
     * @SWG\Response(response="404", description="No survey, data type or documentary structure found.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="body", in="body", description="Values informations.",
     *     @SWG\Schema(type="object",
     *     @SWG\Property(property="value", type="string"),
     *     @SWG\Property(property="docStructId", type="integer"),
     *     @SWG\Property(property="surveyId", type="integer"),
     *     @SWG\Property(property="dataTypeId", type="integer"),
     *     ))
     * @Rest\Put(path="/documentary-structure-data-values", name="app_documentary_structure_data_values_create_update")
     * @Rest\RequestParam(name="surveyId", requirements="[0-9]*", nullable=false)
     * @Rest\RequestParam(name="dataTypeId", requirements="[0-9]*", nullable=false)
     * @Rest\RequestParam(name="docStructId", requirements="[0-9]*", nullable=false)
     * @Rest\RequestParam(name="value", nullable=false)
     * @Rest\View
     * @param int $surveyId Id of survey linked with this value.
     * @param int $dataTypeId Id of type linked with this value.
     * @param int $docStructId Id of documentary structure linked with this value.
     * @param string $value Value for this survey, data type and establishment.
     * @return View Value information has just been created.
     */
    public function insertAction(string $value, int $surveyId, int $dataTypeId, int $docStructId) : View
    {
        return $this->commonInsertAction(DocumentaryStructures::class,
            $value, $surveyId, $dataTypeId, $docStructId);
    }

    /**
     * Delete documentary structure data value.
     * @SWG\Response(response="204", description="Documentary structure data value deleted.")
     * @SWG\Response(response="404", description="No documentary structure, data type or survey found.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="surveyId",type="integer", in="path", description="Survey id.")
     * @SWG\Parameter(name="docStructId",type="integer", in="path", description="Establishment id.")
     * @SWG\Parameter(name="dataTypeId",type="integer", in="path", description="Data type id.")
     * @Rest\Delete(
     *      path="/documentary-structure-data-values/{surveyId}/{docStructId}/{dataTypeId}",
     *      name="app_documentary_structure_data_values_delete",
     *      requirements={"surveyId"="\d+", "docStructId"="\d+", "dataTypeId"="\d+"}
     * )
     * @Rest\View
     * @param int $surveyId Id of survey linked with this value.
     * @param int $dataTypeId Id of type linked with this value.
     * @param int $docStructId Id of documentary structure linked with this value.
     * @return View Information about action.
     */
    public function deleteAction(int $surveyId, int $docStructId, int $dataTypeId) : View
    {
        return $this->commonDeleteAction(DocumentaryStructures::class,
            $surveyId, $docStructId, $dataTypeId);
    }



}
