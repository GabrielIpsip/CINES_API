<?php


namespace App\Common\Traits;

use App\Entity\DataTypes;
use App\Entity\Numbers;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationListInterface;

trait NumbersTrait
{

    /**
     * Get number constraint for data type id selection.
     * @param array $dataTypeId Array of data type id.
     * @return array Array of Numbers doctrine entity.
     */
    private function getAllNumbersByDataTypeId(array $dataTypeId): array
    {
        $dataTypes = $this->managerRegistry->getRepository(DataTypes::class)->findBy(['id' => $dataTypeId]);
        return $this->managerRegistry->getRepository(Numbers::class)->findBy(['dataType' => $dataTypes]);
    }

    /**
     * Get all number constraint in database.
     * @return array Array that contains all number doctrine entity.
     * @throws Exception 404 : No number found.
     */
    private function getAllNumbers(): array
    {
        $numbers = $this->managerRegistry->getRepository(Numbers::class)->findAll();
        if (!$numbers)
        {
            throw new Exception('No number information found.', Response::HTTP_NOT_FOUND);
        }
        return $numbers;
    }

    /**
     * Get number by data type.
     * @param DataTypes $dataType DataType doctrine entity.
     * @return Numbers Number doctrine entity with this dataType.
     * @throws Exception 404 : No number found.
     */
    private function getNumberByDataType(DataTypes $dataType): Numbers
    {
        $number = $this->managerRegistry->getRepository(Numbers::class)
            ->findOneBy(array('dataType' => $dataType));
        if (!$number)
        {
            throw new Exception('No number information with id : ' . $dataType->getId(),
                Response::HTTP_NOT_FOUND);
        }
        return $number;
    }

    /**
     * Check if this dataType already has number information associated whit him.
     * @param int $dataTypeId Id of dataType.
     * @throws Exception 409 : DataType already has number information.
     */
    private function checkIfAlreadyHasNumberInfo(int $dataTypeId)
    {
        $existingNumber = $this->managerRegistry->getRepository(Numbers::class)->find($dataTypeId);
        if ($existingNumber)
        {
            throw new Exception('Data type ' . $dataTypeId . ' already has number information.',
                Response::HTTP_CONFLICT);
        }
    }

    /**
     * Check if number information are corrects.
     * @param Numbers $number Number doctrine entity to check.
     * @param ConstraintViolationListInterface $validationErrors List of constraints.
     * @throws Exception 400 : Error in number information.
     */
    private function checkIfValidNumber(Numbers $number, ConstraintViolationListInterface $validationErrors)
    {
        if (count($validationErrors) > 0)
        {
            throw new Exception($validationErrors, Response::HTTP_BAD_REQUEST);
        }

        $min = $number->getMin();
        $max = $number->getMax();
        $minAlert = $number->getMinAlert();
        $maxAlert = $number->getMaxAlert();

        $error = [];
        if ($max && $min && $max <= $min)
        {
            array_push($error, 'Max can\'t be greater or equal than min.');
        }

        if ($maxAlert && $max && $maxAlert > $max)
        {
            array_push($error, 'Max alert can\'t be greater than max.');
        }

        if ($min && $minAlert && $minAlert < $min)
        {
            array_push($error, 'Min alert can\'t be smaller than min.');
        }

        if ($maxAlert && $minAlert && $minAlert >= $maxAlert)
        {
            array_push($error, 'Max alert can\'t be smaller or equal than min alert.');
        }
        if (count($error) > 0)
        {
            throw new Exception(implode(" ", $error), Response::HTTP_BAD_REQUEST);
        }
    }
}