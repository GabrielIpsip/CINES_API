<?php


namespace App\Repository;

use App\Entity\Users;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

abstract class GroupLocksRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, $entityClass) {
        parent::__construct($registry, $entityClass);
    }

    /**
     * Delete lock older than 15 min.
     */
    public function deleteOlder()
    {
        $limitedDate = new DateTime();
        $limitedDate->modify('-15 minutes');

        $qb = $this->createQueryBuilder('agl')
            ->delete();

        $qb->where(
            $qb->expr()->lte('agl.lockDate', ':limitedDate'))
            ->setParameter('limitedDate', $limitedDate)
            ->getQuery()
            ->execute();
    }

    /**
     * Clean lock of this user.
     * @param Users $user User doctrine entity.
     */
    public function cleanForThisUser(Users $user)
    {
        $qb = $this->createQueryBuilder('dsgl')
            ->delete();

        $qb->where(
            $qb->expr()->eq('dsgl.user', ':user')
        );

        $qb->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

}