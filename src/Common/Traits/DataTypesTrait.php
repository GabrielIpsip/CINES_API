<?php

namespace App\Common\Traits;

use App\Common\Enum\AdministrationType;
use App\Entity\DataTypes;
use App\Entity\DocumentaryStructures;
use App\Entity\Establishments;
use App\Entity\Groups;
use App\Entity\PhysicalLibraries;
use Exception;
use Symfony\Component\HttpFoundation\Response;

trait DataTypesTrait
{

    use TypesTrait;

    /**
     * Get DataType by this id.
     * @param int $dataTypeId Id of dataType in database.
     * @return DataTypes DataType identified by this id.
     * @throws Exception 404 : No dataType found.
     */
    private function getDataTypeById(int $dataTypeId): DataTypes
    {
        $dataType = $this->managerRegistry->getRepository(DataTypes::class)
            ->find($dataTypeId);
        if (!$dataType)
        {
            throw new Exception('No data type found with this id: ' . $dataTypeId,
                Response::HTTP_NOT_FOUND);
        }
        return $dataType;
    }

    /**
     * Get dataTypes by these code.
     * @param array $codes Array of code of data type entity.
     * @return array Array of data type entity.
     * @throws Exception 404: No data type found with this code.
     */
    private function getDataTypeByCodes(array $codes): array
    {
        $dataType = $this->managerRegistry->getRepository(DataTypes::class)->findBy(array('code' => $codes));
        if (!$dataType)
        {
            throw new Exception('No data type found.', Response::HTTP_NOT_FOUND);
        }
        return $dataType;
    }

    /**
     * Get all dataType ordered by group and dataType order.
     * @param bool $publicOnly To get just public dataTypes.
     * @return array Array if data type entity.
     * @throws Exception 404 : No data type found.
     */
    private function getAllDataTypeOrdered(bool $publicOnly = false): array
    {
        $establishmentDataTypes = $this->managerRegistry->getRepository(DataTypes::class)
            ->getAllOrderedDataTypeByAdminType(Establishments::class, $publicOnly);
        $docStructDataTypes = $this->managerRegistry->getRepository(DataTypes::class)
            ->getAllOrderedDataTypeByAdminType(DocumentaryStructures::class, $publicOnly);
        $physicLibDataTypes = $this->managerRegistry->getRepository(DataTypes::class)
            ->getAllOrderedDataTypeByAdminType(PhysicalLibraries::class, $publicOnly);

        $sortedGroup = $this->getAllGroupSortedByParent();

        $establishmentDataTypes = $this->sortDataTypeByGroup($establishmentDataTypes, $sortedGroup,
            AdministrationType::institution);
        $docStructDataTypes = $this->sortDataTypeByGroup($docStructDataTypes, $sortedGroup,
            AdministrationType::documentaryStructure);
        $physicLibDataTypes = $this->sortDataTypeByGroup($physicLibDataTypes, $sortedGroup,
            AdministrationType::physicalLibrary);

        $dataTypes = array_merge($establishmentDataTypes, $docStructDataTypes, $physicLibDataTypes);
        if (count($dataTypes) === 0)
        {
            throw new Exception('No data type found.', Response::HTTP_NOT_FOUND);
        }

        return $dataTypes;
    }

    /**
     * Get all group ordered by administration type.
     * @param string $entityClass Administration class name (ex:Establishment::class)
     * @param Groups[] $sortedGroups Sorted group by parent id and id.
     * @param bool $publicOnly False to show public dataType, else false.
     * @return DataTypes[] DataTypes doctrine entity array.
     */
    private function getAllAdministrationTypeDataTypeOrdered(string $entityClass, array $sortedGroups,
                                                             bool $publicOnly = false): array
    {
        $dataTypes = $this->managerRegistry->getRepository(DataTypes::class)
            ->getAllOrderedDataTypeByAdminType($entityClass, $publicOnly);

        return $this->sortDataTypeByGroup($dataTypes, $sortedGroups,
            $this->getAdministrationTypeIdByEntityClass($entityClass));

    }

