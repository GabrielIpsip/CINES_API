<?php

namespace App\Repository;

use App\Entity\PhysicalLibraryGroupLocks;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PhysicalLibraryGroupLocks|null find($id, $lockMode = null, $lockVersion = null)
 * @method PhysicalLibraryGroupLocks|null findOneBy(array $criteria, array $orderBy = null)
 * @method PhysicalLibraryGroupLocks[]    findAll()
 * @method PhysicalLibraryGroupLocks[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PhysicalLibraryGroupLocksRepository extends GroupLocksRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PhysicalLibraryGroupLocks::class);
    }

}
