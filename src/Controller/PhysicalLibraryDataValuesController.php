<?php

namespace App\Controller;

use App\Entity\PhysicalLibraries;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use App\Controller\AbstractController\DataValuesController;

/**
 * Class PhysicalLibraryDataValuesController
 * @package App\Controller
 * @SWG\Tag(name="Physical library data values")
 */
class PhysicalLibraryDataValuesController extends DataValuesController
{
    /**
     * Show all data value between physical library and survey.
     *
     * @SWG\Response(
     *     response="200",
     *     description="Return all data values of survey for a physical library.",
     *     @SWG\Schema(type="object",
     *          @SWG\Property(property="value", type="string"),
     *          @SWG\Property(property="physicLibId", type="integer"),
     *          @SWG\Property(property="surveyId", type="integer"),
     *          @SWG\Property(property="dataTypeId", type="integer"))
     * )
     * @SWG\Response(response="404", description="No survey, data type or physical library found.")
     * @SWG\Parameter(name="surveyId",type="integer", in="query", description="Survey id")
     * @SWG\Parameter(name="physicLibId",type="integer", in="query", description="Physical library id")
     * @SWG\Parameter(name="type", type="string", in="query", description="Filter by type")
     * @SWG\Parameter(name="format", type="string", in="query",
     *     description="Format to export data for this documentary structure. (CSV)")
     * @SWG\Parameter(name="encoding", type="string", in="query",
     *     description="Encoding for export. Default UTF-8. (CP1252, UTF-8)")
     * @SWG\Parameter(name="lang",type="string", in="query", description="Language code. (ex: fr)")
     *
     * @Rest\Get(
     *      path = "/physical-library-data-values",
     *      name = "app_physical_library_data_values_list"
     * )
     * @Rest\QueryParam(name="surveyId", requirements="[0-9]*", nullable=true)
     * @Rest\QueryParam(name="physicLibId", requirements="[0-9]*", nullable=true)
     * @Rest\QueryParam(name="type", requirements="operation|number|text|boolean", nullable=true)
     * @Rest\QueryParam(name="format", requirements="csv|CSV|pdf|PDF", nullable=true)
     * @Rest\QueryParam(name="encoding", requirements="UTF-8|utf-8|cp1252|CP1252", nullable=true)
     * @Rest\QueryParam(name="lang", requirements="[a-z]{2}", default="fr")
     * @Rest\View
     * @param int|null $surveyId Survey id to filter result.
     * @param int|null $physicLibId Physical library id to filter result.
     * @param string|null $type To filter result.
     * @param string|null $format File format to return data.
     * @param string|null $encoding Encoding of result.
     * @param string $lang Lang of export.
     * @return View Array with all values.
     */
    public function listAction(?int $surveyId, ?int $physicLibId, ?string $type, ?string $format, ?string $encoding,
                               string $lang): View
    {
        return $this->commonListAction(PhysicalLibraries::class,
            $surveyId, $physicLibId, $type, $format, $encoding, $lang);
    }

    /**
     * Insert new value between survey, data type and physical library or update it if already exists.
     *
     * @SWG\Response(
     *     response="200",
     *     description="Return value information created or updated.",
     *     @SWG\Schema(type="object",
     *          @SWG\Property(property="value", type="string"),
     *          @SWG\Property(property="physicLibId", type="integer"),
     *          @SWG\Property(property="surveyId", type="integer"),
     *          @SWG\Property(property="dataTypeId", type="integer"))
     * )
     * @SWG\Response(response="404", description="No survey, data type or physical library found.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="body", in="body", description="Values informations.",
     *     @SWG\Schema(type="object",
     *     @SWG\Property(property="value", type="string"),
     *     @SWG\Property(property="physicLibId", type="integer"),
     *     @SWG\Property(property="surveyId", type="integer"),
     *     @SWG\Property(property="dataTypeId", type="integer"),
     *     ))
     * @Rest\Put(path="/physical-library-data-values", name="app_physcial_library_data_values_create_update")
     * @Rest\RequestParam(name="surveyId", requirements="[0-9]*", nullable=false)
     * @Rest\RequestParam(name="dataTypeId", requirements="[0-9]*", nullable=false)
     * @Rest\RequestParam(name="physicLibId", requirements="[0-9]*", nullable=false)
     * @Rest\RequestParam(name="value", nullable=false)
     * @Rest\View
     * @param int $surveyId Id of survey linked with this value.
     * @param int $dataTypeId Id of type linked with this value.
     * @param int $physicLibId Id of physical library linked with this value.
     * @param string $value Value for this survey, data type and establishment.
     * @return View Value information has just been created.
     */
    public function insertAction(string $value, int $surveyId, int $dataTypeId, int $physicLibId) : View
    {
        return $this->commonInsertAction(PhysicalLibraries::class,
            $value, $surveyId, $dataTypeId, $physicLibId);
    }


    /**
     * Delete physical library data value.
     * @SWG\Response(response="204", description="Physical library data value deleted.")
     * @SWG\Response(response="404", description="No physical library, data type or survey found.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="surveyId",type="integer", in="path", description="Survey id.")
     * @SWG\Parameter(name="physicLibId",type="integer", in="path", description="Establishment id.")
     * @SWG\Parameter(name="dataTypeId",type="integer", in="path", description="Data type id.")
     * @Rest\Delete(
     *      path="/physical-library-data-values/{surveyId}/{physicLibId}/{dataTypeId}",
     *      name="app_physical_library_data_values_delete",
     *      requirements={"surveyId"="\d+", "physicLibId"="\d+", "dataTypeId"="\d+"}
     * )
     * @Rest\View
     * @param int $surveyId Id of survey linked with this value.
     * @param int $dataTypeId Id of type linked with this value.
     * @param int $physicLibId Id of documentary structure linked with this value.
     * @return View Information about action.
     */
    public function deleteAction(int $surveyId, int $physicLibId, int $dataTypeId) : View
    {
        return $this->commonDeleteAction(PhysicalLibraries::class,
            $surveyId, $physicLibId, $dataTypeId);
    }


}
