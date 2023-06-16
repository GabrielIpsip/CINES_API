<?php


namespace App\Common\Traits;

use App\Entity\DataTypes;
use App\Entity\Texts;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolationListInterface;

trait TextsTrait
{
    /**
     * Get all text constraint in database.
     * @return array Array that contains all text doctrine entity.
     * @throws Exception 404 : No text found.
     */
    private function getAllTexts(): array
    {
        $texts = $this->managerRegistry->getRepository(Texts::class)->findAll();
        if (!$texts)
        {
            throw new Exception('No text information found.', Response::HTTP_NOT_FOUND);
        }
        return $texts;
    }

    /**
     * Check if this dataType already has text information associated whit him.
     * @param int $dataTypeId Id of dataType.
     * @throws Exception 409 : DataType already has text information.
     */
    private function checkIfAlreadyHasTextInfo(int $dataTypeId)
    {
        $existingText = $this->managerRegistry->getRepository(Texts::class)->find($dataTypeId);
        if ($existingText)
        {
            throw new Exception('Data type $dataTypeId already have text information.',
                Response::HTTP_CONFLICT);
        }
    }

    /**
     * Get text by data type.
     * @param DataTypes $dataType DataType doctrine entity.
     * @return Texts Text doctrine entity with this dataType.
     * @throws Exception 404 : No text found.
     */
    private function getTextByDataType(DataTypes $dataType): Texts
    {
        $text = $this->managerRegistry->getRepository(Texts::class)
            ->findOneBy(array('dataType' => $dataType));
        if (!$text)
        {
            throw new Exception('No text information with id : ' . $dataType->getId(),
                Response::HTTP_NOT_FOUND);
        }
        return $text;
    }

    /**
     * Check if text information are corrects.
     * @param Texts $text Text doctrine entity to check.
     * @param ConstraintViolationListInterface $validationErrors List of constraints.
     * @throws Exception 400 : Error in text information.
     */
    private function checkInfosIsValid(Texts $text, ConstraintViolationListInterface $validationErrors)
    {
        if (count($validationErrors) > 0)
        {
            throw new Exception($validationErrors, Response::HTTP_BAD_REQUEST);
        }

        $minLength = $text->getMinLength();
        $maxLength = $text->getMaxLength();

        if ($minLength && $maxLength && $minLength >= $maxLength)
        {
            throw new Exception("MinLength must be smaller than maxLength.", Response::HTTP_BAD_REQUEST);
        }
    }
}