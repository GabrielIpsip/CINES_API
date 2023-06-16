<?php

namespace App\Common\Traits;

use App\Common\Enum\AdministrationType;
use App\Entity\AdministrationTypes;
use Exception;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Response;

trait AdministrationTypesTrait
{

    /**
     * Get all administration types.
     * @return array Return all administration type contains in database.
     * @throws Exception 404 : No administration type found.
     */
    private function getAllAdministrationTypes(): array
    {
        $administrationTypes = $this->managerRegistry->getRepository(AdministrationTypes::class)->findAll();
        if (count($administrationTypes) === 0)
        {
            throw new Exception('No administration type found.', Response::HTTP_NOT_FOUND);
        }
        return $administrationTypes;
    }

    /**
     * Get administration type by id.
     * @param int $id Id of administration type.
     * @return AdministrationTypes Administration type identified with this id.
     * @throws Exception 404 : No administration type with this id.
     */
    private function getAdministrationTypeById(int $id): AdministrationTypes
    {
        $administrationType = $this->managerRegistry->getRepository(AdministrationTypes::class)->find($id);
        if (!$administrationType)
        {
            throw new Exception('No administration type with id : ' . $id,
                Response::HTTP_NOT_FOUND);
        }
        return $administrationType;
    }

    /**
     * Get administration type by name.
     * @param string $name Name of administration type.
     * @return AdministrationTypes Administration doctrine entity.
     * @throws Exception 404 : No administration type found with this name.
     */
    private function getAdministrationTypeByName(string $name): AdministrationTypes
    {
        $administrationType = $this->managerRegistry->getRepository(AdministrationTypes::class)
            ->findOneBy(array('name' => $name));
        if (!$administrationType)
        {
            throw new Exception('No administration type with name : ' . $name,
                Response::HTTP_NOT_FOUND);
        }
        return $administrationType;
    }

    private function getAdministrationTypeIdByName(string $name): int
    {
        $administrationTypeEnum = new ReflectionClass(AdministrationType::class);
        return $administrationTypeEnum->getConstant($name);
    }
}