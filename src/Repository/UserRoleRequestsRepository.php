<?php

namespace App\Repository;

use App\Entity\UserRoleRequests;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserRoleRequests|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserRoleRequests|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserRoleRequests[]    findAll()
 * @method UserRoleRequests[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRoleRequestsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserRoleRequests::class);
    }

    /**
     * Clean request oldest than 30 days.
     */
    public function deleteOlder()
    {
        $limitedDate = new DateTime();
        $limitedDate->modify("-30 day");

        $qb = $this->createQueryBuilder('r')
            ->delete();

        $qb->where(
            $qb->expr()->lte('r.creation', ':limitedDate'))
            ->setParameter('limitedDate', $limitedDate)
            ->getQuery()
            ->execute();
    }

}
