<?php


namespace App\Common\Traits;

use App\Entity\AbstractEntity\AdministrationRelations;
use App\Entity\AbstractEntity\Administrations;
use App\Entity\RelationTypes;
use DateTime;
use Exception;
use Symfony\Component\HttpFoundation\Response;

trait RelationsTrait
{

    /**
     * Get existing relation from database.
     * @param string $entityClass To know which repository to use. (ex: Establishments::class)
     * @param Administrations $administrationOrigin Origin of relation.
     * @param Administrations $administrationResult Result of relation.
     * @param RelationTypes $relationType Type of relation.
     * @return AdministrationRelations Relation doctrine entity.
     * @throws Exception 404 : Relation not found.
     */
    private function getRelation(string $entityClass, Administrations $administrationOrigin,
                                 Administrations $administrationResult, RelationTypes $relationType)
    : AdministrationRelations
    {
        $originParamName = 'origin' . ucfirst(self::ADMINISTRATION_CAMEL_CASE[$entityClass]);
        $resultParamName = 'result' . ucfirst(self::ADMINISTRATION_CAMEL_CASE[$entityClass]);

        $relation = $this->managerRegistry->getRepository(self::ADMINISTRATION_RELATION_CLASS[$entityClass])
            ->findOneBy(array(
                $originParamName => $administrationOrigin,
                $resultParamName => $administrationResult,
                'type' => $relationType));

        if (!$relation)
        {
            throw new Exception('Relation not found.', Response::HTTP_NOT_FOUND);
        }
        return $relation;
    }

    /**
     * Throw an error if relation already exists.
     * @param string $entityClass To know which repository to use. (ex: Establishments::class)
     * @param Administrations $administrationOrigin Origin of relation.
     * @param Administrations $administrationResult Result of relation.
     * @param RelationTypes $relationType Type of relation.
     * @throws Exception 409 : Relation already exists.
     */
    private function checkIfRelationAlreadyExists(string $entityClass, Administrations $administrationOrigin,
                                           Administrations $administrationResult, RelationTypes $relationType)
    {
        try
        {
            $relation = $this->getRelation($entityClass, $administrationOrigin, $administrationResult, $relationType);
            if ($relation)
            {
                throw new Exception('Error: one relation with this ' . self::ADMINISTRATION_NAME[$entityClass] .
                    ' already exists.',
                    Response::HTTP_CONFLICT, true);
            }
        }
        catch (Exception $e) { /* Do nothing */ }
    }

    /**
     * @param DateTime $startDate
     * @param DateTime|null $endDate
     * @throws Exception
     */
    private function checkRelationDate(DateTime $startDate, ?DateTime $endDate)
    {
        if ($endDate && $startDate > $endDate)
        {
            throw new Exception('date : Error startDate is greater than endDate',
                Response::HTTP_BAD_REQUEST);
        }
    }
}