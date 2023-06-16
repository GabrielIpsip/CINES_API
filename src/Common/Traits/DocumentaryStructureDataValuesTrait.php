<?php

namespace App\Common\Traits;

use App\Entity\DataTypes;
use App\Entity\DocumentaryStructureDataValues;
use App\Entity\Surveys;
use Exception;
use Symfony\Component\HttpFoundation\Response;

trait DocumentaryStructureDataValuesTrait
{

    /**
     * Get data values of documentary structures.
     * @param DataTypes $dataType DataType of value.
     * @param array $docStructs Array of documentary structure entity.
     * @param Surveys $survey Survey of value.
     * @return array Array of documentary structure data values entity.
     * @throws Exception 404 : No data value found.
     */
    private function getDocStructDataValues(DataTypes $dataType, array $docStructs, Surveys $survey)
    : array
    {
        $dataValues = $this->managerRegistry->getRepository(DocumentaryStructureDataValues::class)
            ->findBy(array('dataType' => $dataType, 'documentaryStructure' => $docStructs, 'survey' => $survey));
        if (count($dataValues) === 0)
        {
            throw new Exception('No data value found.', Response::HTTP_NOT_FOUND);
        }
        return $dataValues;
    }
}