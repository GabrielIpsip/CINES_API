<?php


namespace App\Common\Traits;

use App\Entity\AbstractEntity\AdministrationDataValues;
use App\Entity\AbstractEntity\Administrations;
use App\Entity\DataTypes;
use App\Entity\Operations;
use App\Entity\Surveys;
use App\Utils\StringTools;
use Exception;
use Symfony\Component\HttpFoundation\Response;

trait DataValuesTrait
{
    use OperationsTrait;

    /**
     * Get data value entity.
     * @param string $entityClass To know which repository to use. (ex: Establishments::class)
     * @param Surveys $survey Survey entity of dataValue.
     * @param Administrations $administration Administration entity of DataValue.
     * @param DataTypes $dataType DataType entity of DataValue.
     * @return AdministrationDataValues Administration entity.
     * @throws Exception 404 : DataValue not found.
     */
    private function getDataValue(string $entityClass, Surveys $survey, Administrations $administration,
                                  DataTypes $dataType): AdministrationDataValues
    {
        $value = $this->managerRegistry->getRepository(self::ADMINISTRATION_DATA_VALUE_CLASS[$entityClass])
            ->findOneBy(array('survey' => $survey,
                self::ADMINISTRATION_CAMEL_CASE[$entityClass] => $administration,
                'dataType' => $dataType));
        if (!$value)
        {
            throw new Exception('Value not found.', Response::HTTP_NOT_FOUND);
        }
        return $value;
    }

    /**
     * Update an administration data type value in database, or create it if not exists.
     * @param string $entityClass To know which repository to use. (ex: Establishments::class)
     * @param Surveys $survey Survey entity of dataValue.
     * @param Administrations $administration Administration entity of DataValue.
     * @param DataTypes $dataType DataType entity of DataValue.
     * @param string $value Value to insert for this administration data type value.
     * @return AdministrationDataValues Return administration data type created or updated.
     */
    private function updateOrInsertValue(string $entityClass, Surveys $survey, DataTypes $dataType,
                                         Administrations $administration, string $value): AdministrationDataValues
    {
        $doctrine = $this->managerRegistry;
        $valueInfo = $doctrine->getRepository(self::ADMINISTRATION_DATA_VALUE_CLASS[$entityClass])
            ->findOneBy(array('survey' => $survey,
                'dataType' => $dataType,
                self::ADMINISTRATION_CAMEL_CASE[$entityClass] => $administration));
        if ($valueInfo)
        {
            $valueInfo->setValue($value);
        }
        else
        {
            $administrationDataValuesClass = self::ADMINISTRATION_DATA_VALUE_CLASS[$entityClass];
            $valueInfo = new $administrationDataValuesClass($value, $dataType, $administration, $survey);
            $doctrine->getManager()->persist($valueInfo);
        }
        return $valueInfo;
    }

    /////////// Operation part /////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Update all operation for an survey and administration.
     * @param string $entityClass To know which repository to use. (ex: Establishments::class)
     * @param Surveys $survey Survey entity which operation must be updated.
     * @param Administrations $administration Administration entity which operation must be updated.
     */
    private function updateOperationValues(string $entityClass, Surveys $survey, Administrations $administration)
    {
        $operationDataTypes = $this->managerRegistry->getRepository(Operations::class)
            ->getOperationByEntity($entityClass);
        $values = $this->managerRegistry->getRepository(self::ADMINISTRATION_DATA_VALUE_CLASS[$entityClass])
            ->findBy(array('survey' => $survey, self::ADMINISTRATION_CAMEL_CASE[$entityClass] => $administration));
        $dataTypes = array();
        $result = array();
        foreach ($operationDataTypes as $operationDataType)
        {
            $dataType = $operationDataType->getDataType();
            array_push($dataTypes, $dataType);
            $result[$dataType->getId()] = $this->computeOperation($operationDataType->getFormula(),$values);
        }

        $surveyResult[$survey->getId()] = $result;
        $this->updateOrInsertMultipleOperationAndSurveyValues(
            $entityClass, [$survey], $dataTypes, $administration, $surveyResult);
    }

