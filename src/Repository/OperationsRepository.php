<?php

namespace App\Repository;

use App\Entity\DocumentaryStructures;
use App\Entity\Establishments;
use App\Entity\Operations;
use App\Entity\PhysicalLibraries;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Operations|null find($id, $lockMode = null, $lockVersion = null)
 * @method Operations|null findOneBy(array $criteria, array $orderBy = null)
 * @method Operations[]    findAll()
 * @method Operations[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OperationsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Operations::class);
    }

    /**
     * @var array Set in cache result of getOperationByEntity() function.
     */
    private $resultOperationByEntity = array();

    /**
     * Get all operation information for dataType associated with this type of administration entity.
     * @param string $entityClass Administration class name (Ex: Establishments::class)
     * @return array|int|mixed|string Array with all operation information.
     */
    public function getOperationByEntity(string $entityClass): array
    {
        if (count($this->resultOperationByEntity) > 0)
        {
            return $this->resultOperationByEntity;
        }

        $em = $this->getEntityManager();

        $query = $em->createQuery("SELECT o
                                   FROM App\Entity\Operations o
                                   JOIN App\Entity\DataTypes dt
                                   JOIN App\Entity\Groups g
                                   JOIN App\Entity\AdministrationTypes at
                                   WHERE o.dataType = dt
                                   AND dt.group = g
                                   AND g.administrationType = at
                                   AND at.id = ?1");

        switch ($entityClass)
        {
            case Establishments::class:
                $query->setParameter(1, 1);
                break;
            case DocumentaryStructures::class:
                $query->setParameter(1, 3);
                break;
            case PhysicalLibraries::class:
                $query->setParameter(1, 2);
                break;
        }
        $this->resultOperationByEntity = $query->getResult();
        return $this->resultOperationByEntity;
    }

}
