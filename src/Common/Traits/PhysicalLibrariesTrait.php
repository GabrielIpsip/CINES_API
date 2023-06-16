<?php


namespace App\Common\Traits;

use App\Entity\DocumentaryStructures;
use App\Entity\PhysicalLibraries;
use App\Entity\PhysicalLibraryActiveHistory;
use App\Entity\PhysicalLibraryLinkHistory;
use Exception;
use Symfony\Component\HttpFoundation\Response;

trait PhysicalLibrariesTrait
{
    /**
     * Get physical library with this id from database.
     * @param int $id Id of physical library
     * @return PhysicalLibraries Physical library doctrine entity with this id.
     * @throws Exception 404 : No physical library found.
     */
    private function getPhysicalLibraryById(int $id): PhysicalLibraries
    {
        $physicLib = $this->managerRegistry->getRepository(PhysicalLibraries::class)->find($id);
        if (!$physicLib)
        {
            throw new Exception('No physical library with id : ' . $id, Response::HTTP_NOT_FOUND);
        }
        return $physicLib;
    }

    /**
     * Get all physical libraries in array indexed by year, and for each year, indexed by documentary structure id.
     * @param array $surveys Physical libraries activated for these surveys.
     * @return array Array with all physical libraries for these surveys.
     *               ex : IndexedDocStruct[surveyId][docStructId] = [Array of physical libraries]
     */
    private function getAllPhysicLibIndexedByYearAndDocStruct(array $surveys): array
    {
        $physicLibs = $this->managerRegistry->getRepository(PhysicalLibraries::class)->findAll();
        $indexedPhysicLibs = [];
        foreach ($surveys as $survey)
        {
            $physicLibActiveHistory = $this->managerRegistry->getRepository(PhysicalLibraryActiveHistory::class)
                ->findBy(['survey' => $survey]);
            $physicLibLinkHistory = $this->managerRegistry->getRepository(PhysicalLibraryLinkHistory::class)
                ->findBy(['survey' => $survey]);
            $indexedPhysicLibs[$survey->getId()] = [];

            foreach ($physicLibs as $physicLib)
            {
                $active = $physicLib->getActive();
                foreach ($physicLibActiveHistory as $activeHistoryLine)
                {
                    if ($physicLib->getId() === $activeHistoryLine->getAdministration()->getId()) {
                        $active = $activeHistoryLine->getActive();
                        break;
                    }
                }

                if ($active)
                {
                    $docStructId = $physicLib->getDocumentaryStructure()->getId();
                    foreach ($physicLibLinkHistory as $linkHistoryLine)
                    {
                        if ($physicLib->getId() === $linkHistoryLine->getPhysicalLibrary()->getId())
                        {
                            $docStructId = $linkHistoryLine->getDocumentaryStructure()->getId();
                        }
                    }
                    if (!array_key_exists($docStructId, $indexedPhysicLibs[$survey->getId()]))
                    {
                        $indexedPhysicLibs[$survey->getId()][$docStructId] = [];
                    }
                    array_push($indexedPhysicLibs[$survey->getId()][$docStructId], $physicLib);
                }
            }
        }
        return $indexedPhysicLibs;
    }

    //// Sort Order part ///////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Update sort order of physical library of documentary structure.
     * @param PhysicalLibraries $existingPhysicLib Old physical library with old sort order.
     * @param PhysicalLibraries $newPhysicLib Physical library to integrate in sorted physical library list, with new
     *                                        sort older.
     * @param DocumentaryStructures $docStruct Documentary structure parent for physical library list.
     */
    private function updateSortOrderForPhysicLib(PhysicalLibraries $existingPhysicLib, PhysicalLibraries $newPhysicLib,
                                                 DocumentaryStructures $docStruct)
    {
        $sameOrderPhysicLib = $this->managerRegistry->getRepository(PhysicalLibraries::class)
            ->findOneBy(array('documentaryStructure' => $docStruct, 'sortOrder' => $newPhysicLib->getSortOrder()));

        if ($sameOrderPhysicLib)
        {
            $sameOrderPhysicLib->setSortOrder($existingPhysicLib->getSortOrder());
        }
        else
        {
            $this->AddOrCreateSortOrder($newPhysicLib, $docStruct);
        }
    }

    /**
     * Generate sort order of $physicLib if doesn't exist, else insert in list and adjust already sorted list.
     * @param PhysicalLibraries $physicLib Physical library doctrine entity with the new sort order or the sort order to
     *                                     generate.
     * @param DocumentaryStructures $docStruct Documentary structure parent for physical library list.
     */
    private function AddOrCreateSortOrder(PhysicalLibraries $physicLib, DocumentaryStructures $docStruct)
    {
        if (!$physicLib->getSortOrder())
        {
            $physicLib->setSortOrder($this->generateSortOrder($docStruct));
        }
        else
        {
            $this->adjustSortOrderOfPhysicLibOfDocStruct($docStruct, $physicLib->getSortOrder());
        }
    }

    /**
     * Update sort order for all physical libraries of documentary structure.
     * @param DocumentaryStructures $docStruct
     * @param int $sortOrder New sort order added in physical library list of documentary structure.
     */
    private function adjustSortOrderOfPhysicLibOfDocStruct(DocumentaryStructures $docStruct, int $sortOrder)
    {
        $physicLibArray = $this->managerRegistry->getRepository(PhysicalLibraries::class)
            ->findBy(array('documentaryStructure' => $docStruct));

        foreach ($physicLibArray as $physicLib)
        {
            if ($physicLib->getSortOrder() >= $sortOrder)
            {
                $physicLib->setSortOrder($physicLib->getSortOrder() + 1);
            }
        }
    }

    /**
     * Generate a new and not used sort order.
     * @param DocumentaryStructures $docStruct Documentary structure parent.
     * @return int The sort order.
     */
    private function generateSortOrder(DocumentaryStructures $docStruct): int
    {
        $physicLib = $this->managerRegistry->getRepository(PhysicalLibraries::class)
            ->findOneBy(array('documentaryStructure' => $docStruct), array('sortOrder' => 'DESC'));
        if ($physicLib)
        {
            return $physicLib->getSortOrder() + 1;
        }
        return 1;
    }
}