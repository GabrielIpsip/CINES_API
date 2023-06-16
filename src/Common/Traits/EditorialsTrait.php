<?php


namespace App\Common\Traits;


use App\Entity\Editorials;
use Exception;
use Symfony\Component\HttpFoundation\Response;

trait EditorialsTrait
{

    /**
     * Get all editorials in database.
     * @param int $surveyState State id of editorial survey.
     * @return array Array with all editorials doctrine entities.
     * @throws Exception 404 : No editorial found.
     */
    private function getAllEditorials(int $surveyState): array
    {
        $editorials = $this->managerRegistry->getRepository(Editorials::class)
            ->getAllEditorialsBySurveyState($surveyState);
        if (count($editorials) === 0)
        {
            throw new Exception('No editorial found.', Response::HTTP_NOT_FOUND);
        }
        return $editorials;
    }

    /**
     * Get editorial by survey id.
     * @param int $surveyId Id of survey.
     * @return Editorials Editorial doctrine entity.
     * @throws Exception 404 : No editorial found.
     */
    private function getEditorialBySurveyId(int $surveyId): Editorials
    {
        $editorial = $this->managerRegistry->getRepository(Editorials::class)->find($surveyId);
        if (!$editorial)
        {
            throw new Exception('No editorial for the survey with id : ' . $surveyId,
                Response::HTTP_NOT_FOUND);
        }
        return $editorial;
    }
}