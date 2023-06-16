<?php

namespace App\Common\Traits;

use App\Entity\DataTypes;
use App\Entity\DocumentaryStructureComments;
use App\Entity\DocumentaryStructures;
use App\Entity\Surveys;
use Exception;
use Symfony\Component\HttpFoundation\Response;

trait DocumentaryStructureCommentsTrait
{
    /**
     * Get comments for a documentary structure by array of criteria.
     * @param array $criteria Array with criteria for the doctrine request.
     * @return array Array with all documentary structure comment entities found.
     * @throws Exception 404 : No comment found.
     */
    private function getDocumentaryStructureCommentsByCriteria(array $criteria): array
    {
        $comments = $this->managerRegistry->getRepository(DocumentaryStructureComments::class)
            ->findBy($criteria);
        if (count($comments) === 0)
        {
            throw new Exception('No comment found.', Response::HTTP_NOT_FOUND);
        }
        return $comments;
    }

    /**
     * Get most recent comment.
     * @param Surveys $survey Maximum survey to get comment, no get comment from oldest survey.
     * @param DataTypes $dataType DataType of comment.
     * @param array $docStructIds List of documentary structure id.
     * @return array Array with all documentary structure comment entities found, in array representation.
     * @throws Exception 404 : No comment found.
     */
    private function getMostRecentComment(Surveys $survey, DataTypes $dataType,
                                          array $docStructIds): array
    {
        $comments = $this->managerRegistry->getRepository(DocumentaryStructureComments::class)
            ->getMostRecentComment($survey, $dataType, $docStructIds);

        if (count($comments) === 0)
        {
            throw new Exception('No comment found.', Response::HTTP_NOT_FOUND);
        }

        foreach ($comments as &$comment)
        {
            $comment['docStructId'] = +$comment['docStructId'];
            $comment['surveyId'] = +$comment['surveyId'];
            $comment['dataTypeId'] = +$comment['dataTypeId'];
        }

        return $comments;
    }

    /**
     * Search one comment in database.
     * @param Surveys $survey Survey of comment.
     * @param DocumentaryStructures $docStruct Documentary structure of comment.
     * @param DataTypes $dataType DataType of comment.
     * @return DocumentaryStructureComments Doctrine entity comment.
     * @throws Exception 404 : No comment found.
     */
    private function getComment(Surveys $survey, DocumentaryStructures $docStruct, DataTypes $dataType)
    : DocumentaryStructureComments
    {
        $comment = $this->managerRegistry->getRepository(DocumentaryStructureComments::class)
            ->findOneBy(array('survey' => $survey, 'documentaryStructure' => $docStruct, 'dataType' => $dataType));
        if (!$comment)
        {
            throw new Exception('No comment found.', Response::HTTP_NOT_FOUND);
        }
        return $comment;
    }
}