<?php


namespace App\Common\Traits;

use App\Entity\DocumentaryStructureLinkHistory;
use App\Entity\DocumentaryStructures;
use App\Entity\Establishments;
use App\Entity\Surveys;
use Exception;
use Symfony\Component\HttpFoundation\Response;

trait DocumentaryStructureLinkHistoryTrait
{

    /**
     * Get documentary structure link history by documentary structure.
     * @param DocumentaryStructures $docStruct Documentary structure doctrine entity.
     * @param bool $throwError Throw error if no history line found.
     * @return array All link history line found in database.
     * @throws Exception 404 : No history found.
     */
    private function getDocStructLinkHistoryByDocStruct(DocumentaryStructures $docStruct, bool $throwError = true)
    : array
    {
        $history = $this->managerRegistry->getRepository(DocumentaryStructureLinkHistory::class)
            ->findBy(['documentaryStructure' => $docStruct]);

        if (count($history) === 0 && $throwError)
        {
            throw new Exception('No documentary structure history found.', Response::HTTP_NOT_FOUND);
        }
        return $history;
    }

    /**
     * Get documentary structure link history by establishment.
     * @param Establishments $establishment Establishment doctrine entity.
     * @param bool $throwError Throw error if no history line found.
     * @return array All link history line found in database.
     * @throws Exception 404 : No history found.
     */
    private function getDocStructLinkHistoryByEstablishment(Establishments $establishment, bool $throwError = true)
    : array
    {
        $history = $this->managerRegistry->getRepository(DocumentaryStructureLinkHistory::class)
            ->findBy(['establishment' => $establishment]);

        if (count($history) === 0 && $throwError)
        {
            throw new Exception('No documentary structure history found.', Response::HTTP_NOT_FOUND);
        }
        return $history;
    }

    /**
     * Get documentary structure link history line.
     * @param DocumentaryStructures $docStruct Documentary structure doctrine entity.
     * @param Surveys $survey Survey of link history line.
     * @param bool $throwError To throw error if no line found.
     * @return DocumentaryStructureLinkHistory|null Documentary structure link history line doctrine entity.
     * @throws Exception 404 : No documentary structure link history line found.
     */
    private function getDocStructLinkHistoryLine(DocumentaryStructures $docStruct, Surveys $survey,
                                                 bool $throwError = true): ?DocumentaryStructureLinkHistory
    {
        $historyLine = $this->managerRegistry->getRepository(DocumentaryStructureLinkHistory::class)
            ->findOneBy(['documentaryStructure' => $docStruct, 'survey' => $survey]);
        if (!$historyLine && $throwError)
        {
            throw new Exception('No documentary structure link history line found',
                Response::HTTP_NOT_FOUND);
        }
        return $historyLine;
    }

    /**
     * Change establishment linked with documentary structure in history for last survey.
     * @param DocumentaryStructures $docStruct Documentary structure doctrine entity.
     * @param int $establishmentId Establishment id to link with documentary structure.
     * @throws Exception 404 : Establishment not found.
     */
    private function updateDocStructLinkHistoryForLastSurvey(DocumentaryStructures $docStruct, int $establishmentId)
    {
        $lastSurvey = $this->getLastSurvey();
        $this->insertOrUpdateDocStructLinkHistory($docStruct, $lastSurvey, $establishmentId);
    }

    /**
     * Insert or update documentary structure link history line.
     * @param DocumentaryStructures $docStruct Documentary structure doctrine entity.
     * @param Surveys $survey Survey of history line.
     * @param int $establishmentId Establishment id to link with documentary structure.
     * @return array Index 0 : Link history line doctrine entity object. Index 1 : HTTP code response.
     * @throws Exception 404 : No establishment found with this id.
     */
    private function insertOrUpdateDocStructLinkHistory(DocumentaryStructures $docStruct, Surveys $survey,
                                                        int $establishmentId): array
    {
        $historyLine = $this->getDocStructLinkHistoryLine($docStruct, $survey, false);
        $establishment = null;

        $em = $this->managerRegistry->getManager();
        $responseCode = Response::HTTP_OK;

        if ($historyLine)
        {
            if ($historyLine->getEstablishment()->getId() !== $establishmentId)
            {
                $establishment = $this->getEstablishmentById($establishmentId);
                $historyLine->setEstablishment($establishment);
            }
        }
        else
        {
            $establishment = $this->getEstablishmentById($establishmentId);
            $historyLine = new DocumentaryStructureLinkHistory($docStruct, $establishment, $survey);
            $em->persist($historyLine);
            $responseCode = Response::HTTP_CREATED;
        }

        if ($establishment != null)
        {
            $lastSurvey = $this->getLastSurvey();
            if ($survey->getId() === $lastSurvey->getId())
            {
                $docStruct->setEstablishment($establishment);
            }
        }

        $em->flush();
        return [$historyLine, $responseCode];
    }
}
