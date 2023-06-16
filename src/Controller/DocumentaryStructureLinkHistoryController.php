<?php


namespace App\Controller;

use App\Common\Enum\Role;
use App\Common\Traits\DocumentaryStructureLinkHistoryTrait;
use App\Common\Traits\DocumentaryStructuresTrait;
use App\Common\Traits\EstablishmentsTrait;
use App\Common\Traits\SurveysTrait;
use App\Controller\AbstractController\ESGBUController;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use App\Entity\DocumentaryStructureLinkHistory;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Establishments;
use App\Entity\DocumentaryStructures;

/**
 * Class DocumentaryStructureLinkHistoryController
 * @package App\Controller
 * @SWG\Tag(name="Documentary structure link history")
 */
class DocumentaryStructureLinkHistoryController extends ESGBUController
{

    use DocumentaryStructuresTrait,
        EstablishmentsTrait,
        DocumentaryStructureLinkHistoryTrait,
        SurveysTrait;

    /** Show documentary structure link history.
     * @SWG\Response(
     *     response="200",
     *     description="Return history of establishment linked with documentary structure.",
     *     @SWG\Schema(type="array",
     *       @SWG\Items(type="object",
     *         @SWG\Property(property="docStruct", ref=@Model(type=DocumentaryStructures::class)),
     *         @SWG\Property(property="surveyId", type="integer"),
     *         @SWG\Property(property="establishment", ref=@Model(type=Establishments::class))))
     * )
     * @SWG\Response(
     *     response="404",
     *     description="No documentary structure history found.",
     * )
     * @SWG\Parameter(name="docStructId",type="integer", in="query",
     *     description="History for this documentary structure")
     * @SWG\Parameter(name="establishmentId",type="integer", in="query",
     *     description="History for this establishment")
     * @Rest\Get(
     *      path = "/documentary-structure-link-history",
     *      name = "app_documentary_structure_link_history_list"
     * )
     * @Rest\QueryParam(name="docStructId",requirements="\d+",nullable=true)
     * @Rest\QueryParam(name="establishmentId",requirements="\d+",nullable=true)
     * @Rest\View
     * @param int|null $docStructId Documentary structure id.
     * @param int|null $establishmentId Establishment id.
     * @return View Documentary structure link history.
     */
    public function listAction(?int $docStructId, ?int $establishmentId): View
    {
        try
        {
            $history = [];
            if ($docStructId)
            {
                $docStruct = $this->getDocStructById($docStructId);
                $history += $this->getDocStructLinkHistoryByDocStruct($docStruct, false);
            }

            if ($establishmentId)
            {
                $establishment = $this->getEstablishmentById($establishmentId);
                $history += $this->getDocStructLinkHistoryByEstablishment($establishment, false);
            }

            if (count($history) === 0)
            {
                throw new Exception('No history line found.', Response::HTTP_NOT_FOUND);
            }

            foreach ($history as &$historyLine)
            {
                $historyLine = $this->formatLinkHistoryLineForResponse($historyLine);
            }
            return $this->createView($history, Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Insert or create new link history line.
     * @SWG\Response(
     *     response="201",
     *     description="Link history line has been created.",
     *     @SWG\Schema(type="object",
     *         @SWG\Property(property="docStruct", ref=@Model(type=DocumentaryStructures::class)),
     *         @SWG\Property(property="surveyId", type="integer"),
     *         @SWG\Property(property="establishment", ref=@Model(type=Establishments::class)))
     * )
     * @SWG\Response(
     *     response="200",
     *     description="Link history line has been updated.",
     *     @SWG\Schema(type="object",
     *         @SWG\Property(property="docStruct", ref=@Model(type=DocumentaryStructures::class)),
     *         @SWG\Property(property="surveyId", type="integer"),
     *         @SWG\Property(property="establishment", ref=@Model(type=Establishments::class)))
     * )
     * @SWG\Response(response="404", description="No survey, documentary structure or establishment found.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="body", in="body", description="History line information.",
     *     @SWG\Schema(type="object",
     *     @SWG\Property(property="docStructId", type="integer"),
     *     @SWG\Property(property="establishmentId", type="integer"),
     *     @SWG\Property(property="surveyId", type="integer"))
     * )
     * @Rest\Put(path="/documentary-structure-link-history",
     *     name="app_documentary_structure_link_history_insert_create")
     * @Rest\RequestParam(name="docStructId", nullable=false, requirements="[0-9]*")
     * @Rest\RequestParam(name="establishmentId", nullable=false, requirements="[0-9]*")
     * @Rest\RequestParam(name="surveyId", nullable=false, requirements="[0-9]*")
     * @Rest\View
     * @param int $docStructId Documentary structure id.
     * @param int $establishmentId Establishment id.
     * @param int $surveyId Survey id.
     * @return View Documentary structure link history line has just been created.
     */
    public function insertOrCreateAction(int $docStructId, int $establishmentId, int $surveyId): View
    {
        try
        {
            $this->checkRights([Role::ADMIN]);
            $docStruct = $this->getDocStructById($docStructId);
            $survey = $this->getSurveyById($surveyId);

            $response = $this->insertOrUpdateDocStructLinkHistory($docStruct, $survey, $establishmentId);

            return $this->createView($this->formatLinkHistoryLineForResponse($response[0]), $response[1], true);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }

    /**
     * Format link history doctrine entity for http response.
     * @param DocumentaryStructureLinkHistory $historyLine Doctrine entity.
     * @return array Array that contains information for response.
     */
    private function formatLinkHistoryLineForResponse(DocumentaryStructureLinkHistory $historyLine): array
    {
        return [
            'docStruct' => $historyLine->getDocumentaryStructure(),
            'establishment' => $historyLine->getEstablishment(),
            'surveyId' => $historyLine->getSurvey()->getId()
        ];
    }
}
