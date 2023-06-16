<?php

namespace App\Repository;

use App\Entity\DocumentaryStructureDataValues;
use App\Entity\DocumentaryStructures;
use App\Entity\Surveys;
use App\Entity\Types;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\Query\Expr\Join;

/**
 * @method DocumentaryStructureDataValues|null find($id, $lockMode = null, $lockVersion = null)
 * @method DocumentaryStructureDataValues|null findOneBy(array $criteria, array $orderBy = null)
 * @method DocumentaryStructureDataValues[]    findAll()
 * @method DocumentaryStructureDataValues[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DocumentaryStructureDataValuesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocumentaryStructureDataValues::class);
    }

    /**
     * Get documentary structure data values by type of data type, for a survey.
     * @param DocumentaryStructures|null $docStruct Documentary structure doctrine entity.
     * @param Surveys|null $survey Survey doctrine entity.
     * @param Types $type Type doctrine entity, type of data values.
     * @return int|mixed|string Return array with data values doctrine entities.
     */
    public function findByTypeValue(?DocumentaryStructures $docStruct, ?Surveys $survey, Types $type)
    {
        $qb = $this->createQueryBuilder('v');
        if ($type)
        {
            $qb->join('v.dataType', 'd')
                ->join('d.type', 't', Join::WITH, $qb->expr()->eq('t', ':type'))
                ->setParameter('type', $type);
        }
        if ($docStruct)
        {
            $qb->andWhere($qb->expr()->eq('v.documentaryStructure', ':docStruct'))
                ->setParameter('docStruct', $docStruct);
        }
        if ($survey)
        {
            $qb->andWhere($qb->expr()->eq('v.survey', ':survey'))
                ->setParameter('survey', $survey);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get all value of documentary structure sorted for export.
     * @param int $docStructId Id of documentary structure of values.
     * @return mixed[] Array of value doctrine entities.
     * @throws DBALException Error in SQL query.
     */
    public function getAllValueForExport(int $docStructId)
    {
        $queryStr = "SELECT surveys.data_calendar_year AS surveyDataCalendarYear,
            data_types.code AS dataTypeCode,
            documentary_structure_data_values.documentary_structure_fk AS docStructId,
            documentary_structure_data_values.value AS value,
            groups.id as groupId,
            data_types.group_order as groupOrder
            FROM documentary_structure_data_values, data_types, groups, surveys
            WHERE documentary_structure_data_values.data_type_fk = data_types.id 
            AND data_types.group_fk = groups.id
            AND documentary_structure_data_values.documentary_structure_fk = $docStructId
            AND documentary_structure_data_values.survey_fk = surveys.id
            AND groups.administration_type_fk = 3
            UNION ALL
            SELECT s1.data_calendar_year, data_types.code, $docStructId, NULL, groups.id, data_types.group_order
            FROM survey_data_types, data_types, groups, surveys s1
            WHERE survey_data_types.type_fk = data_types.id
            AND data_types.group_fk = groups.id
            AND survey_data_types.survey_fk = s1.id
            AND groups.administration_type_fk = 3
            AND survey_data_types.active = 1
            AND data_types.id not in (
                SELECT data_types.id
                FROM documentary_structure_data_values, data_types, groups, surveys s2
                WHERE documentary_structure_data_values.data_type_fk = data_types.id 
                AND data_types.group_fk = groups.id
                AND documentary_structure_data_values.documentary_structure_fk = $docStructId
                AND documentary_structure_data_values.survey_fk = s2.id
                AND groups.administration_type_fk = 3
                AND s1.id = s2.id)
            ORDER BY surveyDataCalendarYear DESC, groupId ASC, groupOrder ASC";

        $em = $this->getEntityManager();
        $stmt = $em->getConnection()->prepare($queryStr);
        return $stmt->executeQuery()->fetchAllAssociative();
    }

    /**
     * Get All documentary structure data values with array representation.
     * @param DocumentaryStructures $docStruct Documentary structure of data values.
     * @param Surveys $survey Survey of data values.
     * @return array Array of data values array representation.
     */
    public function getAllDocStructDataValuesLikeArray(DocumentaryStructures $docStruct, Surveys $survey): array
    {
        $qb = $this->createQueryBuilder('dsdv');
        $qb->addSelect('dsdv');
        $qb->andWhere($qb->expr()->eq('dsdv.survey', ':val1'));
        $qb->andWhere($qb->expr()->eq('dsdv.documentaryStructure', ':val2'));
        $qb->setParameter('val1', $survey);
        $qb->setParameter('val2', $docStruct);
        return $qb->getQuery()->getArrayResult();
    }
}
