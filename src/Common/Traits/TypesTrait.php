<?php

namespace App\Common\Traits;

use App\Common\Enum\AdministrationType;
use App\Entity\DocumentaryStructures;
use App\Entity\Establishments;
use App\Entity\PhysicalLibraries;
use App\Entity\Types;
use Exception;
use Symfony\Component\HttpFoundation\Response;

trait TypesTrait
{
    /**
     * Get all types in database.
     * @return array Array with all type doctrine entities.
     * @throws Exception 404 : No type found.
     */
    private function getAllTypes(): array
    {
        $types = $this->managerRegistry->getRepository(Types::class)->findAll();
        if (count($types) === 0)
        {
            throw new Exception('No type found.', Response::HTTP_NOT_FOUND);
        }
        return $types;
    }


    /**
     * Get type entity by id.
     * @param int $typeId Id of type in database.
     * @return Types Type identified by this id.
     * @throws Exception 404: No type found with this id.
     */
    private function getTypeById(int $typeId): Types
    {
        $type = $this->managerRegistry->getRepository(Types::class)->find($typeId);
        if (!$type)
        {
            throw new Exception('No type with id : ' . $type, Response::HTTP_NOT_FOUND);
        }
        return $type;
    }

    /**
     * Get type entity by name.
     * @param string $name Name of type in database.
     * @return Types Type doctrine entity that has this name.
     * @throws Exception 404 : No type found with this name.
     */
    private function getTypeByName(string $name): Types
    {
        $type = $this->managerRegistry->getRepository(Types::class)->findOneBy(array('name' => $name));
        if (!$type)
        {
            throw new Exception('No type found with this name : ' . $type,
                Response::HTTP_NOT_FOUND);
        }
        return $type;
    }

    /**
     * Return Operation type doctrine entity.
     * @return Types Operation Type.
     */
    private function getOperationType(): Types
    {
        $instance = self::TYPE_CLASS[self::OPERATION]['instance'];
        if (!$instance) {
            $instance = $this->managerRegistry->getRepository(Types::class)
                ->findOneBy(array('name' => self::OPERATION));
        }
        return $instance;
    }

    /**
     * Get administration type io type by entity class.
     * @param string $entityClass Administration entity class name. (Ex: Establishment::class)
     * @return int Administration type id.
     */
    private function getAdministrationTypeIdByEntityClass(string $entityClass): int
    {
        switch ($entityClass)
        {
            case Establishments::class:
                return AdministrationType::institution;
            case DocumentaryStructures::class:
                return AdministrationType::documentaryStructure;
            case PhysicalLibraries::class:
                return AdministrationType::physicalLibrary;
        }
        return -1;
    }
}
