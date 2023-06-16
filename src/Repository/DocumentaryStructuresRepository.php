<?php

namespace App\Repository;

use App\Common\Enum\AdministrationType;
use App\Common\Enum\Type;
use App\Entity\DocumentaryStructures;
use App\Entity\Establishments;
use App\Entity\Surveys;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\DBALException;

/**
 * @method DocumentaryStructures|null find($id, $lockMode = null, $lockVersion = null)
 * @method DocumentaryStructures|null findOneBy(array $criteria, array $orderBy = null)
 * @method DocumentaryStructures[]    findAll()
 * @method DocumentaryStructures[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DocumentaryStructuresRepository extends ProgressRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocumentaryStructures::class);
    }

    /**
     * Search documentary structure with keyword.
     * @param array $filtersArray Array with keywords to filter result.
     * @param Establishments|null $establishment To search just in documentary structure of establishment.
     * @param array|null $docStructFilters Filter result with array of documentary structure, result is in this array.
     * @return DocumentaryStructures[] Returns an array of documentary structure objects.
     */
    public function search(array $filtersArray, ?Establishments $establishment, ?array $docStructFilters = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->addSelect('e')
            ->addSelect('dp')
            ->addSelect('r');

        $i = 0;
        if ($docStructFilters)
        {
            foreach ($docStructFilters as $docStruct)
            {
                $qb->orWhere($qb->expr()->eq('d.id', ':val' . $i));
                $qb->setParameter('val' . $i, $docStruct->getId());
                $i++;
            }
        }

        foreach ($filtersArray as $filter)
        {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like($qb->expr()->lower('d.officialName'),':val'.$i),
                    $qb->expr()->like($qb->expr()->lower('d.useName'), ':val'.$i),
                    $qb->expr()->like($qb->expr()->lower('d.acronym'), ':val'.$i),
                    $qb->expr()->like($qb->expr()->lower('d.address'), ':val'.$i),
                    $qb->expr()->like($qb->expr()->lower('d.city'), ':val'.$i),
                    $qb->expr()->like($qb->expr()->lower('d.postalCode'), ':val'.$i)));
            $qb->setParameter('val'.$i, '%'.strtolower($filter).'%');
            $i++;
        }

        if ($establishment)
        {
            $qb->andWhere($qb->expr()->eq('d.establishment', ':establishment'))
                ->setParameter('establishment', $establishment);
        }

        return $qb->join('d.establishment', 'e')
            ->join('d.department', 'dp')
            ->join('dp.region', 'r')
            ->addOrderBy('d.active', 'DESC')
            ->addOrderBy('d.useName', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Search documentary structure by establishment.
     * @param array $docStructFilters Set documentary structure array to filter result.
     * @param array $filtersArray Array of keywords to search establishments to get associated documentary structure.
     * @return array|int|string Returns an array of documentary structure objects.
     */
    public function searchDocStructByEstablishment(array $docStructFilters, array $filtersArray): array
    {
        if (count($docStructFilters) === 0)
        {
            return array();
        }
        $qb = $this->createQueryBuilder('d')
            ->addSelect('e')
            ->addSelect('t')
            ->addSelect('dp')
            ->addSelect('r');

        $i = 0;
        foreach ($docStructFilters as $docStruct)
        {
            $qb->orWhere($qb->expr()->eq('d.id', ':val' . $i));
            $qb->setParameter('val' . $i, $docStruct->getId());
            $i++;
        }

        foreach ($filtersArray as $filter)
        {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like($qb->expr()->lower('e.officialName'),':val'.$i),
                    $qb->expr()->like($qb->expr()->lower('e.useName'), ':val'.$i),
                    $qb->expr()->like($qb->expr()->lower('e.acronym'), ':val'.$i),
                    $qb->expr()->like($qb->expr()->lower('e.address'), ':val'.$i),
                    $qb->expr()->like($qb->expr()->lower('e.city'), ':val'.$i),
                    $qb->expr()->like($qb->expr()->lower('e.postalCode'), ':val'.$i)));
            $qb->setParameter('val'.$i, '%'.strtolower($filter).'%');
            $i++;
        }

        return $qb->join('d.establishment', 'e')
            ->join('e.type', 't')
            ->join('d.department', 'dp')
            ->join('dp.region', 'r')
            ->addOrderBy('d.active', 'DESC')
            ->addOrderBy('d.useName', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }


    /**
     * Get number of response for all documentary structures in this array.
     * @param array $docStructArray Array of documentary structure with array representation.
     * @param Surveys $survey Number of response for this survey.
     * @return array|null Array with id of documentary structure like key, and nbrResponse like value.
     */
    public function getNbrResponse(array $docStructArray, Surveys $survey): ?array
    {
        if (count($docStructArray) === 0)
        {
            return null;
        }

        $nbrDataTypeActive = $this->getNbrActiveDataType($survey,
            AdministrationType::documentaryStructure);

        if ($nbrDataTypeActive === 0)
        {
            return null;
        }

        $em = $this->getEntityManager();

        $queryStr = 'SELECT ds.id,
            COUNT(ddv.value) AS nbrResponse
            FROM App\Entity\DocumentaryStructures ds 
            JOIN App\Entity\DocumentaryStructureDataValues ddv
            JOIN App\Entity\Surveys sv
            JOIN App\Entity\DataTypes dt
            JOIN App\Entity\Groups gp
            JOIN App\Entity\AdministrationTypes at
            JOIN App\Entity\SurveyDataTypes sdt
            WHERE ddv.documentaryStructure = ds
            AND ddv.survey = sv
            AND ddv.dataType = dt
            AND dt.group = gp
            AND gp.administrationType = at
            AND sdt.type = dt
            AND sdt.survey = sv
            AND sdt.active = 1
            AND at.name = \'documentaryStructure\'
            AND  sv.id = ?1
            AND IDENTITY(dt.type) <> ?3
            AND ( ds.id = ?2';

        if (count($docStructArray) > 1)
        {
            for ($i = 1; $i < count($docStructArray); $i++)
            {
                $queryStr .= ' OR ds.id = ?' . ($i + 3);
            }
        }

        $queryStr .= ') GROUP BY ds.id';

        $query = $em->createQuery($queryStr);
        $query->setParameter(1, $survey->getId());
        $query->setParameter(2, $docStructArray[0]['id']);
        $query->setParameter(3, Type::operation);

        if (count($docStructArray) > 1)
        {
            for ($i = 1; $i < count($docStructArray); $i++)
            {
                $query->setParameter($i + 3, $docStructArray[$i]['id']);
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


    /**
     * Add progress parameter in documentary structure array representation for all documentary structure in this array.
     * @param array $docStructArray Array of documentary structure with array representation.
     * @param Surveys $survey Total progress for this survey.
     * @return array|null Return array with documentary structure like index and total progress like value.
     * @throws DBALException SQL query error.
     */
    public function getTotalProgress(array $docStructArray, Surveys $survey): ?array
    {
        $nbrDocStructDataTypeActive = $this->getNbrActiveDataType($survey,
            AdministrationType::documentaryStructure);
        $nbrPhysicLibDataTypeActive = $this->getNbrActiveDataType($survey,
            AdministrationType::physicalLibrary);

        if (count($docStructArray) === 0 || ($nbrDocStructDataTypeActive === 0 && $nbrPhysicLibDataTypeActive === 0))
        {
            return null;
        }

        $queryStr = "SELECT docStructId, SUM(nbrResponse) as nbrResponse FROM (
          SELECT documentary_structures.id AS docStructId, COUNT(documentary_structure_data_values.value) AS nbrResponse
          FROM documentary_structures
          JOIN documentary_structure_data_values ON documentary_structures.id = documentary_structure_data_values.documentary_structure_fk
          JOIN surveys ON documentary_structure_data_values.survey_fk = surveys.id
          JOIN data_types ON documentary_structure_data_values.data_type_fk = data_types.id
          JOIN groups ON data_types.group_fk = groups.id
          JOIN survey_data_types ON survey_data_types.type_fk = data_types.id AND survey_data_types.survey_fk = surveys.id
          WHERE groups.administration_type_fk = " . AdministrationType::documentaryStructure . "
          AND survey_data_types.active = 1
          AND surveys.id = " . $survey->getId() . "
          AND data_types.type_fk <> " . Type::operation . "
          AND documentary_structures.active = 1
          AND (documentary_structures.id = " .$docStructArray[0]['id'];

        if (count($docStructArray) > 1)
        {
            for ($i = 1; $i < count($docStructArray); $i++)
            {
                $queryStr .= ' OR documentary_structures.id = ' . $docStructArray[$i]['id'];
            }
        }
        $queryStr .= ') GROUP BY documentary_structures.id UNION ALL ';

        $queryStr .= "SELECT physical_libraries.documentary_structure_fk AS docStructId, COUNT(physical_library_data_values.value)
          FROM physical_libraries
          JOIN physical_library_data_values ON physical_libraries.id = physical_library_data_values.physical_library_fk
          JOIN surveys ON physical_library_data_values.survey_fk = surveys.id
          JOIN data_types ON physical_library_data_values.data_type_fk = data_types.id
          JOIN groups ON data_types.group_fk = groups.id
          JOIN survey_data_types ON survey_data_types.type_fk = data_types.id AND survey_data_types.survey_fk = surveys.id
          WHERE groups.administration_type_fk = " . AdministrationType::physicalLibrary . "
          AND survey_data_types.active = 1
          AND surveys.id = " . $survey->getId() . "
          AND data_types.type_fk <> " . Type::operation . "
          AND physical_libraries.active = 1
          AND (physical_libraries.documentary_structure_fk = " . $docStructArray[0]['id'];

        if (count($docStructArray) > 1)
        {
            for ($i = 1; $i < count($docStructArray); $i++)
            {
                $queryStr .= ' OR physical_libraries.documentary_structure_fk = ' . $docStructArray[$i]['id'];
            }
        }
        $queryStr .= ') GROUP BY physical_libraries.id) X group by docStructId';

        $em = $this->getEntityManager();
        $stmt = $em->getConnection()->prepare($queryStr);

        $result = $stmt->executeQuery()->fetchAllAssociative();

        return $this->computeTotalProgress($nbrDocStructDataTypeActive, $nbrPhysicLibDataTypeActive, $result);
    }

    /**
     * Compute total progress of documentary structures.
     * @param int $nbrDocStructDataTypeActive Number of active data type for documentary structure for survey.
     * @param int $nbrPhysicLibDataTypeActive Number of active data type for physical library for the survey.
     * @param array $result Result of getTotalProgress() function.
     * @return array|null Array with documentary structure id like key and total progress like value.
     */
    private function computeTotalProgress(int $nbrDocStructDataTypeActive, int $nbrPhysicLibDataTypeActive,
                                         array $result) : ?array
    {
        if (count($result) === 0)
        {
            return null;
        }

        $nbrPhysicLib = $this->getNbrPhysicLibByDocStruct($result);

        $docStructArray = array();

        foreach ($result as $r)
        {
            $docStructId = $r['docStructId'];
            $docStructArray[$docStructId] = array();
            $docStructArray[$docStructId]['nbrResponse'] = 0;
            $docStructArray[$docStructId]['nbrPhysicLib'] = 0;
            $docStructArray[$docStructId]['nbrDocStruct'] = 1;
            $docStructArray[$docStructId]['nbrResponse'] = $r['nbrResponse'];
        }

        foreach ($nbrPhysicLib as $nbr)
        {
            $docStructId = $nbr['docStructId'];
            $docStructArray[$docStructId]['nbrPhysicLib'] = $nbr['nbrPhysicLib'];
        }

        foreach ($docStructArray as &$docStruct)
        {
            $docStruct = 100 * ($docStruct['nbrResponse'] / (
                        ($nbrPhysicLibDataTypeActive * $docStruct['nbrPhysicLib']) +
                        ($nbrDocStructDataTypeActive * $docStruct['nbrDocStruct'])
                    ));
        }

        return $docStructArray;
    }

}