    /**
     * Sort dataType doctrine array by group.
     * @param DataTypes[] $sortedDataTypes DataTypes sorted by group and group order.
     * @param Groups[] $sortedGroups Group sorted by parent group and id.
     * @param int $administrationType Administration type id of data type to filter in array result.
     * @return DataTypes[] DataTypes doctrine entity array.
     */
    private function sortDataTypeByGroup(array $sortedDataTypes, array $sortedGroups, int $administrationType): array
    {
        $sortedDataTypeByGroup = [];
        foreach ($sortedGroups as $group)
        {
            if ($group->getAdministrationType()->getId() !== $administrationType)
            {
                continue;
            }

            foreach ($sortedDataTypes as $dataType)
            {
                if ($dataType->getGroup()->getId() === $group->getId())
                {
                    array_push($sortedDataTypeByGroup, $dataType);
                }
            }
        }

        return $sortedDataTypeByGroup;
    }

    /**
     * Check if dataType in database already exists with this code.
     * @param string $code Code to check in database.
     * @param int|null $dataTypeId To exclude an id in research.
     * @throws Exception 409 : Data type with this code already exists.
     */
    private function checkIfAlreadyExistsCode(string $code, int $dataTypeId = null)
    {
        $existingDataType = $this->managerRegistry->getRepository(DataTypes::class)
            ->findOneBy(array('code' => $code));
        if (($existingDataType && !$dataTypeId)
            || ($existingDataType && $dataTypeId && $existingDataType->getId() != $dataTypeId))
        {
            throw new Exception('Data type already exists with this code : ' . $code,
                Response::HTTP_CONFLICT);
        }
    }

    /**
     * Get constraint entity of dataType.
     * @param DataTypes $dataType DataType that you want get constraint type.
     * @return mixed|null Return Texts, Numbers, Operations or null if boolean or error.
     */
    private function getConstraint(DataTypes $dataType)
    {
        $constraintClass = self::TYPE_CLASS[$dataType->getType()->getName()]['class'];
        if (!$constraintClass)
        {
            return null;
        }
        return $this->managerRegistry->getRepository($constraintClass)->find($dataType->getId());
    }

    /**
     * Update name of dataType or create it if not exists.
     * @param DataTypes $existingDataType DataType whose names are to be updated.
     * @param array|null $names Array with name values. ex : $names['fr'] = 'french name'.
     * @throws Exception 404 : If language code not found.
     *                   400 : If name array does not contain the default lang value.
     */
    private function updateOrCreateName(DataTypes $existingDataType, ?array $names)
    {
        $existingName = $existingDataType->getName();
        if ($existingName)
        {
            $this->updateTranslation($names, $existingName,self::TABLE_NAME);
        }
        else
        {
            $nameContent = $this->addTranslation($names, self::TABLE_NAME);
            $existingDataType->setName($nameContent);
        }
    }

    /**
     * Update definition of dataType or create it if not exists.
     * @param DataTypes $existingDataType DataType whose definition are to be updated.
     * @param array|null $definitions Array with definition values. ex : $definition['fr'] = 'french definition'.
     * @throws Exception 404 : If language code not found.
     *                   400 : If definition array does not contain the default lang value.
     */
    private function updateOrCreateDefinition(DataTypes $existingDataType, ?array $definitions)
    {
        $existingDefinition = $existingDataType->getDefinition();
        if ($existingDefinition)
        {
            $this->updateTranslation(
                $definitions, $existingDefinition, self::TABLE_NAME);
        }
        else
        {
            $definitionContent = $this->addTranslation($definitions, self::TABLE_NAME);
            $existingDataType->setDefinition($definitionContent);
        }
    }

