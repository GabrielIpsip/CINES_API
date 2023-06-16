<?php


namespace App\Common\Traits;


use App\Entity\AbstractEntity\AdministrationActiveHistory;
use App\Entity\AbstractEntity\Administrations;
use App\Entity\DocumentaryStructureActiveHistory;
use App\Entity\DocumentaryStructures;
use App\Entity\EstablishmentActiveHistory;
use App\Entity\Establishments;
use App\Entity\PhysicalLibraries;
use App\Entity\PhysicalLibraryActiveHistory;
use App\Entity\Surveys;
use Exception;
use Symfony\Component\HttpFoundation\Response;

trait AdministrationActiveHistoryTrait
{

    /**
     * Get all active history of administration.
     * @param string $administrationEntityClass Class name of administration (ex: Establishments::class)
     * @param Administrations $administration Administration doctrine entity.
     * @return array Array that contains history.
     * @throws Exception 404 : No history found.
     */
    private function getActiveHistory(string $administrationEntityClass, Administrations $administration): array
    {
        $activeHistoryClass = $this->getActiveHistoryClass($administrationEntityClass);
        $activeHistoryAdministrationParameter = $this->getAdministrationParameterName($activeHistoryClass);

        $history = $this->managerRegistry->getRepository($activeHistoryClass)->findBy([
            $activeHistoryAdministrationParameter => $administration
        ]);

        if (count($history) === 0)
        {
            throw new Exception('No history found.', Response::HTTP_NOT_FOUND);
        }
        return $history;
    }

    /**
     * Get active history line.
     * @param string $administrationEntityClass lass name of administration (ex: Establishments::class)
     * @param Administrations $administration Administration doctrine entity.
     * @param Surveys $survey Survey doctrine entity.
     * @param bool $throwError True to throw error when no history line found.
     * @return AdministrationActiveHistory|null Active history line doctrine entity.
     * @throws Exception 404 : No history line found.
     */
    private function getActiveHistoryLine(string $administrationEntityClass, Administrations $administration,
                                          Surveys $survey, bool $throwError = true): ?AdministrationActiveHistory
    {
        $activeHistoryClass = $this->getActiveHistoryClass($administrationEntityClass);
        $activeHistoryAdministrationParameter = $this->getAdministrationParameterName($activeHistoryClass);

        $historyLine = $this->managerRegistry->getRepository($activeHistoryClass)->findOneBy([
            $activeHistoryAdministrationParameter => $administration,
            'survey' => $survey
        ]);
        if (!$historyLine && $throwError)
        {
            throw new Exception('No history line found.', Response::HTTP_NOT_FOUND);
        }
        return $historyLine;
    }

    /**
     * Get class name of active history class by administration class name.
     * @param string $administrationEntityClass Class name of administration (ex: Establishments::class)
     * @return string|null Active history class name.
     */
    private function getActiveHistoryClass(string $administrationEntityClass): ?string
    {
        switch ($administrationEntityClass)
        {
            case Establishments::class:
                return EstablishmentActiveHistory::class;
            case DocumentaryStructures::class:
                return DocumentaryStructureActiveHistory::class;
            case PhysicalLibraries::class:
                return PhysicalLibraryActiveHistory::class;
        }
        return null;
    }

    /**
     * Get parameter name use in entity/database corresponding of administration type.
     * @param string $activeHistoryEntityClass Class name of active administration history class
     * (ex: EstablishmentActiveHistory::class)
     * @return string|null Parameter name.
     */
    private function getAdministrationParameterName(string $activeHistoryEntityClass): ?string
    {
        switch ($activeHistoryEntityClass)
        {
            case EstablishmentActiveHistory::class:
                return 'establishment';
            case DocumentaryStructureActiveHistory::class:
                return 'documentaryStructure';
            case PhysicalLibraryActiveHistory::class:
                return 'physicalLibrary';
        }
        return null;
    }

    /**
     * Update active history line for last created survey.
     * @param string $administrationEntityClass Class name of administration (ex: Establishments::class)
     * @param Administrations $administration Administration doctrine entity.
     * @param bool $active True if administration is active for this survey, else false.
     * @param bool $setOtherSurveyInactive True to disable administration for other survey.
     * @throws Exception 404 : No survey found.
     */
    private function updateActiveHistoryForLastSurvey(string $administrationEntityClass, Administrations $administration,
                                                      bool $active, bool $setOtherSurveyInactive = false)
    {
        $lastSurvey = $this->getLastSurvey();
        $this->insertOrUpdateActiveHistoryLine($administrationEntityClass, $administration, $lastSurvey, $active);

        if ($setOtherSurveyInactive)
        {
            $surveys = $this->managerRegistry->getRepository(Surveys::class)->findAll();
            foreach ($surveys as $survey)
            {
                if ($survey->getId() === $lastSurvey->getId())
                {
                    continue;
                }

                $this->insertOrUpdateActiveHistoryLine($administrationEntityClass, $administration, $survey, false);
            }
        }
    }

    /**
     * Insert or update active history line for this administration.
     * @param string $administrationEntityClass Class name of administration (ex: Establishments::class)
     * @param Administrations $administration Administration doctrine entity.
     * @param Surveys $survey Survey of active history line.
     * @param bool $active True if administration is active for this survey, else false.
     * @return array Index 0 : Active history line doctrine entity, Index 1 : HTTP code response.
     * @throws Exception 404 : No survey found.
     */
    private function insertOrUpdateActiveHistoryLine(string $administrationEntityClass, Administrations $administration,
                                                     Surveys $survey, bool $active): array
    {
        $activeHistoryLine = $this->getActiveHistoryLine(
            $administrationEntityClass, $administration, $survey, false);

        $em = $this->managerRegistry->getManager();
        $codeResponse = Response::HTTP_OK;

        if ($activeHistoryLine)
        {
            $activeHistoryLine->setActive($active);
        }
        else
        {
            $historyAdministrationClass = $this->getActiveHistoryClass($administrationEntityClass);
            $activeHistoryLine = new $historyAdministrationClass($administration, $survey, $active);
            $em->persist($activeHistoryLine);
            $codeResponse = Response::HTTP_CREATED;
        }
        $em->flush();
        return [$activeHistoryLine, $codeResponse];
    }
}
