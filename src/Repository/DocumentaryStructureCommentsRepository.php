<?php

namespace App\Repository;

use App\Entity\DataTypes;
use App\Entity\DocumentaryStructureComments;
use App\Entity\Surveys;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\DBALException;

/**
 * @method DocumentaryStructureComments|null find($id, $lockMode = null, $lockVersion = null)
 * @method DocumentaryStructureComments|null findOneBy(array $criteria, array $orderBy = null)
 * @method DocumentaryStructureComments[]    findAll()
 * @method DocumentaryStructureComments[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DocumentaryStructureCommentsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocumentaryStructureComments::class);
    }

    /**
     * If comment not exists for this survey, dataType and documentary structures, return most recent comment for this
     * dataType and documentary structure of oldest surveys.
     * @param Surveys $survey Survey most recent for the comment.
     * @param DataTypes $dataType DataType of comment.
     * @param array $docStructIds Array that contains documentary structure id.
     * @return array Return array representation of comments entity.
     * @throws \Doctrine\DBAL\Exception
     */
    public function getMostRecentComment(Surveys $survey, DataTypes  $dataType, array $docStructIds): array
    {
        $queryStr = 'SELECT d.documentary_structure_fk as docStructId,
                     d.survey_fk as surveyId,
                     d.data_type_fk as dataTypeId,
                     d.comment as comment
                     FROM documentary_structure_comments d, surveys s
                     WHERE d.survey_fk = s.id
                     AND d.data_type_fk = ' . $dataType->getId() . '
                     AND s.creation <= \'' . $survey->getCreation()->format('Y-m-d H:i:s T') . '\'';

        $nbrDocStructId = count($docStructIds);
        if ($nbrDocStructId)
        {
            $queryStr .= ' AND ( ';
        }

        foreach ($docStructIds as $docStructId)
        {
            $condition = " d.documentary_structure_fk = $docStructId";
            if ($nbrDocStructId - 1 === 0)
            {
                $queryStr .= $condition;
                $queryStr .= ' ) ';
            }
            else
            {
                $queryStr .= $condition . ' OR ';
            }
            $nbrDocStructId--;
        }

        $queryStr .= ' AND s.creation IN (
                            SELECT MAX(s2.creation)
                            FROM surveys s2, documentary_structure_comments d2
                            WHERE d2.survey_fk = s2.id
                            AND d2.survey_fk = s2.id
                            AND d2.data_type_fk = ' . $dataType->getId() . '
                            AND s2.creation <= \'' . $survey->getCreation()->format('Y-m-d H:i:s T') . '\'
                            AND d2.documentary_structure_fk = d.documentary_structure_fk )';

        $em = $this->getEntityManager();
        $stmt = $em->getConnection()->prepare($queryStr);

        return $stmt->executeQuery()->fetchAllAssociative();
    }
    
}
