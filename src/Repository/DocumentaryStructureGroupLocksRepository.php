<?php

namespace App\Repository;

use App\Entity\DocumentaryStructureGroupLocks;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DocumentaryStructureGroupLocks|null find($id, $lockMode = null, $lockVersion = null)
 * @method DocumentaryStructureGroupLocks|null findOneBy(array $criteria, array $orderBy = null)
 * @method DocumentaryStructureGroupLocks[]    findAll()
 * @method DocumentaryStructureGroupLocks[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DocumentaryStructureGroupLocksRepository extends GroupLocksRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocumentaryStructureGroupLocks::class);
    }

}
