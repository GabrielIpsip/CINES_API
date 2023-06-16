<?php


namespace App\Common\Traits;


use App\Entity\EstablishmentTypes;
use Exception;
use Symfony\Component\HttpFoundation\Response;

trait EstablishmentTypesTrait
{

    /**
     * Return establishment type by id.
     * @param int $typeId Id of establishment type in database.
     * @return EstablishmentTypes Establishment type doctrine entity.
     * @throws Exception 404 : No establishment type found.
     */
    private function getEstablishmentTypeById(int $typeId): EstablishmentTypes
    {
        $establishmentType = $this->managerRegistry->getRepository(EstablishmentTypes::class)
            ->find($typeId);

        if (!$establishmentType)
        {
            throw new Exception('No establishment type with id : ' . $typeId, Response::HTTP_NOT_FOUND);
        }
        return $establishmentType;
    }

    /**
     * Get all establishment type in database.
     * @return array
     * @throws Exception
     */
    private function getAllEstablishmentTypes(): array
    {
        $establishmentTypes = $this->managerRegistry->getRepository(EstablishmentTypes::class)->findAll();
        if (count($establishmentTypes) === 0)
        {
            throw new Exception('No establishment type found.', Response::HTTP_NOT_FOUND);
        }
        return $establishmentTypes;
    }
}