    /**
     * Update instruction of dataType or create it if not exists.
     * @param DataTypes $existingDataType DataType whose instructions are to be updated.
     * @param array|null $instructions Array with instruction values. ex : $instruction['fr'] = 'french instruction'.
     * @throws Exception 404 : If language code not found.
     *                   400 : If instruction array does not contain the default lang value.
     */
    private function updateOrCreateInstruction(DataTypes $existingDataType, ?array $instructions)
    {
        $existingInstruction = $existingDataType->getInstruction();
        if ($existingInstruction)
        {
            $this->updateTranslation($instructions, $existingInstruction, self::TABLE_NAME);
        }
        else
        {
            $instructionContent = $this->addTranslation($instructions, self::TABLE_NAME);
            $existingDataType->setInstruction($instructionContent);
        }
    }

    /** Update measure unit of dataType or create it if not exists.
     * @param DataTypes $existingDataType DataType whose measure units are to be updated.
     * @param array|null $measureUnits Array with measure units values. ex : $measureUnits['fr'] = 'french measure unit'.
     * @throws Exception 404 : If language code not found.
     *                   400 : If measure unit array does not contain the default lang value.
     */
    private function updateOrCreateMeasureUnit(DataTypes $existingDataType, ?array $measureUnits)
    {
        $existingMeasureUnit = $existingDataType->getMeasureUnit();
        if ($existingMeasureUnit)
        {
            $this->updateTranslation(
                $measureUnits, $existingDataType->getMeasureUnit(), self::TABLE_NAME);
        }
        else
        {
            $measureUnitContent = $this->addTranslation($measureUnits, self::TABLE_NAME);
            $existingDataType->setMeasureUnit($measureUnitContent);
        }
    }

    /** Update date of dataType or create it if not exists.
     * @param DataTypes $existingDataType DataType whose measure units are to be updated.
     * @param array|null $dates Array with dates values. ex : $dates['fr'] = 'french dates'.
     * @throws Exception 404 : If language code not found.
     *                   400 : If measure unit array does not contain the default lang value.
     */
    private function updateOrCreateDate(DataTypes $existingDataType, ?array $dates)
    {
        $existingDate = $existingDataType->getDate();
        if ($existingDate)
        {
            $this->updateTranslation(
                $dates, $existingDataType->getDate(), self::TABLE_NAME);
        }
        else
        {
            $dateContent = $this->addTranslation($dates, self::TABLE_NAME);
            $existingDataType->setDate($dateContent);
        }
    }

    /**
     * Update all group order of dataType in same group of dataType in argument, to avoid conflicts.
     * @param DataTypes $dataType DataType with the new group order.
     */
    private function updateGroupOrderForEachDataTypeOfGroup(DataTypes $dataType)
    {
        $dataTypeSameOrder = $this->managerRegistry->getRepository(DataTypes::class)
            ->findOneBy(array('groupOrder' => $dataType->getGroupOrder(), 'group' => $dataType->getGroup()));
        if ($dataTypeSameOrder && $dataTypeSameOrder->getId() !== $dataType->getId())
        {
            $dataTypeSameGroupArray = $this->managerRegistry->getRepository(DataTypes::class)
                ->findBy(array('group' => $dataType->getGroup()));
            foreach ($dataTypeSameGroupArray as $dataTypeSameGroup)
            {
                if ($dataTypeSameGroup->getGroupOrder() >= $dataType->getGroupOrder())
                {
                    $dataTypeSameGroup->setGroupOrder($dataTypeSameGroup->getGroupOrder() + 1);
                }
            }
        }
    }

    /**
     * Check if dataType has operation type.
     * @param DataTypes $dataType DataType to test.
     * @return bool True if is operation type, else false.
     */
    public function isOperationType(DataTypes $dataType): bool
    {
        return $dataType->getType()->getId() === $this->getOperationType()->getId();
    }

    /**
     * Get all dataType in database indexed by this id.
     * @param bool $publicOnly Get only public dataType.
     * @return array Array of dataType indexed by this id.
     * @throws Exception 404 : No data type found.
     */
    private function getAllDataTypesIndexed(bool $publicOnly = false): array
    {
        $dataTypes = $this->getAllDataTypeOrdered($publicOnly);

        $indexedDataTypes = array();

        foreach ($dataTypes as $dataType) {
            $indexedDataTypes[$dataType->getId()] = $dataType;
        }

        return $indexedDataTypes;
    }
}
