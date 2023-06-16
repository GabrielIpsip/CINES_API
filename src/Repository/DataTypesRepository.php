<?php

namespace App\Repository;

use App\Entity\DataTypes;
use App\Entity\DocumentaryStructures;
use App\Entity\Establishments;
use App\Entity\PhysicalLibraries;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DataTypes|null find($id, $lockMode = null, $lockVersion = null)
 * @method DataTypes|null findOneBy(array $criteria, array $orderBy = null)
 * @method DataTypes[]    findAll()
 * @method DataTypes[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DataTypesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DataTypes::class);
    }

    /**
     * Return data type sorted by id and groupOrder.
     * @param string $entityClass To know which type of dataType to get.
     * @param bool $publicOnly To get just public dataTypes.
     * @return int|mixed|string Array with dataType doctrine entities.
     */
    public function getAllOrderedDataTypeByAdminType(string $entityClass, bool $publicOnly = false)
    {
        $em = $this->getEntityManager();

        $strQuery = '
            SELECT d
            FROM App\Entity\DataTypes d
            JOIN App\Entity\Groups g
            WHERE d.group = g.id
            AND IDENTITY(g.administrationType) = ?1';

        if ($publicOnly) {
            $strQuery .= ' AND d.private = false ';
        }

        $strQuery .= ' ORDER BY g.id, d.groupOrder';

        $query = $em->createQuery($strQuery);

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
        }

        return $query->getResult();
    }

}
