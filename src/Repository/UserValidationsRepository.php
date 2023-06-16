<?php

namespace App\Repository;

use App\Entity\UserValidations;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserValidations|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserValidations|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserValidations[]    findAll()
 * @method UserValidations[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserValidationsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserValidations::class);
    }

    /**
     * Delete user validation for an user.
     * @param int $userId Id of user that validation must be deleted.
     */
    public function deleteForUser(int $userId)
    {
        $qb = $this->createQueryBuilder('v')
            ->delete();

        $qb->where(
            $qb->expr()->eq('IDENTITY(v.user)', ':userId'))
        ->setParameter('userId', $userId)
        ->getQuery()
        ->execute();
    }

    /**
     * Delete validation oldest than 24 hours.
     */
    public function deleteOlder()
    {
        $limitedDate = new DateTime();
        $limitedDate->modify("-24 hour");

        $qb = $this->createQueryBuilder('v')
            ->delete();

        $qb->where(
            $qb->expr()->lte('v.creation', ':limitedDate'))
            ->setParameter('limitedDate', $limitedDate)
            ->getQuery()
            ->execute();
    }

}
