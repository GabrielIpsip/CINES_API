<?php


namespace App\Controller;

use App\Common\Enum\Role;
use App\Common\Traits\DocumentaryStructuresTrait;
use App\Common\Traits\PhysicalLibraryLinkHistoryTrait;
use App\Common\Traits\PhysicalLibrariesTrait;
use App\Common\Traits\SurveysTrait;
use App\Controller\AbstractController\ESGBUController;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use App\Entity\PhysicalLibraryLinkHistory;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\DocumentaryStructures;
use App\Entity\PhysicalLibraries;

/**
 * Class PhysicalLibraryLinkHistoryController
 * @package App\Controller
 * @SWG\Tag(name="Physical library link history")
 */
class PhysicalLibraryLinkHistoryController extends ESGBUController
{

    use PhysicalLibrariesTrait,
        DocumentaryStructuresTrait,
        SurveysTrait,
        PhysicalLibraryLinkHistoryTrait;

    /** Show physical library link history.
     * @SWG\Response(
     *     response="200",
     *     description="Return history of documentary structure linked with physical library.",
     *     @SWG\Schema(type="array",
     *       @SWG\Items(type="object",
     *         @SWG\Property(property="physicLib", ref=@Model(type=PhysicalLibraries::class)),
     *         @SWG\Property(property="surveyId", type="integer"),
     *         @SWG\Property(property="docStruct", ref=@Model(type=DocumentaryStructures::class))))
     * )
     * @SWG\Response(
     *     response="404",
     *     description="No physical library history found.",
     * )
     * @SWG\Parameter(name="docStructId",type="integer", in="query",
     *     description="History for this documentary structure")
     * @SWG\Parameter(name="physicLibId",type="integer", in="query",
     *     description="History for this physical library")
     * @Rest\Get(
     *      path = "/physical-library-link-history",
     *      name = "app_physical_library_link_history_list"
     * )
     * @Rest\QueryParam(name="physicLibId",requirements="\d+",nullable=true)
     * @Rest\QueryParam(name="docStructId",requirements="\d+",nullable=true)
     * @Rest\View
     * @param int|null $docStructId Documentary structure id.
     * @param int|null $physicLibId Physical library id.
     * @return View Physical library link history.
     */
    public function listAction(?int $physicLibId, ?int $docStructId): View
    {
        try
        {
            $history = [];
            if ($physicLibId)
            {
                $physicLib = $this->getPhysicalLibraryById($physicLibId);
                $history += $this->getPhysicLibLinkHistoryByPhysicLib($physicLib, false);
            }

            if ($docStructId)
            {
                $docStruct = $this->getDocStructById($docStructId);
                $history += $this->getPhysicLibLinkHistoryByDocStruct($docStruct, false);
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
     *         @SWG\Property(property="physicLib", ref=@Model(type=PhysicalLibraries::class)),
     *         @SWG\Property(property="surveyId", type="integer"),
     *         @SWG\Property(property="docStruct", ref=@Model(type=DocumentaryStructures::class)))
     * )
     * @SWG\Response(
     *     response="200",
     *     description="Link history line has been updated.",
     *     @SWG\Schema(type="object",
     *         @SWG\Property(property="physicLib", ref=@Model(type=PhysicalLibraries::class)),
     *         @SWG\Property(property="surveyId", type="integer"),
     *         @SWG\Property(property="docStruct", ref=@Model(type=DocumentaryStructures::class)))
     * )
     * @SWG\Response(response="404", description="No survey, physical library or documentary structure found.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="body", in="body", description="History line information.",
     *     @SWG\Schema(type="object",
     *     @SWG\Property(property="physicLibId", type="integer"),
     *     @SWG\Property(property="docStructId", type="integer"),
     *     @SWG\Property(property="surveyId", type="integer"))
     * )
     * @Rest\Put(path="/physical-library-link-history",
     *     name="app_physical_library_link_history_insert_create")
     * @Rest\RequestParam(name="physicLibId", nullable=false, requirements="[0-9]*")
     * @Rest\RequestParam(name="docStructId", nullable=false, requirements="[0-9]*")
     * @Rest\RequestParam(name="surveyId", nullable=false, requirements="[0-9]*")
     * @Rest\View
     * @param int $physicLibId Physical library id.
     * @param int $docStructId Documentary structure id.
     * @param int $surveyId Survey id.
     * @return View Physical library link history line has just been created.
     */
    public function insertOrCreateAction(int $physicLibId, int $docStructId, int $surveyId): View
    {
        try
        {
            $this->checkRights([Role::ADMIN]);
            $physicLib = $this->getPhysicalLibraryById($physicLibId);
            $survey = $this->getSurveyById($surveyId);

            $response = $this->insertOrUpdatePhysicLibLinkHistory($physicLib, $survey, $docStructId);

            return $this->createView($this->formatLinkHistoryLineForResponse($response[0]), $response[1], true);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }

    /**
     * Format link history doctrine entity for http response.
     * @param PhysicalLibraryLinkHistory $historyLine Doctrine entity.
     * @return array Array that contains information for response.
     */
    private function formatLinkHistoryLineForResponse(PhysicalLibraryLinkHistory $historyLine): array
    {
        return [
            'physicLib' => $historyLine->getPhysicalLibrary(),
            'docStruct' => $historyLine->getDocumentaryStructure(),
            'surveyId' => $historyLine->getSurvey()->getId()
        ];
    }
}
