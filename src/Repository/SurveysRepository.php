<?php

namespace App\Repository;

use App\Entity\Surveys;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

/**
 * @method Surveys|null find($id, $lockMode = null, $lockVersion = null)
 * @method Surveys|null findOneBy(array $criteria, array $orderBy = null)
 * @method Surveys[]    findAll()
 * @method Surveys[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SurveysRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Surveys::class);
    }

    /**
     * Get survey by name.
     * @param string $name Name of survey to search.
     * @return array|int|string Survey in array representation.
     */
    public function getByName(string $name): array
    {
        $qb = $this->createQueryBuilder('s');

        $qb->where(
            $qb->expr()->eq(
                $qb->expr()->upper('s.name'), $qb->expr()->upper(':name')))
        ->setParameter('name', $name);

        return $qb->getQuery()
        ->getArrayResult();
    }

    public function getByDataCalendarYear(string $year): Surveys
    {
        $qb = $this->createQueryBuilder('s');

        $qb->where(
            $qb->expr()->like('s.dataCalendarYear', ':year')
        )->setParameter('year', "$year-%");

        $survey = null;
        try
        {
            $survey = $qb->getQuery()->getSingleResult();
        }
        catch (Exception $e) { }
        return $survey;
    }

    public function getLastSurvey(): Surveys
    {
        $qb = $this->createQueryBuilder('s');

        $qb->select('s')
            ->orderBy('s.id', 'DESC')
            ->setMaxResults(1);

        $s = $qb->getQuery()->getSingleResult();
        return $s;
    }
}
