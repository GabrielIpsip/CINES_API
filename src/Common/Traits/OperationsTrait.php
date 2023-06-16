<?php


namespace App\Common\Traits;


use App\Entity\DataTypes;
use App\Entity\Operations;
use App\Utils\StringTools;
use Exception;
use Symfony\Component\HttpFoundation\Response;

trait OperationsTrait
{
    /**
     * Get all operations in database.
     * @return array Array that contains all operations.
     * @throws Exception 404 : No operation found.
     */
    private function getAllOperation(): array
    {
        $operations = $this->managerRegistry->getRepository(Operations::class)->findAll();
        if (!$operations)
        {
            throw new Exception('No operation information found.', Response::HTTP_NOT_FOUND);
        }
        return $operations;
    }

    /**
     * Get operation by data type.
     * @param DataTypes $dataType DataType doctrine entity.
     * @return Operations Operation doctrine entity with this dataType.
     * @throws Exception 404 : No operation found.
     */
    private function getOperationByDataType(DataTypes $dataType): Operations
    {
        $operation = $this->managerRegistry->getRepository(Operations::class)
            ->findOneBy(array('dataType' => $dataType));
        if (!$operation)
        {
            throw new Exception('No operation information with id : ' . $dataType->getId(),
                Response::HTTP_NOT_FOUND);
        }
        return $operation;
    }

    /**
     * Check if this dataType already has operation information associated whit him.
     * @param int $dataTypeId Id of dataType.
     * @throws Exception 409 : DataType already has operation information.
     */
    private function checkAlreadyExistsOperation(int $dataTypeId)
    {
        $existingDataType = $this->managerRegistry->getRepository(Operations::class)->find($dataTypeId);
        if ($existingDataType)
        {
            throw new Exception('Data type $dataTypeId already have operation information.',
                Response::HTTP_CONFLICT);
        }
    }

    /**
     * Check if formula syntax is correct.
     * @param string $formula Formula string.
     * @throws Exception 400 : Syntax error.
     *                   404 : An operator doesn't exist.
     */
    private function checkFormula(string $formula)
    {
        $isFunction = StringTools::isFunction($formula);

        if (!$isFunction &&
            (!StringTools::areAllClosedParenthesis($formula) || !StringTools::correctParenthesis($formula)))
        {
            throw new Exception('Formula error.', Response::HTTP_BAD_REQUEST);
        }

        if ($isFunction)
        {
            $strOperators = StringTools::getOperatorFunction($formula);
        }
        else
        {
            $strOperators = StringTools::splitOperator($formula);
        }

        $operators = $this->managerRegistry->getRepository(DataTypes::class)
            ->findBy(array('code' => $strOperators));

        $numberOperator = 0;
        $codeOperator = 0;
        foreach ($strOperators as $strOperator)
        {
            if (preg_match('/^[0-9.]+$/', $strOperator))
            {
                $numberOperator++;
                continue;
            }

            foreach ($operators as $operator)
            {
                if ($operator->getCode() === $strOperator)
                {
                    $codeOperator++;
                    break;
                }
            }

        }

        if ($codeOperator + $numberOperator != count($strOperators))
        {
            throw new Exception("An operator doesn't exist.", Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Replace code without value, in formula function already formatted by formatFormulaFunction(), in neutral element.
     * @param array $formulaInfo Array with info about function in formula, see formatFormulaFunction().
     * @param array $codeInFormula Array with all code in formula.
     * @param int $nbCode Number of code in formula.
     */
    private function replaceCodeFormulaFunction(array& $formulaInfo, array $codeInFormula, int $nbCode)
    {
        $nbNoValue = 0;
        if ($formulaInfo['isSum'] || $formulaInfo['isAvg'])
        {
            $formulaInfo['formula'] = preg_replace("/\bND\b/", '0', $formulaInfo['formula'],
                -1, $nbNoValue);
            foreach ($codeInFormula as $code)
            {
                $isNoValue = 0;
                $formulaInfo['formula'] = preg_replace("/\b$code\b/", '0', $formulaInfo['formula'],
                    -1, $isNoValue);
                $nbNoValue += $isNoValue;
            }
        }
        if ($formulaInfo['isAvg'])
        {
            $nbCode -= $nbNoValue;
            $formulaInfo['formula'] = '(' . $formulaInfo['formula'] . ')/' . $nbCode;
        }
    }

    /**
     * Add in formula information, if formula is sum or avg and format function to addition.
     * @param array $formulaInfo Array with formula information.
     */
    private function formatFormulaFunction(array& $formulaInfo) {
        $formula = $formulaInfo['formula'];
        $isSum = StringTools::isSum($formula);
        $isAvg = StringTools::isAvg($formula);

        if ($isAvg || $isSum)
        {
            $formula = StringTools::functionToAddition($formula);
        }
        $formulaInfo['formula'] = $formula;
        $formulaInfo['isSum'] = $isSum;
        $formulaInfo['isAvg'] = $isAvg;
    }
}