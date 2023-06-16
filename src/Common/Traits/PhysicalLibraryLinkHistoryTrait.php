<?php


namespace App\Common\Traits;

use App\Entity\DocumentaryStructures;
use App\Entity\PhysicalLibraryLinkHistory;
use App\Entity\PhysicalLibraries;
use App\Entity\Surveys;
use Exception;
use Symfony\Component\HttpFoundation\Response;

trait PhysicalLibraryLinkHistoryTrait
{
    /**
     * Get physical library link history by physical library.
     * @param PhysicalLibraries $physicLib Physical library doctrine entity.
     * @param bool $throwError Throw error if no history line found.
     * @return array All link history line found in database.
     * @throws Exception 404 : No history found.
     */
    private function getPhysicLibLinkHistoryByPhysicLib(PhysicalLibraries $physicLib, bool $throwError = true): array
    {
        $history = $this->managerRegistry->getRepository(PhysicalLibraryLinkHistory::class)
            ->findBy(['physicalLibrary' => $physicLib]);

        if (count($history) === 0 && $throwError)
        {
            throw new Exception('No physical library history found.', Response::HTTP_NOT_FOUND);
        }
        return $history;
    }

    /**
     * Get physical library link history by documentary structure.
     * @param DocumentaryStructures $docStruct Documentary structure doctrine entity.
     * @param bool $throwError Throw error if no history line found.
     * @return array All link history line found in database.
     * @throws Exception 404 : No history found.
     */
    private function getPhysicLibLinkHistoryByDocStruct(DocumentaryStructures $docStruct, bool $throwError = true)
    : array
    {
        $history = $this->managerRegistry->getRepository(PhysicalLibraryLinkHistory::class)
            ->findBy(['documentaryStructure' => $docStruct]);

        if (count($history) === 0 && $throwError)
        {
            throw new Exception('No physical library history found.', Response::HTTP_NOT_FOUND);
        }
        return $history;
    }

    /**
     * Get physical library link history line.
     * @param PhysicalLibraries $physicLib Physical library doctrine entity.
     * @param Surveys $survey Survey of link history line.
     * @param bool $throwError To throw error if no line found.
     * @return PhysicalLibraryLinkHistory|null Physical library link history line doctrine entity.
     * @throws Exception 404 : No physical library link history line found.
     */
    private function getPhysicLibLinkHistoryLine(PhysicalLibraries $physicLib, Surveys $survey,
                                                 bool $throwError = true): ?PhysicalLibraryLinkHistory
    {
        $historyLine = $this->managerRegistry->getRepository(PhysicalLibraryLinkHistory::class)
            ->findOneBy(['physicalLibrary' => $physicLib, 'survey' => $survey]);
        if (!$historyLine && $throwError)
        {
            throw new Exception('No physical library link history line found',
                Response::HTTP_NOT_FOUND);
        }
        return $historyLine;
    }

    /**
     * Change documentary structure linked with physical library in history for last survey.
     * @param PhysicalLibraries $physicLib Physical library doctrine entity.
     * @param int $docStructId Documentary structure id to link with physical library.
     * @throws Exception 404 : Documentary structure not found.
     */
    private function updateDocStructLinkHistoryForLastSurvey(PhysicalLibraries $physicLib, int $docStructId)
    {
        $lastSurvey = $this->getLastSurvey();
        $this->insertOrUpdatePhysicLibLinkHistory($physicLib, $lastSurvey, $docStructId);
    }

    /**
     * Insert or update physical library link history line.
     * @param PhysicalLibraries $physicLib Physical library doctrine entity.
     * @param Surveys $survey Survey of history line.
     * @param int $docStructId Documentary structure id to link with physical library.
     * @return array Index 0 : Link history line doctrine entity object. Index 1 : HTTP code response.
     * @throws Exception 404 : No documentary structure found with this id.
     */
    private function insertOrUpdatePhysicLibLinkHistory(PhysicalLibraries $physicLib, Surveys $survey,
                                                       int $docStructId): array
    {
        $historyLine = $this->getPhysicLibLinkHistoryLine($physicLib, $survey, false);
        $docStruct = null;

        $em = $this->managerRegistry->getManager();
        $responseCode = Response::HTTP_OK;

        if ($historyLine)
        {
            if ($historyLine->getDocumentaryStructure()->getId() !== $docStructId)
            {
                $docStruct = $this->getDocStructById($docStructId);
                $historyLine->setDocumentaryStructure($docStruct);
            }
        }
        else
        {
            $docStruct = $this->getDocStructById($docStructId);
            $historyLine = new PhysicalLibraryLinkHistory($physicLib, $docStruct, $survey);
            $em->persist($historyLine);
            $responseCode = Response::HTTP_CREATED;
        }

        if ($docStruct != null)
        {
            $lastSurvey = $this->getLastSurvey();
            if ($survey->getId() === $lastSurvey->getId())
            {
                $physicLib->setDocumentaryStructure($docStruct);
            }
        }

        $em->flush();
        return [$historyLine, $responseCode];
    }
}
