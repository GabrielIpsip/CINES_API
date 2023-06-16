<?php

namespace App\Common\Traits;

use App\Controller\DocumentaryStructuresController;
use App\Entity\DocumentaryStructureActiveHistory;
use App\Entity\DocumentaryStructureLinkHistory;
use App\Entity\DocumentaryStructures;
use App\Entity\Establishments;
use App\Entity\Surveys;
use Exception;
use Symfony\Component\HttpFoundation\Response;

trait DocumentaryStructuresTrait
{
    /**
     * Return documentary structure entity in database by id.
     * @param int $docStructId Id of documentary structure.
     * @return DocumentaryStructures Documentary structure with this id.
     * @throws Exception 404 : No documentary structure found with this id.
     */
    private function getDocStructById(int $docStructId): DocumentaryStructures
    {
        $docStruct = $this->managerRegistry->getRepository(DocumentaryStructures::class)
            ->find($docStructId);
        if (!$docStruct)
        {
            throw new Exception('No documentary structure found with this id: ' . $docStructId,
                Response::HTTP_NOT_FOUND);
        }
        return $docStruct;
    }

    /**
     * Return serial documentary structure entity by id.
     * @param array $docStructId Array with all documentary structure id.
     * @return array|DocumentaryStructures Array with all documentary structure entity with these id.
     * @throws Exception 404 : No documentary structure found.
     */
    private function getSerialDocStructById(array $docStructId): array
    {
        $docStruct = $this->managerRegistry->getRepository(DocumentaryStructures::class)
            ->findBy(array('id' => $docStructId));

        if (!$docStruct)
        {
            throw new Exception('No documentary structure with id : ' . $docStructId,
                Response::HTTP_NOT_FOUND);
        }

        return $docStruct;
    }

    /**
     * Get all documentary structure linked with an establishment.
     * @param Establishments $establishment Establishment entity.
     * @return array Array of documentary structure associated with this establishment.
     * @throws Exception 404 : No documentary structure found.
     */
    private function getDocStructByEstablishment(Establishments $establishment): array
    {
        $docStructs = $this->managerRegistry->getRepository(DocumentaryStructures::class)
            ->findBy(array('establishment' => $establishment));
        if (count($docStructs) === 0)
        {
            throw new Exception('This establishment not has associated documentary structure',
                Response::HTTP_NOT_FOUND);
        }
        return $docStructs;
    }

    /**
     * Get documentary structures by establishment linked with there.
     * @param int $establishmentId Id of establishment.
     * @return array Array that contains documentary structure doctrine entity linked with this establishment.
     * @throws Exception 404 : No documentary structure associated with this establishment.
     */
    private function getDocStructByEstablishmentId(int $establishmentId): array
    {
        $doctrine = $this->managerRegistry;

        $establishment = $doctrine->getRepository(Establishments::class)
            ->find($establishmentId);
        return $this->getDocStructByEstablishment($establishment);
    }

    /**
     * Check if total progress of documentary structure is 100%.
     * @param DocumentaryStructures $docStruct Documentary structure progress to check.
     * @param Surveys $survey Survey to check total progress.
     * @throws Exception 400 : Total progress is not 100%.
     */
    private function checkTotalProgressDocStruct(DocumentaryStructures $docStruct, Surveys $survey)
    {
        $docStruct = DocumentaryStructuresController::getFormattedDocStructByEntityForResponse($docStruct);
        $docStructProgress = $this->managerRegistry->getRepository(DocumentaryStructures::class)
            ->getTotalProgress([$docStruct], $survey);
        $docStructProgress = $docStructProgress[$docStruct['id']];
        if (!$docStructProgress)
        {
            $docStructProgress = 0;
        }
        if ($docStructProgress != 100)
        {
            throw new Exception('Can\'t valid survey. Total progress of documentary structure is '
                . $docStructProgress . '%, must be 100%', Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Get all documentary structures in array indexed by year, and for each year, indexed by establishment id.
     * @param array $surveys Documentary structure activated for these surveys.
     * @return array Array with all documentary structure for these surveys.
     *               ex : IndexedDocStruct[surveyId][establishmentId] = [Array of documentary structures]
     */
    private function getAllDocStructIndexedByYearAndEstablishment(array $surveys): array
    {
        $docStructs = $this->managerRegistry->getRepository(DocumentaryStructures::class)->findAll();
        $indexedDocStruct = [];
        foreach ($surveys as $survey)
        {
            $docStructActiveHistory = $this->managerRegistry->getRepository(DocumentaryStructureActiveHistory::class)
                ->findBy(['survey' => $survey]);
            $docStructLinkHistory = $this->managerRegistry->getRepository(DocumentaryStructureLinkHistory::class)
                ->findBy(['survey' => $survey]);
            $indexedDocStruct[$survey->getId()] = [];

            foreach ($docStructs as $docStruct)
            {
                $active = $docStruct->getActive();
                foreach ($docStructActiveHistory as $activeHistoryLine)
                {
                    if ($docStruct->getId() === $activeHistoryLine->getAdministration()->getId()) {
                        $active = $activeHistoryLine->getActive();
                        break;
                    }
                }

                if ($active)
                {
                    $establishmentId = $docStruct->getEstablishment()->getId();
                    foreach ($docStructLinkHistory as $linkHistoryLine)
                    {
                        if ($docStruct->getId() === $linkHistoryLine->getDocumentaryStructure()->getId())
                        {
                            $establishmentId = $linkHistoryLine->getEstablishment()->getId();
                        }
                    }
                    if (!array_key_exists($establishmentId, $indexedDocStruct[$survey->getId()]))
                    {
                        $indexedDocStruct[$survey->getId()][$establishmentId] = [];
                    }
                    array_push($indexedDocStruct[$survey->getId()][$establishmentId], $docStruct);
                }
            }
        }
        return $indexedDocStruct;
    }

}