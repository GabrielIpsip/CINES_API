<?php


namespace App\Controller;

use App\Common\Traits\DataTypesTrait;
use App\Common\Traits\DocumentaryStructureCommentsTrait;
use App\Common\Traits\DocumentaryStructuresTrait;
use App\Common\Enum\Role;
use App\Common\Traits\SurveysTrait;
use App\Entity\DocumentaryStructureComments;
use App\Utils\StringTools;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Response;
use App\Controller\AbstractController\ESGBUController;

/**
 * Class DocumentaryStructureCommentsController
 * @package App\Controller
 * @SWG\Tag(name="Documentary structure comments")
 */
class DocumentaryStructureCommentsController extends ESGBUController
{
    use SurveysTrait,
        DocumentaryStructuresTrait,
        DataTypesTrait,
        DocumentaryStructureCommentsTrait;

    /**
     * Show all comment.
     * @SWG\Response(
     *     response="200",
     *     description="Return all comment.",
     *     @SWG\Schema(type="array", @SWG\Items(
     *      @SWG\Property(property="surveyId", type="integer"),
     *      @SWG\Property(property="docStructId", type="integer"),
     *      @SWG\Property(property="dataTypeId", type="integer"),
     *      @SWG\Property(property="comment", type="string")))
     * )
     * @SWG\Response(response="404", description="No comment found.")
     * @Rest\Get(path="/documentary-structure-comments", name="app_documentary_structure_comments_list")
     * @Rest\QueryParam(name="surveyId",requirements="\d+",nullable=true)
     * @Rest\QueryParam(name="dataTypeId",requirements="\d+",nullable=true)
     * @Rest\QueryParam(name="docStructId",requirements="^\d+(\d+|,)*",nullable=true,
     *     description="Id can be separated by comma without space: ex: 12,34,1")
     * @Rest\QueryParam(name="last", strict=true, requirements="true|false", default="false",
     *     description="To get most recent comment for each documentary structure for this data type.")
     * @Rest\View
     * @param int|null $surveyId Survey id to filter result.
     * @param int|null $dataTypeId Data type id to filter result.
     * @param string|null $docStructId Documentary structure id to filter result.
     * @param string|null $last To get most recent comments for the dataType and documentary structures.
     * @return View Array with all comments.
     */
    public function listAction(?int $surveyId, ?int $dataTypeId, ?string $docStructId, ?string $last): View
    {
        try
        {
            $last = StringTools::stringToBool($last);
            $docStructId = $docStructId ? StringTools::commaSplit($docStructId) : null;
            $criteria = array();

            if ($surveyId)
            {
                $survey = $this->getSurveyById($surveyId);
                $criteria['survey'] = $survey;
            }
            if ($dataTypeId)
            {
                $dataType = $this->getDataTypeById($dataTypeId);
                $criteria['dataType'] = $dataType;
            }
            if ($docStructId)
            {
                $docStructs = $this->getSerialDocStructById($docStructId);
                $criteria['documentaryStructure'] = $docStructs;
            }

            if ($last)
            {
                if (!$surveyId || !$dataTypeId || !$docStructId)
                {
                    throw new Exception('Missing parameter', Response::HTTP_BAD_REQUEST);
                }
                $comments = $this->getMostRecentComment($survey, $dataType, $docStructId);
            }
            else
            {
                $comments = $this->getDocumentaryStructureCommentsByCriteria($criteria);
                foreach ($comments as &$comment)
                {
                    $comment = $this->formatCommentForResponse($comment);
                }
            }

            return $this->createView($comments, Response::HTTP_OK);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }



    /** Show comments type by survey, documentary structure and data type id.
     * @SWG\Response(
     *     response="200",
     *     description="Return comment select by survey, documentary structure and data type id.",
     *     @SWG\Items(
     *      @SWG\Property(property="surveyId", type="integer"),
     *      @SWG\Property(property="docStructId", type="integer"),
     *      @SWG\Property(property="dataTypeId", type="integer"),
     *      @SWG\Property(property="comment", type="string"))
     * )
     * @SWG\Response(
     *     response="404",
     *     description="No survey, documentary structure, data type or comment found.",
     * )
     * @SWG\Parameter(name="surveyId",type="integer", in="path", description="Survey id.")
     * @SWG\Parameter(name="docStructId",type="integer", in="path", description="Documentary structure id.")
     * @SWG\Parameter(name="dataTypeId",type="integer", in="path", description="Data type id.")
     * @Rest\Get(
     *      path = "/documentary-structure-comments/{surveyId}/{docStructId}/{dataTypeId}",
     *      name = "app_documentary_structure_comments_show",
     *      requirements = {"surveyId"="\d+", "docStructId"="\d+", "dataTypeId"="\d+"}
     * )
     * @Rest\View
     * @param int $surveyId Survey id.
     * @param int $docStructId Documentary structure id.
     * @param int $dataTypeId Data type id.
     * @return View Comment information.
     */
    public function showAction(int $surveyId, int $docStructId, int $dataTypeId) : View
    {
        try
        {
            $docStruct = $this->getDocStructById($docStructId);
            $this->checkRights([Role::ADMIN, Role::ADMIN_RO, Role::SURVEY_ADMIN, Role::USER, Role::VALID_SURVEY_RESP],
                $docStruct);

            $survey = $this->getSurveyById($surveyId);
            $dataType = $this->getDataTypeById($dataTypeId);

            $comment = $this->getComment($survey, $docStruct, $dataType);
            return $this->createView($this->formatCommentForResponse($comment), Response::HTTP_OK);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Create new comment.
     * @SWG\Response(
     *     response="201",
     *     description="Create comment.",
     *     @SWG\Items(
     *      @SWG\Property(property="surveyId", type="integer"),
     *      @SWG\Property(property="docStructId", type="integer"),
     *      @SWG\Property(property="dataTypeId", type="integer"),
     *      @SWG\Property(property="comment", type="string"))
     * )
     * @SWG\Response(response="404", description="No survey, documentary structure or data type found.")
     * @SWG\Response(response="400", description="Bad request. Body not valid.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Response(response="409", description="Comment already exists.")
     * @SWG\Parameter(name="body", in="body", description="Comment informations.",
     *     @SWG\Schema(type="object",
     *      @SWG\Property(property="surveyId", type="integer"),
     *      @SWG\Property(property="docStructId", type="integer"),
     *      @SWG\Property(property="dataTypeId", type="integer"),
     *      @SWG\Property(property="comment", type="string")))
     * @Rest\Post(path="/documentary-structure-comments", name="app_documentary_structure_comments_create")
     * @Rest\RequestParam(name="surveyId", requirements="[0-9]*", nullable=false)
     * @Rest\RequestParam(name="docStructId", requirements="[0-9]*", nullable=false)
     * @Rest\RequestParam(name="dataTypeId", requirements="[0-9]*", nullable=false)
     * @Rest\RequestParam(name="comment", nullable=false)
     * @Rest\View
     * @param int $surveyId Survey Id.
     * @param int $docStructId Documentary structure id.
     * @param int $dataTypeId Data type id.
     * @param string $comment Comment value.
     * @return View Comment has just been created.
     */
    public function createAction(int $surveyId, int $docStructId, int $dataTypeId, string $comment) : View
    {
        try
        {
            $docStruct = $this->getDocStructById($docStructId);
            $this->checkRights([Role::ADMIN, Role::VALID_SURVEY_RESP, Role::SURVEY_ADMIN, Role::USER], $docStruct);

            $survey = $this->getSurveyById($surveyId);
            $dataType = $this->getDataTypeById($dataTypeId);

            $commentEntity = $this->managerRegistry->getRepository(DocumentaryStructureComments::class)
                ->findOneBy(array('survey' => $survey, 'documentaryStructure' => $docStruct, 'dataType' => $dataType));
            if ($commentEntity)
            {
                return $this->createView('Comment already exists.', Response::HTTP_CONFLICT, true);
            }
            $commentEntity = new DocumentaryStructureComments($comment, $survey, $docStruct, $dataType);

            $em = $this->managerRegistry->getManager();
            $em->persist($commentEntity);
            $em->flush();

            return $this->createView(
                $this->formatCommentForResponse($commentEntity), Response::HTTP_CREATED, true);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }


    /**
     * Update a comment.
     * @SWG\Response(
     *     response="200",
     *     description="Update a comment selected by survey, documentary structure and data type id.",
     *     @SWG\Items(
     *      @SWG\Property(property="surveyId", type="integer"),
     *      @SWG\Property(property="docStructId", type="integer"),
     *      @SWG\Property(property="dataTypeId", type="integer"),
     *      @SWG\Property(property="comment", type="string"))
     * )
     * @SWG\Response(response="404", description="Data missing in database")
     * @SWG\Response(response="400", description="Error to update establishment. Body not valid.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="surveyId",type="integer", in="path", description="Survey id.")
     * @SWG\Parameter(name="docStructId",type="integer", in="path", description="Documentary structure id.")
     * @SWG\Parameter(name="dataTypeId",type="integer", in="path", description="Data type id.")
     * @SWG\Parameter(name="body", in="body", description="Establishment informations.",
     *     @SWG\Schema(type="object",
     *     @SWG\Property(property="comment", type="string"))
     * )
     * @Rest\Patch(
     *     path="/documentary-structure-comments/{surveyId}/{docStructId}/{dataTypeId}",
     *     name="app_documentary_structure_comments_update",
     *     requirements={"surveyId"="\d+", "docStructId"="\d+", "dataTypeId"="\d+"}
     * )
     * @Rest\RequestParam(name="comment", nullable=false)
     * @Rest\View
     * @param int $surveyId Survey Id.
     * @param int $docStructId Documentary structure id.
     * @param int $dataTypeId Data type id.
     * @param string $comment Comment value.
     * @return View Comment has just been updated.
     */
    public function updateAction(int $surveyId, int $docStructId, int $dataTypeId, string $comment) : View
    {
        try
        {
            $docStruct = $this->getDocStructById($docStructId);
            $this->checkRights([Role::ADMIN, Role::VALID_SURVEY_RESP, Role::SURVEY_ADMIN, Role::USER], $docStruct);

            $survey = $this->getSurveyById($surveyId);
            $dataType = $this->getDataTypeById($dataTypeId);

            $commentEntity = $this->getComment($survey, $docStruct, $dataType);
            $commentEntity->setComment($comment);

            $this->managerRegistry->getManager()->flush();
            return $this->createView(
                $this->formatCommentForResponse($commentEntity), Response::HTTP_OK, true);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }

    /**
     * Format entity for response.
     * @param DocumentaryStructureComments $comment Doctrine entity.
     * @return array Array with all information to send in response.
     */
    private function formatCommentForResponse(DocumentaryStructureComments $comment): array
    {
        return array(
          'surveyId' => $comment->getSurvey()->getId(),
          'docStructId' => $comment->getDocumentaryStructure()->getId(),
          'dataTypeId' => $comment->getDataType()->getId(),
          'comment' => $comment->getComment()
        );
    }
}