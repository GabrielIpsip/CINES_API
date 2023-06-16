<?php


namespace App\Common\Traits;


use App\Common\Enum\Role;
use App\Entity\AbstractEntity\Administrations;
use App\Entity\DocumentaryStructures;
use App\Entity\Establishments;
use App\Entity\PhysicalLibraries;
use App\Entity\Surveys;
use App\Entity\SurveyValidations;
use Exception;
use Symfony\Component\HttpFoundation\Response;

trait SurveyValidationsTrait
{
    /**
     * Get surveyValidation in database by criteria.
     * @param array $criteria Array that contains criteria.
     * @return array Array that contains all surveyValidation doctrine entity which match with criteria.
     * @throws Exception 404 : No survey validation found.
     */
    private function getSurveyValidationsByCriteria(array $criteria): array
    {
        $surveyValidations = $this->managerRegistry->getRepository(SurveyValidations::class)
            ->findBy($criteria);

        if (count($surveyValidations) === 0)
        {
            throw new Exception('No survey validations found.', Response::HTTP_NOT_FOUND);
        }
        return $surveyValidations;
    }

    /**
     * Check if survey is validate by user for documentary structure, read value in SurveyValidation table.
     * @param Surveys $survey Survey of validation to check.
     * @param DocumentaryStructures $docStruct Documentary structure of validation to check.
     * @throws Exception 403 : Survey is validate for this documentary structure.
     */
    private function checkIfValidateSurveyForDocStruct(Surveys $survey, DocumentaryStructures $docStruct)
    {
        $surveyValidation = $this->managerRegistry->getRepository(SurveyValidations::class)
            ->findOneBy(array('documentaryStructure' => $docStruct, 'survey' => $survey));

        if ($surveyValidation && $surveyValidation->getValid())
        {
            throw new Exception('This survey is validate. It can\'t be modified anymore',
                Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Check if survey is validate by user, for an administration.
     * @param string $entityClass To know which repository to use. (ex: Establishments::class)
     * @param Administrations $administration Administration to check if validate.
     * @param Surveys $survey Survey of validation to check.
     * @throws Exception 403 : Survey is validate for this documentary structure, or documentary structure associated
     *                         with the physical library.
     */
    private function checkIfValidateSurveyForAdministration(string $entityClass, Administrations $administration,
                                                            Surveys $survey)
    {
        if ($this->checkRightsBool([Role::ADMIN]))
        {
            return;
        }

        switch ($entityClass)
        {
            case Establishments::class:
                break;

            case DocumentaryStructures::class:
                $this->checkIfValidateSurveyForDocStruct($survey, $administration);
                break;

            case PhysicalLibraries::class:
                $this->checkIfValidateSurveyForDocStruct($survey, $administration->getDocumentaryStructure());
                break;
        }
    }
}
