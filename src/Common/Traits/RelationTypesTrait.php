<?php


namespace App\Common\Traits;

use App\Entity\RelationTypes;
use Exception;
use Symfony\Component\HttpFoundation\Response;

trait RelationTypesTrait
{
    /**
     * Get Relation type by this id.
     * @param int $typeId Id of relation type.
     * @return RelationTypes Relation type doctrine entity with this id.
     * @throws Exception 404 : No relation type found.
     */
    private function getRelationTypeById(int $typeId): RelationTypes
    {
        $relationType = $this->managerRegistry->getRepository(RelationTypes::class)->find($typeId);
        if (!$relationType)
        {
            throw new Exception('No relation type with id : ' . $typeId, Response::HTTP_NOT_FOUND);
        }
        return $relationType;
    }

    /**
     * Get all relation type in database.
     * @return array Array with all relation type doctrine entity.
     * @throws Exception 404 : No relation type found.
     */
    private function getAllRelationType(): array
    {
        $relationTypes = $this->managerRegistry->getRepository(RelationTypes::class)->findAll();
        if (count($relationTypes) === 0)
        {
            throw new Exception('No relation type found.', Response::HTTP_NOT_FOUND);
        }
        return $relationTypes;
    }
}