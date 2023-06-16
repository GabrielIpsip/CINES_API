<?php

namespace App\Common\Traits;

use App\Common\Enum\State;
use App\Entity\States;
use App\Entity\Surveys;
use Exception;
use Symfony\Component\HttpFoundation\Response;

trait SurveysTrait
{
    /**
     * Get survey entity by this id.
     * @param int $surveyId Id of survey in database.
     * @return Surveys Survey entity that has this id.
     * @throws Exception 404 : No survey found.
     */
    private function getSurveyById(int $surveyId): Surveys
    {
        $survey = $this->managerRegistry->getRepository(Surveys::class)->find($surveyId);
        if (!$survey)
        {
            throw new Exception('No survey found with this id: ' . $surveyId, Response::HTTP_NOT_FOUND);
        }
        return $survey;
    }

    /**
     * Get all survey in this state.
     * @param int $state Id of state in database. (use Common\Enum\State)
     * @return array Array of surveys doctrine entity.
     * @throws Exception 404 : No survey found in this state.
     */
    private function getAllSurveyByState(int $state): array
    {
        $state = $this->managerRegistry->getRepository(States::class)->find($state);
        $surveys = $this->managerRegistry->getRepository(Surveys::class)->findBy(['state' => $state]);
        if (count($surveys) === 0)
        {
            throw new Exception('No survey found in this state : ' . $state->getName(),
                Response::HTTP_NOT_FOUND);
        }
        return $surveys;
    }

    /**
     * Get survey with creation is newer.
     * @return Surveys Last survey doctrine entity.
     * @throws Exception 404 : No survey found.
     */
    private function getLastSurvey(): Surveys
    {
        $lastSurvey = $this->managerRegistry->getRepository(Surveys::class)->findOneBy([], ['creation' => 'DESC']);
        if (!$lastSurvey)
        {
            throw new Exception('No survey found.', Response::HTTP_NOT_FOUND);
        }
        return $lastSurvey;
    }

    /**
     * Get last survey created by state.
     * @param int $state Sate id. (Use Common\State)
     * @param bool $throwError True to throw error, else false.
     * @return Surveys|null Survey doctrine entity found.
     * @throws Exception 404 : No survey found.
     */
    private function getLastSurveyByState(int $state, $throwError = true): ?Surveys
    {
        $doctrine = $this->managerRegistry;

        $state = $doctrine->getRepository(States::class)->find($state);
        if (!$state && $throwError)
        {
            throw new Exception('No \'open\' state found', Response::HTTP_NOT_FOUND);
        }

        $lastSurvey = $doctrine->getRepository(Surveys::class)
            ->findOneBy(array('state' => $state), array('creation' => 'DESC'));
        if (!$lastSurvey && $throwError)
        {
            throw new Exception('No open survey', Response::HTTP_NOT_FOUND);
        }

        return $lastSurvey;
    }

    /**
     * Get last open survey and if there is not, the last survey closed, else last published, else last survey created.
     * @return Surveys Survey doctrine entity.
     * @throws Exception 404 : No survey found.
     */
    private function getLastActiveSurvey(): Surveys
    {
            $survey = $this->getLastSurveyByState(State::OPEN, false);
            if ($survey)
            {
                return $survey;
            };
            $survey = $this->getLastSurveyByState(State::CLOSE, false);
            if ($survey)
            {
                return $survey;
            }
            $survey = $this->getLastSurveyByState(State::PUBLISHED, false);
            if ($survey)
            {
                return $survey;
            }
            return $this->getLastSurvey();
    }

    /**
     * Get list of survey by criteria and ordered.
     * @param array $criteria Array with all criteria for search surveys.
     * @param array|null $orderBy Order the result list.
     * @return array Ordered array with all survey which match with criteria.
     * @throws Exception 404 : No survey found.
     */
    private function getSurveyByCriteriaOrdered(array $criteria, ?array $orderBy): array
    {
        $surveys = $this->managerRegistry->getRepository(Surveys::class)->findBy($criteria, $orderBy);
        if (count($surveys) === 0)
        {
            throw new Exception('No surveys found.', Response::HTTP_NOT_FOUND);
        }
        return $surveys;
    }

    /**
     * Check if a survey in database already has this name.
     * @param string $name Name to check.
     * @throws Exception 409 : A survey already has this name.
     */
    private function checkIfNameUnique(string $name)
    {
        $survey = $this->managerRegistry->getRepository(Surveys::class)->getByName($name);
        if (count($survey) > 0)
        {
            throw new Exception('Name must be unique. Survey with id : ' . $survey[0]['id'] .
                ' has already name : ' . $name, Response::HTTP_CONFLICT);
        }
    }

    /**
     * @param int $state
     * @return array
     * @throws Exception
     */
    private function getAllDataCalendarYearByState(int $state): array
    {
        $years = [];
        $surveys = $this->getAllSurveyByState(State::PUBLISHED);

        if (count($surveys) !== 0) {
            foreach ($surveys as $survey)
            {
                array_push($years, $survey->getDataCalendarYear()->format('Y'));
            }
        }

        return $years;
    }
}
