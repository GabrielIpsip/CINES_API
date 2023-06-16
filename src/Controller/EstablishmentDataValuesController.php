<?php

namespace App\Controller;

use App\Common\Enum\Role;
use App\Common\Traits\DataTypesTrait;
use App\Common\Traits\DocumentaryStructureDataValuesTrait;
use App\Common\Traits\EstablishmentDataValuesTrait;
use App\Common\Traits\EstablishmentsTrait;
use App\Common\Traits\DocumentaryStructuresTrait;
use App\Common\Traits\SurveysTrait;
use App\Entity\Establishments;
use Exception;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Controller\Annotations as Rest;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Response;
use App\Controller\AbstractController\DataValuesController;

/**
 * Class EstablishmentDataValuesController
 * @package App\Controller
 * @SWG\Tag(name="Establishment data values")
 */
class EstablishmentDataValuesController extends DataValuesController
{
    use EstablishmentsTrait,
        DocumentaryStructuresTrait,
        EstablishmentDataValuesTrait,
        SurveysTrait,
        DataTypesTrait,
        DocumentaryStructureDataValuesTrait;

    /**
     * Show all data value between establishment and survey.
     *
     * @SWG\Response(
     *     response="200",
     *     description="Return all data values of survey for an establishment.",
     *     @SWG\Schema(type="object",
     *          @SWG\Property(property="value", type="string"),
     *          @SWG\Property(property="establishmentId", type="integer"),
     *          @SWG\Property(property="surveyId", type="integer"),
     *          @SWG\Property(property="dataTypeId", type="integer"))
     * )
     * @SWG\Response(response="404", description="No survey, data type or establishment found.")
     * @SWG\Parameter(name="surveyId",type="integer", in="query", description="Survey id")
     * @SWG\Parameter(name="establishmentId",type="integer", in="query", description="Establishment id")
     * @SWG\Parameter(name="type", type="string", in="query", description="Filter by type")
     *
     * @Rest\Get(
     *      path = "/establishment-data-values",
     *      name = "app_establishment_data_values_list"
     * )
     * @Rest\QueryParam(name="surveyId", requirements="[0-9]*", nullable=true)
     * @Rest\QueryParam(name="establishmentId", requirements="[0-9]*", nullable=true)
     * @Rest\QueryParam(name="type", requirements="operation|number|text|boolean", nullable=true)
     * @Rest\View
     * @param int|null $surveyId Survey id to filter result.
     * @param int|null $establishmentId Establishment id to filter result.
     * @param string|null $type To filter result.
     * @return View Array with all values.
     */
    public function listAction(?int $surveyId, ?int $establishmentId, ?string $type): View
    {
        return $this->commonListAction(
            Establishments::class, $surveyId, $establishmentId, $type, null, null, null);
    }

    /**
     * Insert new value between survey, data type and establishment or update it if already exists.
     *
     * @SWG\Response(
     *     response="200",
     *     description="Return value information created or updated.",
     *     @SWG\Schema(type="object",
     *          @SWG\Property(property="value", type="string"),
     *          @SWG\Property(property="establishmentId", type="integer"),
     *          @SWG\Property(property="surveyId", type="integer"),
     *          @SWG\Property(property="dataTypeId", type="integer"))
     * )
     * @SWG\Response(response="404", description="No survey, data type or establishment found.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="body", in="body", description="Values informations.",
     *     @SWG\Schema(type="object",
     *     @SWG\Property(property="value", type="string"),
     *     @SWG\Property(property="establishmentId", type="integer"),
     *     @SWG\Property(property="surveyId", type="integer"),
     *     @SWG\Property(property="dataTypeId", type="integer"),
     *     ))
     * @Rest\Put(path="/establishment-data-values", name="app_establishment_data_values_create_update")
     * @Rest\RequestParam(name="surveyId", requirements="[0-9]*", nullable=false)
     * @Rest\RequestParam(name="dataTypeId", requirements="[0-9]*", nullable=false)
     * @Rest\RequestParam(name="establishmentId", requirements="[0-9]*", nullable=false)
     * @Rest\RequestParam(name="value", nullable=false)
     * @Rest\View
     * @param int $surveyId Id of survey linked with this value.
     * @param int $dataTypeId Id of type linked with this value.
     * @param int $establishmentId Id of establishment linked with this value.
     * @param string $value Value for this survey, data type and establishment.
     * @return View Value information has just been created.
     */
    public function insertAction(string $value, int $surveyId, int $dataTypeId, int $establishmentId) : View
    {
        return $this->commonInsertAction(Establishments::class,
            $value, $surveyId, $dataTypeId, $establishmentId);
    }

