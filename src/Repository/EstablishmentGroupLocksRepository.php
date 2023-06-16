<?php

namespace App\Repository;

use App\Entity\EstablishmentGroupLocks;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EstablishmentGroupLocks|null find($id, $lockMode = null, $lockVersion = null)
 * @method EstablishmentGroupLocks|null findOneBy(array $criteria, array $orderBy = null)
 * @method EstablishmentGroupLocks[]    findAll()
 * @method EstablishmentGroupLocks[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EstablishmentGroupLocksRepository extends GroupLocksRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EstablishmentGroupLocks::class);
    }

}
