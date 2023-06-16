<?php

namespace App\Repository;

use App\Entity\EstablishmentDataValues;
use App\Entity\Establishments;
use App\Entity\Surveys;
use App\Entity\Types;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;

/**
 * @method EstablishmentDataValues|null find($id, $lockMode = null, $lockVersion = null)
 * @method EstablishmentDataValues|null findOneBy(array $criteria, array $orderBy = null)
 * @method EstablishmentDataValues[]    findAll()
 * @method EstablishmentDataValues[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EstablishmentDataValuesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EstablishmentDataValues::class);
    }

    /**
     * Get establishment data values by type of data type, for a survey.
     * @param Establishments|null $establishment Establishment doctrine entity.
     * @param Surveys|null $survey Survey doctrine entity.
     * @param Types $type Type doctrine entity, type of data values.
     * @return int|mixed|string Return array with data values doctrine entities.
     */
    public function findByTypeValue(?Establishments $establishment, ?Surveys $survey, Types $type)
    {
        $qb = $this->createQueryBuilder('v');
        if ($type)
        {
            $qb->join('v.dataType', 'd')
                ->join('d.type', 't', Join::WITH, $qb->expr()->eq('t', ':type'))
                ->setParameter('type', $type);
        }
        if ($establishment)
        {
            $qb->andWhere($qb->expr()->eq('v.establishment', ':establishment'))
                ->setParameter('establishment', $establishment);
        }
        if ($survey)
        {
            $qb->andWhere($qb->expr()->eq('v.survey', ':survey'))
                ->setParameter('survey', $survey);
        }

        return $qb->getQuery()->getResult();

    }

    /**
     * Get All establishment data values with array representation.
     * @param Establishments $establishment Establishment of data values.
     * @param Surveys $survey Survey of data values.
     * @return array Array of data values array representation.
     */
    public function getAllEstablishmentDataValuesLikeArray(Establishments $establishment, Surveys $survey): array
    {
        $qb = $this->createQueryBuilder('edv');
        $qb->addSelect('edv');
        $qb->andWhere($qb->expr()->eq('edv.survey', ':val1'));
        $qb->andWhere($qb->expr()->eq('edv.establishment', ':val2'));
        $qb->setParameter('val1', $survey);
        $qb->setParameter('val2', $establishment);
        return $qb->getQuery()->getArrayResult();
    }
}
