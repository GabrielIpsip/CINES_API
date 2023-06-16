<?php

namespace App\Controller;

use App\Common\Enum\Role;
use App\Common\Traits\DataTypesTrait;
use App\Common\Traits\SurveyDataTypesTrait;
use App\Common\Traits\SurveysTrait;
use App\Entity\SurveyDataTypes;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Response;
use Swagger\Annotations as SWG;
use App\Controller\AbstractController\ESGBUController;

/**
 * Class SurveyDataTypesController
 * @package App\Controller
 * @SWG\Tag(name="Survey data types")
 */
class SurveyDataTypesController extends ESGBUController
{
    use SurveysTrait,
        DataTypesTrait,
        SurveyDataTypesTrait;

    /**
     * Show all relations between data types and survey.
     *
     * @SWG\Response(
     *     response="200",
     *     description="Return data type select by group id.",
     *     @SWG\Schema(type="object",
     *          @SWG\Property(property="id", type="integer"),
     *          @SWG\Property(property="active", type="boolean"),
     *          @SWG\Property(property="surveyId", type="integer"),
     *          @SWG\Property(property="dataTypeId", type="string"))
     * )
     * @SWG\Response(response="404", description="No survey, data type or relation found.")
     * @SWG\Parameter(name="surveyId",type="integer", in="query", description="Survey id")
     * @SWG\Parameter(name="dataTypeId",type="integer", in="query", description="Data type id.")
     *
     * @Rest\Get(
     *      path = "/survey-data-types",
     *      name = "app_survey_data_types_list"
     * )
     * @Rest\QueryParam(name="surveyId", requirements="[0-9]*", nullable=true)
     * @Rest\QueryParam(name="dataTypeId", requirements="[0-9]*", nullable=true)
     * @Rest\View
     * @param int|null $surveyId Survey of id to filter result.
     * @param int|null $dataTypeId Data type i to filter result.
     * @return View Array with all relations.
     */
    public function listAction(?int $surveyId, ?int $dataTypeId): View
    {
        try
        {
            $criteria = array();

            if ($surveyId)
            {
                $criteria['survey'] = $this->getSurveyById($surveyId);
            }

            if ($dataTypeId)
            {
                $criteria['type'] = $this->getDataTypeById($dataTypeId);
            }

            $relations = $this->getSurveyDataTypesByCriteria($criteria);

            $formattedRelations = array();
            foreach ($relations as $relation)
            {
                array_push($formattedRelations, $this->formatRelationForResponse($relation));
            }

            return $this->createView($formattedRelations, Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Create new relation between survey and data type.
     *
     * @SWG\Response(
     *     response="200",
     *     description="Return data type created.",
     *     @SWG\Schema(type="object",
     *          @SWG\Property(property="id", type="integer"),
     *          @SWG\Property(property="active", type="boolean"),
     *          @SWG\Property(property="surveyId", type="integer"),
     *          @SWG\Property(property="name", type="string"))
     * )
     * @SWG\Response(response="404", description="No survey or data type found.")
     * @SWG\Response(response="409", description="Relation already exists.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="body", in="body", description="Relation informations.",
     *     @SWG\Schema(type="object",
     *     @SWG\Property(property="active", type="boolean"),
     *     @SWG\Property(property="surveyId", type="integer"),
     *     @SWG\Property(property="dataTypeId", type="integer"),
     *     ))
     * @Rest\Post(path="/survey-data-types", name="app_survey_data_types_create")
     * @Rest\RequestParam(name="surveyId", requirements="[0-9]*", nullable=false)
     * @Rest\RequestParam(name="dataTypeId", requirements="[0-9]*", nullable=false)
     * @ParamConverter("relation", converter="fos_rest.request_body")
     * @Rest\View
     * @param SurveyDataTypes $relation Create relation with active field get from body.
     * @param int $surveyId Id of survey linked with this relation.
     * @param int $dataTypeId Id of type linked with this relation.
     * @return View Relation has just been created.
     */
    public function createAction(SurveyDataTypes $relation, int $surveyId, int $dataTypeId) : View
    {
        try
        {
            $this->checkRights([Role::ADMIN]);

            $survey = $this->getSurveyById($surveyId);
            $dataType = $this->getDataTypeById($dataTypeId);

            $this->checkIfExistsRelation($survey, $dataType);

            $relation->setType($dataType);
            $relation->setSurvey($survey);

            $em = $this->getDoctrine()->getManager();
            $em->persist($relation);
            $em->flush();

            return $this->createView(
                $this->formatRelationForResponse($relation), Response::HTTP_CREATED, true);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }

    /**
     * Update status of relation between survey and data type.
     * @SWG\Response(
     *     response="200",
     *     description="Return data type updated.",
     *     @SWG\Schema(type="object",
     *          @SWG\Property(property="id", type="integer"),
     *          @SWG\Property(property="active", type="boolean"),
     *          @SWG\Property(property="surveyId", type="integer"),
     *          @SWG\Property(property="dataTypeId", type="integer"))
     * )
     * @SWG\Response(response="404", description="No survey, data type or relation found.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="surveyId", type="integer", in="path", description="Survey id of relation.")
     * @SWG\Parameter(name="dataTypeId", type="integer", in="path", description="Type id of relation.")
     * @Rest\Patch(
     *     path="/survey-data-types/{surveyId}/{dataTypeId}",
     *     name="app_survey_data_types_update",
     *     requirements={"surveyId"="\d+", "dataTypeId"="\d+"}
     * )
     * @Rest\RequestParam(name="active", strict=true)
     * @Rest\View
     * @param int $surveyId Id of survey linked with this relation.
     * @param int $dataTypeId Id of type linked with this relation.
     * @param bool $active True to active the relation, else false.
     * @return View Relation has just been updated.
     */
    public function updateAction(int $surveyId, int $dataTypeId, bool $active) : View
    {
        try
        {
            $this->checkRights([Role::ADMIN]);

            $survey = $this->getSurveyById($surveyId);
            $dataType = $this->getDataTypeById($dataTypeId);
            $relation = $this->getSurveyDataTypeBySurveyAndDataType($survey, $dataType);

            $relation->setActive($active);
            $this->managerRegistry->getManager()->flush();

            return $this->createView(
                $this->formatRelationForResponse($relation), Response::HTTP_OK, true);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }

    /**
     * Format survey dataType for the response.
     * @param SurveyDataTypes|null $relation SurveyDataType doctrine entity to format for the response.
     * @return array|null Array representation of surveyDataType for response.
     */
    private function formatRelationForResponse(?SurveyDataTypes $relation): ?array
    {
        if (!$relation)
        {
            return null;
        }

        return array(
            "id" => $relation->getId(),
            "active" => $relation->getActive(),
            "surveyId" => $relation->getSurvey()->getId(),
            "dataTypeId" => $relation->getType()->getId()
        );
    }
}