<?php


namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Editorials;

/**
 * @method Editorials|null find($id, $lockMode = null, $lockVersion = null)
 * @method Editorials|null findOneBy(array $criteria, array $orderBy = null)
 * @method Editorials[]    findAll()
 * @method Editorials[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EditorialsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Editorials::class);
    }

    public function getAllEditorialsBySurveyState(int $surveyState): array
    {
        $em = $this->getEntityManager();
        $query = $em->createQuery(
            "SELECT e
            FROM App\Entity\Editorials e 
            JOIN App\Entity\Surveys su
            WHERE e.survey = su 
            AND IDENTITY(su.state) = ?1
            ORDER BY su.creation DESC
        ");

        $query->setParameter(1, $surveyState);
        return $query->getResult();
    }
}