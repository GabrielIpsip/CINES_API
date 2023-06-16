<?php

namespace App\Repository;

use App\Common\Enum\AdministrationType;
use App\Common\Enum\Type;
use App\Entity\DocumentaryStructures;
use App\Entity\PhysicalLibraries;
use App\Entity\Surveys;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\ResultSetMapping;
use Exception;

/**
 * @method PhysicalLibraries|null find($id, $lockMode = null, $lockVersion = null)
 * @method PhysicalLibraries|null findOneBy(array $criteria, array $orderBy = null)
 * @method PhysicalLibraries[]    findAll()
 * @method PhysicalLibraries[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PhysicalLibrariesRepository extends ProgressRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PhysicalLibraries::class);
    }

    /**
     * Search physical libraries by keywords.
     * @param array $filtersArray List of keyword to filter list.
     * @param array $orderBy Order by condition.
     * @param DocumentaryStructures|null $docStruct Search for one documentary structure.
     * @param array|null $docStructFilter To filter result with documentary structure.
     * @return PhysicalLibraries[] Returns an array of PhysicalLibraries objects.
     */
    public function search(array $filtersArray, array $orderBy, ?DocumentaryStructures $docStruct,
                           ?array $docStructFilter = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->addSelect('d')
            ->addSelect('dp')
            ->addSelect('r');

        $i = 0;
        if ($docStructFilter)
        {
            foreach ($docStructFilter as $filter)
            {
                $qb->orWhere($qb->expr()->eq('p.documentaryStructure', ':val'.$i));
                $qb->setParameter('val' . $i, $filter);
                $i++;
            }
        }

        foreach ($filtersArray as $filter)
        {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like($qb->expr()->lower('d.officialName'),':val'.$i),
                    $qb->expr()->like($qb->expr()->lower('d.useName'),':val'.$i),
                    $qb->expr()->like($qb->expr()->lower('d.acronym'),':val'.$i),
                    $qb->expr()->like($qb->expr()->lower('d.address'),':val'.$i),
                    $qb->expr()->like($qb->expr()->lower('d.city'),':val'.$i),
                    $qb->expr()->like($qb->expr()->lower('d.postalCode'),':val'.$i),
                    $qb->expr()->like($qb->expr()->lower('p.officialName'),':val'.$i),
                    $qb->expr()->like($qb->expr()->lower('p.useName'), ':val'.$i),
                    $qb->expr()->like($qb->expr()->lower('p.address'), ':val'.$i),
                    $qb->expr()->like($qb->expr()->lower('p.city'), ':val'.$i),
                    $qb->expr()->like($qb->expr()->lower('p.postalCode'), ':val'.$i)));
            $qb->setParameter('val'.$i, '%'.strtolower($filter).'%');
            $i++;
        }

        if ($docStruct)
        {
            $qb->andWhere($qb->expr()->eq('p.documentaryStructure', ':docStruct'))
                ->setParameter('docStruct', $docStruct);
        }

         $qb->join('p.documentaryStructure', 'd')
         ->join('p.department', 'dp')
         ->join('dp.region', 'r');

        foreach ($orderBy as $key => $value) {
            $qb->addOrderBy('p.' . $key, $value);
        }
        return $qb->getQuery()
                  ->getArrayResult();
    }

    /**
     * Search active physical libraries for the given survey.
     * Look in the physical_library_active_history table
     * @TODO: Use DQL Query instead of native query
     * @param Surveys|null $survey Search for one survey.
     * @param array $orderBy Order by condition.
     * @param DocumentaryStructures|null $docStruct Search for one documentary structure.
     * @return PhysicalLibraries[] Returns an array of PhysicalLibraries objects.
     */
    public function searchActive(?Surveys $survey, array $orderBy, ?DocumentaryStructures $docStruct): array
    {
        $rsm = new ResultSetMapping();
        $rsm->addEntityResult('App\Entity\PhysicalLibraries', 'p');
        $rsm->addFieldResult('p', 'id', 'id');
        $rsm->addFieldResult('p', 'official_name', 'officialName');
        $rsm->addFieldResult('p', 'use_name', 'useName');
        $rsm->addFieldResult('p', 'address', 'address');
        $rsm->addFieldResult('p', 'postal_code', 'postalCode');
        $rsm->addFieldResult('p', 'city', 'city');
        $rsm->addFieldResult('p', 'active', 'active');
        $rsm->addFieldResult('p', 'instruction', 'instruction');
        $rsm->addFieldResult('p', 'sort_order', 'sortOrder');
        $rsm->addFieldResult('p', 'fictitious', 'fictitious');
        $rsm->addJoinedEntityResult('App\Entity\DocumentaryStructures', 'd', 'p', 'documentaryStructure');
        $rsm->addFieldResult('d', 'documentary_structure_fk', 'id');
        $rsm->addJoinedEntityResult('App\Entity\Departments', 'dp', 'p', 'department');
        $rsm->addFieldResult('dp', 'department_fk', 'id');

        $query = $this->getEntityManager()->createNativeQuery('SELECT physical_libraries.* FROM physical_libraries 
                LEFT JOIN physical_library_active_history ON physical_library_fk=physical_libraries.id
                LEFT JOIN documentary_structures ON documentary_structure_fk=documentary_structures.id
                WHERE documentary_structure_fk=? AND survey_fk=? AND physical_library_active_history.active=1', $rsm);

        $query->setParameter(1, $docStruct->getId());
        $query->setParameter(2, $survey->getId());
        $result = $query->getArrayResult();
        return $result;
    }

    /**
     * Get number of response for all physical libraries in this array.
     * @param array $physicLibArray Array of physical libraries with array representation.
     * @param Surveys $survey Number of response for this survey.
     * @return array|null Array with id of physical library like key, and nbrResponse like value.
     * @throws Exception 404 : Error to get last active survey.
     */
    public function getNbrResponse(array $physicLibArray, Surveys $survey) : ?array
    {
        if (count($physicLibArray) === 0)
        {
            return null;
        }

        $nbrDataTypeActive = $this->getNbrActiveDataType($survey, AdministrationType::physicalLibrary);

        if ($nbrDataTypeActive === 0 || count($physicLibArray) === 0)
        {
            return null;
        }

        $em = $this->getEntityManager();

        $queryStr = "SELECT pl.id,
            COUNT(pdv.value) AS nbrResponse
            FROM App\Entity\PhysicalLibraries pl 
            JOIN App\Entity\PhysicalLibraryDataValues pdv
            JOIN App\Entity\Surveys sv
            JOIN App\Entity\DataTypes dt
            JOIN App\Entity\Groups gp
            JOIN App\Entity\AdministrationTypes at
            JOIN App\Entity\SurveyDataTypes sdt
            WHERE pdv.physicalLibrary = pl
            AND pdv.survey = sv
            AND pdv.dataType = dt
            AND dt.group = gp
            AND gp.administrationType = at
            AND sdt.type = dt
            AND sdt.survey = sv
            AND sdt.active = 1
            AND at.id = ?3
            AND  sv.id = ?1
            AND IDENTITY(dt.type) <> ?4
            AND ( pl.id = ?2";

        if (count($physicLibArray) > 1)
        {
            for ($i = 1; $i < count($physicLibArray); $i++)
            {
                $queryStr .= ' OR pl.id = ?' . ($i + 4);
            }
        }

        $queryStr .= ') GROUP BY pl.id';

        $query = $em->createQuery($queryStr);
        $query->setParameter(1, $survey->getId());
        $query->setParameter(2, $physicLibArray[0]['id']);
        $query->setParameter(3, AdministrationType::physicalLibrary);
        $query->setParameter(4, Type::operation);

        if (count($physicLibArray) > 1)
        {
            for ($i = 1; $i < count($physicLibArray); $i++)
            {
                $query->setParameter($i + 4, $physicLibArray[$i]['id']);
            }
        }

        $result = $query->getArrayResult();

        $indexResult = array();

        foreach ($result as $r)
        {
            $indexResult[$r['id']] = 100 * $r['nbrResponse'] / $nbrDataTypeActive;
        }

        return $indexResult;
    }

}
