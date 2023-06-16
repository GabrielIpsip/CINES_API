<?php

namespace App\Common\Traits;

use App\Entity\EstablishmentActiveHistory;
use App\Entity\Establishments;
use Exception;
use Symfony\Component\HttpFoundation\Response;

trait EstablishmentsTrait
{
    /**
     * Get establishment by id.
     * @param int $establishmentId Establishment id.
     * @return Establishments Establishment entity.
     * @throws Exception 404 : no establishment found.
     */
    private function getEstablishmentById(int $establishmentId): Establishments
    {
        $establishment = $this->managerRegistry->getRepository(Establishments::class)->find($establishmentId);
        if (!$establishment)
        {
            throw new Exception('No establishment with this id : ' . $establishmentId,
                Response::HTTP_NOT_FOUND);
        }
        return $establishment;
    }

    /**
     * Get all establishment in array indexed by survey id.
     * @param array $surveys Establishment activated for these surveys.
     * @return array Array with all establishment for these surveys.
     *               ex: indexedEstablishment[surveyId] = [Array of establishment]
     */
    private function getAllActiveEstablishmentByYear(array $surveys): array
    {
        $establishments = $this->managerRegistry->getRepository(Establishments::class)->findAll();
        $indexedEstablishments = [];
        foreach ($surveys as $survey)
        {
            $establishmentActiveHistory = $this->managerRegistry->getRepository(EstablishmentActiveHistory::class)
                ->findBy(['survey' => $survey]);
            $indexedEstablishments[$survey->getId()] = [];

            foreach ($establishments as $establishment)
            {
                $active = $establishment->getActive();
                foreach ($establishmentActiveHistory as $activeHistoryLine)
                {
                    if ($establishment->getId() === $activeHistoryLine->getAdministration()->getId())
                    {
                        $active = $activeHistoryLine->getActive();
                        break;
                    }
                }
                if ($active)
                {
                    array_push($indexedEstablishments[$survey->getId()], $establishment);
                }
            }
        }
        return $indexedEstablishments;
    }
}