    /**
     * Compute an operation by this formula.
     * @param string $formula Formula to compute.
     * @param array $values Array with all values necessaries for compute operation.
     * @return float|string Result of operation, or 'ERROR' if can't compute this.
     */
    private function computeOperation(string $formula, array $values)
    {
        $formulaInfo = array('formula' => $formula);
        $this->formatFormulaFunction($formulaInfo);
        $codeInFormula = StringTools::splitOperator($formulaInfo['formula']);
        $nbCode = count($codeInFormula);

        $i = 0;
        foreach ($values as $value)
        {
            if ($i === $nbCode)
            {
                break;
            }
            $dataTypeCode = $value->getDataType()->getCode();
            if (in_array($dataTypeCode, $codeInFormula))
            {
                $formulaInfo['formula'] = preg_replace("/\b$dataTypeCode\b/",
                    $value->getValue(),
                    $formulaInfo['formula']);
                $i++;
            }
        }

        $this->replaceCodeFormulaFunction($formulaInfo, $codeInFormula, $nbCode);
        try
        {
            $result = math_eval($formulaInfo['formula']);
            $result = round($result, 2);
        }
        catch (Exception $e)
        {
            $result = 'ERROR';
        }
        return $result;
    }

    /**
     * Update or insert for the first time operation for serial survey in same time.
     * @param string $entityClass To know which repository to use. (ex: Establishments::class)
     * @param array $surveys Array with all survey to use for compute operation.
     * @param array $dataTypes Array with all operation data type.
     * @param Administrations $administration Administration entity of operation to insert.
     * @param array $values Array with all value indexed : $values[surveyId][dataTypeId].
     */
    private function updateOrInsertMultipleOperationAndSurveyValues(string $entityClass, array $surveys,
                                                                    array $dataTypes, Administrations $administration,
                                                                    array $values)
    {
        $valueInfos = $this->managerRegistry->getRepository(self::ADMINISTRATION_DATA_VALUE_CLASS[$entityClass])
            ->findBy(array('survey' => $surveys,
                'dataType' => $dataTypes,
                self::ADMINISTRATION_CAMEL_CASE[$entityClass] => $administration));

        $indexedDataType = array();
        foreach ($dataTypes as $dataType)
        {
            $indexedDataType[$dataType->getId()] = $dataType;
        }

        $indexedSurvey = array();
        foreach ($surveys as $survey)
        {
            $indexedSurvey[$survey->getId()] = $survey;
        }


        $indexedValueInfo = array();
        foreach ($valueInfos as $valueInfo)
        {
            $indexedValueInfo[$valueInfo->getSurvey()->getId()][$valueInfo->getDataType()->getId()] = $valueInfo;
        }

        foreach ($values as $surveyId => $dataTypeIndexedValue)
        {
            foreach ($dataTypeIndexedValue as $dataTypeId => $value)
            {
                if (array_key_exists($surveyId, $indexedValueInfo)
                    && array_key_exists($dataTypeId, $indexedValueInfo[$surveyId]))
                {
                    $valueInfo = $indexedValueInfo[$surveyId][$dataTypeId];
                    $valueInfo->setValue($value);
                }
                else
                {
                    $administrationDataValuesClass = self::ADMINISTRATION_DATA_VALUE_CLASS[$entityClass];
                    $valueInfo = new $administrationDataValuesClass(
                        $value, $indexedDataType[$dataTypeId], $administration, $indexedSurvey[$surveyId]);
                    $this->managerRegistry->getManager()->persist($valueInfo);
                }
            }
        }

        $this->managerRegistry->getManager()->flush();
    }

    /**
     * Update and compute operation for all surveys in parameter.
     * @param string $entityClass To know which repository to use. (ex: Establishments::class)
     * @param Administrations $administration Administration entity of operation to compute.
     * @param array $surveys Array of survey entity to compute.
     */
    private function updateOperationForAllSurvey(string $entityClass, Administrations $administration, array $surveys)
    {
        $operationDataTypes = $this->managerRegistry->getRepository(Operations::class)
            ->getOperationByEntity($entityClass);
        $values = $this->managerRegistry->getRepository(self::ADMINISTRATION_DATA_VALUE_CLASS[$entityClass])
            ->findBy(array('survey' => $surveys, self::ADMINISTRATION_CAMEL_CASE[$entityClass] => $administration));

        $indexedValues = array();
        foreach ($surveys as $survey)
        {
            $indexedValues[$survey->getId()] = array();
        }

        foreach ($values as $value)
        {
            $surveyId = $value->getSurvey()->getId();
            array_push($indexedValues[$surveyId], $value);
        }

        $result = array();
        $dataTypes = array();
        foreach ($operationDataTypes as $operationDataType)
        {
            $dataType = $operationDataType->getDataType();
            array_push($dataTypes, $dataType);

            foreach ($surveys as $survey)
            {
                $result[$survey->getId()][$dataType->getId()] =
                    $this->computeOperation($operationDataType->getFormula(), $indexedValues[$survey->getId()]);
            }
        }

        $this->updateOrInsertMultipleOperationAndSurveyValues(
            $entityClass, $surveys, $dataTypes, $administration, $result);
    }

}