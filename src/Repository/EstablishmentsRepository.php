<?php

namespace App\Repository;

use App\Common\Enum\AdministrationType;
use App\Common\Enum\Type;
use App\Entity\Establishments;
use App\Entity\Surveys;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Establishments|null find($id, $lockMode = null, $lockVersion = null)
 * @method Establishments|null findOneBy(array $criteria, array $orderBy = null)
 * @method Establishments[]    findAll()
 * @method Establishments[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EstablishmentsRepository extends ProgressRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Establishments::class);
    }

    /**
     * Search establishment with keyword.
     * @param array $filtersArray Array with keywords to filter result.
     * @return Establishments[] Returns an array of documentary structure objects.
     */
    public function search(array $filtersArray): array
    {
        $qb = $this->createQueryBuilder('e')
                   ->addSelect('t')
                   ->addSelect('dp')
                   ->addSelect('r');

        $i = 0;
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

        return $qb->join('e.type', 't')
                  ->join('e.department', 'dp')
                  ->join('dp.region', 'r')
                  ->addOrderBy('e.active', 'DESC')
                  ->addOrderBy('e.useName', 'ASC')
                  ->getQuery()
                  ->getArrayResult();
    }

    /**
     * Get number of response for all establishments in this array.
     * @param array $establishmentArray Array of establishment with array representation.
     * @param Surveys $survey Number of response for this survey.
     * @return array|null Array with id of establishment like key, and nbrResponse like value.
     */
    public function getNbrResponse(array $establishmentArray, Surveys $survey): ?array
    {
        if (count($establishmentArray) === 0)
        {
            return null;
        }

        $nbrDataTypeActive = $this->getNbrActiveDataType($survey, AdministrationType::institution);

        if ($nbrDataTypeActive === 0)
        {
            return null;
        }

        $em = $this->getEntityManager();

        $queryStr = "SELECT e.id,
            COUNT(edv.value) AS nbrResponse
            FROM App\Entity\Establishments e
            JOIN App\Entity\EstablishmentDataValues edv
            JOIN App\Entity\Surveys sv
            JOIN App\Entity\DataTypes dt
            JOIN App\Entity\Groups gp
            JOIN App\Entity\AdministrationTypes at
            JOIN App\Entity\SurveyDataTypes sdt
            WHERE edv.establishment = e
            AND edv.survey = sv
            AND edv.dataType = dt
            AND dt.group = gp
            AND gp.administrationType = at
            AND sdt.type = dt
            AND sdt.survey = sv
            AND sdt.active = 1
            AND at.id = ?3
            AND  sv.id = ?1
            AND IDENTITY(dt.type) <> 3
            AND ( e.id = ?2";

        if (count($establishmentArray) > 1)
        {
            for ($i = 1; $i < count($establishmentArray); $i++)
            {
                $queryStr .= ' OR e.id = ?' . ($i + 3);
            }
        }

        $queryStr .= ') GROUP BY e.id';

        $query = $em->createQuery($queryStr);
        $query->setParameter(1, $survey->getId());
        $query->setParameter(2, $establishmentArray[0]['id']);
        $query->setParameter(3, AdministrationType::institution);

        if (count($establishmentArray) > 1)
        {
            for ($i = 1; $i < count($establishmentArray); $i++)
            {
                $query->setParameter($i + 3, $establishmentArray[$i]['id']);
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
     * Add progress parameter in establishment array representation for all establishment in this array.
     * @param array $establishmentArray Array of establishments with array representation.
     * @param Surveys $survey Total progress for this survey.
     * @return array|null Return array with establishment like index and total progress like value.
     * @throws \Doctrine\DBAL\Exception
     */
    public function getTotalProgress(array $establishmentArray, Surveys $survey): ?array
    {
        $nbrEstablishmentDataTypeActive = $this->getNbrActiveDataType($survey,
            AdministrationType::institution);
        $nbrDocStructDataTypeActive = $this->getNbrActiveDataType($survey,
            AdministrationType::documentaryStructure);
        $nbrPhysicLibDataTypeActive = $this->getNbrActiveDataType($survey,
            AdministrationType::physicalLibrary);

        if (count($establishmentArray) === 0 ||
            ($nbrEstablishmentDataTypeActive && $nbrDocStructDataTypeActive === 0 && $nbrPhysicLibDataTypeActive === 0))
        {
            return null;
        }

        $queryStr = "SELECT establishmentId, SUM(nbrResponse) as nbrResponse FROM (
          SELECT establishments.id AS establishmentId, 0 AS nbrResponse
          FROM establishments
          JOIN establishment_data_values ON establishments.id = establishment_data_values.establishment_fk
          JOIN surveys ON establishment_data_values.survey_fk = surveys.id
          JOIN data_types ON establishment_data_values.data_type_fk = data_types.id
          JOIN groups ON data_types.group_fk = groups.id
          JOIN survey_data_types ON survey_data_types.type_fk = data_types.id AND survey_data_types.survey_fk = surveys.id
          WHERE groups.administration_type_fk = " . AdministrationType::institution . "
          AND survey_data_types.active = 1
          AND establishments.active = 1
          AND surveys.id = " . $survey->getId() . "
          AND data_types.type_fk <> " . Type::operation . "
          AND (establishments.id = " . $establishmentArray[0]['id'];
        if (count($establishmentArray) > 1)
        {
            for ($i = 1; $i < count($establishmentArray); $i++)
            {
                $queryStr .= ' OR establishments.id = ' . $establishmentArray[$i]['id'];
            }
        }
        $queryStr .= ') GROUP BY establishments.id UNION ALL ';

        $queryStr .= "SELECT documentary_structures.establishment_fk AS establishmentId, COUNT(documentary_structure_data_values.value) AS nbrResponse
          FROM documentary_structures
          JOIN documentary_structure_data_values ON documentary_structures.id = documentary_structure_data_values.documentary_structure_fk
          JOIN surveys ON documentary_structure_data_values.survey_fk = surveys.id
          JOIN data_types ON documentary_structure_data_values.data_type_fk = data_types.id
          JOIN groups ON data_types.group_fk = groups.id
          JOIN survey_data_types ON survey_data_types.type_fk = data_types.id AND survey_data_types.survey_fk = surveys.id
          WHERE groups.administration_type_fk = " .  AdministrationType::documentaryStructure . "
          AND survey_data_types.active = 1
          AND surveys.id = " . $survey->getId() . "
          AND data_types.type_fk <> " . Type::operation . "
          AND documentary_structures.active = 1
          AND (documentary_structures.establishment_fk = " . $establishmentArray[0]['id'];
        if (count($establishmentArray) > 1)
        {
            for ($i = 1; $i < count($establishmentArray); $i++)
            {
                $queryStr .= ' OR documentary_structures.establishment_fk = ' . $establishmentArray[$i]['id'];
            }
        }
        $queryStr .= ') GROUP BY documentary_structures.id UNION ALL ';

        $queryStr .= "SELECT documentary_structures.establishment_fk AS establishmentId, COUNT(physical_library_data_values.value) AS nbrResponse
          FROM physical_libraries
          JOIN documentary_structures ON physical_libraries.documentary_structure_fk = documentary_structures.id
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
          AND (documentary_structures.establishment_fk = " . $establishmentArray[0]['id'];
        if (count($establishmentArray) > 1)
        {
            for ($i = 1; $i < count($establishmentArray); $i++)
            {
                $queryStr .= ' OR documentary_structures.establishment_fk = ' . $establishmentArray[$i]['id'];
            }
        }
        $queryStr .= ') GROUP BY physical_libraries.id) X group by establishmentId';

        $em = $this->getEntityManager();
        $stmt = $em->getConnection()->prepare($queryStr);
        $resultSet = $stmt->executeQuery();
        $result = $resultSet->fetchAllAssociative();

        return $this->computeTotalProgress($nbrEstablishmentDataTypeActive, $nbrDocStructDataTypeActive,
            $nbrPhysicLibDataTypeActive, $result);
    }

    /**
     * Compute total progress of establishments.
     * @param int $nbrEstablishmentDataTypeActive Number of active data type for establishment for survey.
     * @param int $nbrDocStructDataTypeActive Number of active data type for documentary structure for survey.
     * @param int $nbrPhysicLibDataTypeActive Number of active data type for physical library for the survey.
     * @param array $result Result of getTotalProgress() function.
     * @return array|null Array with establishment id like key and total progress like value.
     */
    private function computeTotalProgress(int $nbrEstablishmentDataTypeActive, int $nbrDocStructDataTypeActive,
                                          int $nbrPhysicLibDataTypeActive, array $result): ?array
    {
        if (count($result) === 0)
        {
            return null;
        }

        $nbrDocStruct = $this->getNbrDocStructByEstablishment($result);
        $nbrPhysicLib = $this->getNbrPhysicLibByEstablishment($result);

        $establishmentArray = array();

        foreach ($result as $r)
        {
            $establishmentId = $r['establishmentId'];
            $establishmentArray[$establishmentId] = array();
            $establishmentArray[$establishmentId]['nbrPhysicLib'] = 0;
            $establishmentArray[$establishmentId]['nbrDocStruct'] = 0;
            $establishmentArray[$establishmentId]['nbrEstablishment'] = 0;
            $establishmentArray[$establishmentId]['nbrResponse'] = $r['nbrResponse'];
        }

        foreach ($nbrDocStruct as $nbr)
        {
            $establishmentId = $nbr['establishmentId'];
            $establishmentArray[$establishmentId]['nbrDocStruct'] = $nbr['nbrDocStruct'];
        }

        foreach ($nbrPhysicLib as $nbr)
        {
            $establishmentId = $nbr['establishmentId'];
            $establishmentArray[$establishmentId]['nbrPhysicLib'] = $nbr['nbrPhysicLib'];
        }


        foreach ($establishmentArray as &$establishment)
        {
            $divider = ($nbrPhysicLibDataTypeActive * $establishment['nbrPhysicLib']) +
                ($nbrDocStructDataTypeActive * $establishment['nbrDocStruct']) +
                ($nbrEstablishmentDataTypeActive * $establishment['nbrEstablishment']);

            if ($divider === 0)
            {
                $establishment = 0;
            }
            else
            {
                $establishment = 100 * ($establishment['nbrResponse'] / $divider);
            }
        }

        return $establishmentArray;
    }
}
