<?php

namespace App\Common\Traits;

use App\Entity\DataTypes;
use App\Entity\EstablishmentDataValues;
use App\Entity\Establishments;
use App\Entity\Surveys;
use Exception;
use Symfony\Component\HttpFoundation\Response;

trait EstablishmentDataValuesTrait
{

    /**
     * Return data value for an establishment.
     * @param DataTypes $dataType DataType of value.
     * @param Establishments $establishment Establishment of value.
     * @param Surveys $survey Survey of value.
     * @return EstablishmentDataValues Establishment data value entity.
     * @throws Exception 404 : No data value found.
     */
    private function getEstablishmentDataValue(DataTypes $dataType, Establishments $establishment, Surveys $survey)
    : EstablishmentDataValues
    {
        $dataValue = $this->managerRegistry->getRepository(EstablishmentDataValues::class)
            ->findOneBy(array('dataType' => $dataType, 'establishment' => $establishment, 'survey' => $survey));
        if (!$dataValue)
        {
            throw new Exception('No data value found.', Response::HTTP_NOT_FOUND);
        }
        return $dataValue;
    }
}