    /**
     * Delete establishment data value.
     * @SWG\Response(response="204", description="Establishment data value deleted.")
     * @SWG\Response(response="404", description="No establishement, data type or survey found.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="surveyId",type="integer", in="path", description="Survey id.")
     * @SWG\Parameter(name="establishmentId",type="integer", in="path", description="Establishment id.")
     * @SWG\Parameter(name="dataTypeId",type="integer", in="path", description="Data type id.")
     * @Rest\Delete(
     *      path="/establishment-data-values/{surveyId}/{establishmentId}/{dataTypeId}",
     *      name="app_establishment_data_values_delete",
     *      requirements={"surveyId"="\d+", "establishmentId"="\d+", "dataTypeId"="\d+"}
     * )
     * @Rest\View
     * @param int $surveyId Id of survey linked with this value.
     * @param int $dataTypeId Id of type linked with this value.
     * @param int $establishmentId Id of establishment linked with this value.
     * @return View Information about action.
     */
    public function deleteAction(int $surveyId, int $establishmentId, int $dataTypeId) : View
    {
        return $this->commonDeleteAction(Establishments::class, $surveyId, $establishmentId, $dataTypeId);
    }

    /**
     * Check if establishment budget is bigger than all associated documentary structure.
     * @SWG\Response(
     *     response="200",
     *     description="Check if establishment budget is bigger than all associated documentary structure.",
     *     @SWG\Schema(type="object",
     *          @SWG\Property(property="error", type="boolean"),
     *          @SWG\Property(property="EtabDepDoc", type="number"),
     *          @SWG\Property(property="sumDepDTot", type="number"))
     * )
     * @SWG\Response(response="404", description="No establishment type found.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @Rest\Get(
     *     path="/establishment-data-values/check-budget/{surveyId}/{establishmentId}",
     *     name="app_establishment_data_values_check_budget",
     *     requirements={"surveyId"="\d+", "establishmentId"="\d+"}
     * )
     * @Rest\View
     * @param int $surveyId Survey id.
     * @param int $establishmentId Establishment id.
     * @return View
     */
    public function checkBudget(int $surveyId, int $establishmentId): View
    {
        try
        {
            $establishment = $this->getEstablishmentById($establishmentId);
            $this->checkRights([Role::ADMIN, Role::ADMIN_RO, Role::USER, Role::VALID_SURVEY_RESP, Role::SURVEY_ADMIN],
                null, $establishment);

            $survey = $this->getSurveyById($surveyId);
            $docStructs = $this->getDocStructByEstablishment($establishment);

            $dataTypes = $this->getDataTypeByCodes(array('EtabDepDoc', 'DepDTot'));
            if (count($dataTypes) === 2)
            {
                if ($dataTypes[0]->getCode() === 'EtabDepDoc')
                {
                    $etabDepDoc = $dataTypes[0];
                    $depDTot = $dataTypes[1];
                }
                else
                {
                    $etabDepDoc = $dataTypes[1];
                    $depDTot = $dataTypes[0];
                }
            }
            else
            {
                throw new Exception('EtabDepDoc or DepDTot not exists.', Response::HTTP_NOT_FOUND);
            }

            $establishmentDataValue = $this->getEstablishmentDataValue($etabDepDoc, $establishment, $survey);
            $docStructDataValues = $this->getDocStructDataValues($depDTot, $docStructs, $survey);

            $sumDepDTot = 0;
            foreach ($docStructDataValues as $docStructDataValue)
            {
                $sumDepDTot += $docStructDataValue->getValue();
            }

            $response['EtabDepDoc'] = +$establishmentDataValue->getValue();
            $response['sumDepDTot'] = $sumDepDTot;
            $response['error'] =  $establishmentDataValue->getValue() < $sumDepDTot;

            return $this->createView($response, Response::HTTP_OK);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }



}