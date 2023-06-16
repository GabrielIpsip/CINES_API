<?php


namespace App\Common\Traits;


use App\Common\Enum\AdministrationType;
use App\Entity\AbstractEntity\Administrations;
use App\Entity\DocumentaryStructures;
use App\Entity\Establishments;
use App\Entity\PhysicalLibraries;
use Exception;
use Symfony\Component\HttpFoundation\Response;

trait AdministrationsTrait
{
    /**
     * Get administration by entityClass an id.
     * @param string $entityClass To know which repository to use. (ex: Establishments::class)
     * @param int $administrationId Id of administration in database.
     * @return Administrations Return administration with this id in this repository.
     * @throws Exception 404 : No administration found with this id in this repository.
     */
    private function getAdministrationById(string $entityClass, int $administrationId): Administrations
    {
        $administration = $this->managerRegistry->getRepository($entityClass)->find($administrationId);
        if (!$administration)
        {
            throw new Exception('No '. self::ADMINISTRATION_NAME[$entityClass] .
                ' found with this id : ' . $administrationId,
                Response::HTTP_NOT_FOUND);
        }
        return $administration;
    }

    /**
     * Get administration entity class by administration type id.
     * @param int $administrationTypeId Id of administration type.
     * @return string Administration entity class name (Ex: Establishments::class)
     */
    private function getAdministrationClassByAdministrationType(int $administrationTypeId): ?string
    {
        switch ($administrationTypeId)
        {
            case AdministrationType::institution:
                return Establishments::class;
            case AdministrationType::documentaryStructure:
                return DocumentaryStructures::class;
            case AdministrationType::physicalLibrary:
                return PhysicalLibraries::class;
        }
        return null;
    }
}