<?php

namespace App\Repository;

use App\Entity\PhysicalLibraries;
use App\Entity\PhysicalLibraryDataValues;
use App\Entity\Surveys;
use App\Entity\Types;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\Query\Expr\Join;

/**
 * @method PhysicalLibraryDataValues|null find($id, $lockMode = null, $lockVersion = null)
 * @method PhysicalLibraryDataValues|null findOneBy(array $criteria, array $orderBy = null)
 * @method PhysicalLibraryDataValues[]    findAll()
 * @method PhysicalLibraryDataValues[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PhysicalLibraryDataValuesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PhysicalLibraryDataValues::class);
    }

    /**
     * Get physical library data values by type of data type, for a survey.
     * @param PhysicalLibraries|null $physicLib Physical library doctrine entity.
     * @param Surveys|null $survey Survey doctrine entity.
     * @param Types $type Type doctrine entity, type of data values.
     * @return int|mixed|string Return array with data values doctrine entities.
     */
    public function findByTypeValue(?PhysicalLibraries $physicLib, ?Surveys $survey, Types $type)
    {
        $qb = $this->createQueryBuilder('v');
        if ($type)
        {
            $qb->join('v.dataType', 'd')
                ->join('d.type', 't', Join::WITH, $qb->expr()->eq('t', ':type'))
                ->setParameter('type', $type);
        }
        if ($physicLib)
        {
            $qb->andWhere($qb->expr()->eq('v.physicalLibrary', ':physicLib'))
                ->setParameter('physicLib', $physicLib);
        }
        if ($survey)
        {
            $qb->andWhere($qb->expr()->eq('v.survey', ':survey'))
                ->setParameter('survey', $survey);
        }

        return $qb->getQuery()->getResult();

    }

    /**
     * Get all value of physical library sorted for export.
     * @param int $physicLibId Id of physical library of values.
     * @return mixed[] Array of value doctrine entities.
     * @throws \Doctrine\DBAL\Exception
     */
    public function getAllValueForExport(int $physicLibId)
    {
        $queryStr = "SELECT surveys.data_calendar_year AS surveyDataCalendarYear,
            data_types.code AS dataTypeCode,
            physical_library_data_values.physical_library_fk AS physicLibId,
            physical_library_data_values.value AS value,
            groups.id as groupId,
            data_types.group_order as groupOrder
            FROM physical_library_data_values, data_types, groups, surveys
            WHERE physical_library_data_values.data_type_fk = data_types.id 
            AND data_types.group_fk = groups.id
            AND physical_library_data_values.physical_library_fk = $physicLibId
            AND physical_library_data_values.survey_fk = surveys.id
            AND groups.administration_type_fk = 2
            UNION ALL
            SELECT s1.data_calendar_year, data_types.code, $physicLibId, NULL, groups.id, data_types.group_order
            FROM survey_data_types, data_types, groups, surveys s1
            WHERE survey_data_types.type_fk = data_types.id
            AND data_types.group_fk = groups.id
            AND survey_data_types.survey_fk = s1.id
            AND groups.administration_type_fk = 2
            AND survey_data_types.active = 1
            AND data_types.id not in (
                SELECT data_types.id 
                FROM physical_library_data_values, data_types, groups, surveys s2
                WHERE physical_library_data_values.data_type_fk = data_types.id 
                AND data_types.group_fk = groups.id
                AND physical_library_data_values.physical_library_fk = $physicLibId
                AND physical_library_data_values.survey_fk = s2.id
                AND groups.administration_type_fk = 2
                AND s1.id = s2.id)
            ORDER BY surveyDataCalendarYear DESC, groupId ASC, groupOrder ASC";

        $em = $this->getEntityManager();
        $stmt = $em->getConnection()->prepare($queryStr);

        return $stmt->executeQuery()->fetchAllAssociative();
    }

    /**
     * Get All physical library data values with array representation.
     * @param PhysicalLibraries $physicLib Physical library of data values.
     * @param Surveys $survey Survey of data values.
     * @return array Array of data values array representation.
     */
    public function getAllPhysicLibDataValuesLikeArray(PhysicalLibraries $physicLib, Surveys $survey): array
    {
        $qb = $this->createQueryBuilder('pldv');
        $qb->addSelect('pldv');
        $qb->andWhere($qb->expr()->eq('pldv.survey', ':val1'));
        $qb->andWhere($qb->expr()->eq('pldv.physicalLibrary', ':val2'));
        $qb->setParameter('val1', $survey);
        $qb->setParameter('val2', $physicLib);
        return $qb->getQuery()->getArrayResult();
    }
}
