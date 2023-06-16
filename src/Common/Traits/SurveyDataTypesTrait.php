<?php


namespace App\Common\Traits;

use App\Entity\DataTypes;
use App\Entity\SurveyDataTypes;
use App\Entity\Surveys;
use Exception;
use Symfony\Component\HttpFoundation\Response;

trait SurveyDataTypesTrait
{
    /**
     * Get all relation between survey and dataType in database by criteria.
     * @param array $criteria Array that contains all search criteria for doctrine.
     * @return array Array with all surveyDataTypes doctrine entity which match with criteria.
     * @throws Exception 404 : No relation between data type and survey found.
     */
    private function getSurveyDataTypesByCriteria(array $criteria): array
    {
        $relations = $this->managerRegistry->getRepository(SurveyDataTypes::class)->findBy($criteria);
        if (count($relations) === 0)
        {
            throw new Exception('No relation between data type and survey found.',
                Response::HTTP_NOT_FOUND);
        }
        return $relations;
    }

    /**
     * Get one relation between survey and dataType.
     * @param Surveys $survey Survey of relation.
     * @param DataTypes $dataType DataType of relation.
     * @return SurveyDataTypes SurveyDataType with this survey and dataType.
     * @throws Exception 404 : No relation found.
     */
    private function getSurveyDataTypeBySurveyAndDataType(Surveys $survey, DataTypes $dataType): SurveyDataTypes
    {
        $relation = $this->managerRegistry->getRepository(SurveyDataTypes::class)
            ->findOneBy(array('survey' => $survey, 'type' => $dataType));
        if (!$relation)
        {
            throw new Exception('No relation found.', Response::HTTP_NOT_FOUND);
        }
        return $relation;
    }

    /**
     * Throw exception if relation already exists.
     * @param Surveys $survey Survey of relation.
     * @param DataTypes $dataType DataType of relation.
     * @throws Exception 409 : Relation already exists.
     */
    private function checkIfExistsRelation(Surveys $survey, DataTypes $dataType)
    {
        $existsRelation = $this->managerRegistry->getRepository(SurveyDataTypes::class)
            ->findBy(array(
                'survey' => $survey,
                'type' => $dataType));
        if ($existsRelation)
        {
            throw new Exception('Error: one relation between this data type and this survey already exists.',
                Response::HTTP_CONFLICT);
        }
    }
}