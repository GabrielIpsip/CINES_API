<?php

namespace App\Repository;

use App\Entity\PhysicalLibraryActiveHistory;
use App\Entity\Surveys;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PhysicalLibraryActiveHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PhysicalLibraryActiveHistory::class);
    }

    /**
     * @param int $
     * @return void
     */
    public function findBySurvey(Surveys $survey)
    {
        $em = $this->getEntityManager();

        $strQuery = '
            SELECT a, p FROM App\Entity\PhysicalLibraryActiveHistory a
            JOIN a.physicalLibrary p
            WHERE a.survey = ?1';

        $query = $em->createQuery($strQuery);
        $query->setParameter(1, $survey);

        return $query->getResult();
    }
}