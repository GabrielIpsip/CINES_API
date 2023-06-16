<?php


namespace App\Controller;

use App\Common\Enum\Role;
use App\Common\Traits\DocumentaryStructuresTrait;
use App\Common\Traits\SurveysTrait;
use App\Common\Traits\SurveyValidationsTrait;
use App\Utils\StringTools;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use App\Entity\SurveyValidations;
use App\Controller\AbstractController\ESGBUController;

/**
 * Class SurveyValidationsController
 * @package App\Controller
 * @SWG\Tag(name="Survey validations")
 */
class SurveyValidationsController extends ESGBUController
{
    use SurveyValidationsTrait,
        SurveysTrait,
        DocumentaryStructuresTrait;

    /**
     * Show all survey validations.
     * @SWG\Response(
     *     response="200",
     *     description="Array with all survey validation state.",
     *     @SWG\Schema(type="array",
     *     @SWG\Items(allOf={
     *          @SWG\Schema(ref=@Model(type=SurveyValidations::class))},
     *          @SWG\Property(property="surveyId", type="integer"),
     *          @SWG\Property(property="docStructId", type="integer")))
     * )
     * @SWG\Response(
     *     response="404",
     *     description="No survey, documentary structure, establishment or survey validations found.",
     * )
     * @Rest\QueryParam(name="surveyId",requirements="\d+",nullable=true)
     * @Rest\QueryParam(name="docStructId",requirements="^\d+(\d+|,)*",nullable=true,
     *     description="Id can be separated by comma without space: ex: 12,34,1")
     * @Rest\QueryParam(name="establishmentId",requirements="\d+",nullable=true)
     * @Rest\Get(
     *      path = "/survey-validations",
     *      name = "app_survey_validations_list"
     * )
     * @Rest\View
     * @param string|null $surveyId Survey Id to filter result.
     * @param string|null $docStructId Documentary structure id to filter result.
     * @param string|null $establishmentId
     * @return View Array with all numbers information.
     */
    public function listAction(?string $surveyId, ?string $docStructId, ?string $establishmentId) : View
    {
        try
        {
            $criteria = array();

            if ($surveyId)
            {
                $criteria['survey'] = $this->getSurveyById($surveyId);
            }

            if ($docStructId)
            {
                $docStructId = StringTools::commaSplit($docStructId);
                $criteria['documentaryStructure'] = $this->getSerialDocStructById($docStructId);
            }

            if ($establishmentId && !$docStructId)
            {
                $docStructList = $this->getDocStructByEstablishmentId($establishmentId);
                if (count($docStructList) > 0)
                {
                    $criteria['documentaryStructure'] = $docStructList;
                }
            }

            $surveyValidations = $this->getSurveyValidationsByCriteria($criteria);
            foreach ($surveyValidations as &$surveyValidation)
            {
                $surveyValidation = $this->formatSurveyValidationsForResponse($surveyValidation);
            }

            return $this->createView($surveyValidations, Response::HTTP_OK);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Create a update survey validation relation.
     *
     * @SWG\Response(
     *     response="200",
     *     description="Return number information created.",
     *     @SWG\Schema(type="object", allOf={
     *          @SWG\Schema(ref=@Model(type=SurveyValidations::class))},
     *          @SWG\Property(property="surveyId", type="integer"),
     *          @SWG\Property(property="docStructId", type="integer"))
     * )
     * @SWG\Response(response="404", description="No survey or documentary structure found.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Response(response="400", description="Total progress not equal to 100%.")
     * @SWG\Response(response="409", description="Survey validation relation already exists.")
     * @SWG\Parameter(name="body", in="body", description="Data type informations.",
     *     @SWG\Schema(type="object",
     *          @SWG\Property(property="valid", type="boolean"),
     *          @SWG\Property(property="surveyId", type="integer"),
     *          @SWG\Property(property="docStructId", type="integer"))
     * )
     * @Rest\Post(path="/survey-validations", name="app_survey_validation_create")
     * @Rest\RequestParam(name="surveyId", requirements="[0-9]*", nullable=false)
     * @Rest\RequestParam(name="docStructId", requirements="[0-9]*", nullable=false)
     * @Rest\RequestParam(name="valid", nullable=false)
     * @Rest\View
     * @param bool $valid
     * @param int $surveyId
     * @param int $docStructId
     * @throws Exception
     * @return View Information of survey that just be validated.
     */
    public function createOrUpdate(bool $valid, int $surveyId, int $docStructId) : View
    {
        try
        {
            $em = $this->getDoctrine()->getManager();
            $docStruct = $this->getDocStructById($docStructId);
            $this->checkRights([Role::ADMIN, Role::VALID_SURVEY_RESP], $docStruct);
            $survey = $this->getSurveyById($surveyId);

            if ($valid)
            {
                $this->checkTotalProgressDocStruct($docStruct, $survey);
            }

            $surveyValidation = $this->getDoctrine()->getRepository(SurveyValidations::class)
                ->findOneBy(array('survey' => $survey, 'documentaryStructure' => $docStruct));
            if ($surveyValidation)
            {
                $surveyValidation->setValid($valid);
            }
            else
            {
                $surveyValidation = new SurveyValidations($valid, $survey, $docStruct);
                $em->persist($surveyValidation);
            }

            $em->flush();
            return $this->createView(
                $this->formatSurveyValidationsForResponse($surveyValidation), Response::HTTP_OK, true);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }

    /**
     * Format survey validation for response.
     * @param SurveyValidations $surveyValidation SurveyValidation doctrine entity to format.
     * @return array Array representation of surveyValidation for response.
     */
    private function formatSurveyValidationsForResponse(SurveyValidations $surveyValidation): array
    {
        return array(
          'id' => $surveyValidation->getId(),
          'valid' => $surveyValidation->getValid(),
          'validationDate' => $surveyValidation->getValidationDate(),
          'surveyId' => $surveyValidation->getSurvey()->getId(),
          'docStructId' => $surveyValidation->getDocumentaryStructure()->getId()
        );
    }
}