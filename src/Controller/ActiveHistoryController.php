<?php


namespace App\Controller;


use App\Common\Enum\Role;
use App\Common\Traits\AdministrationActiveHistoryTrait;
use App\Common\Traits\AdministrationsTrait;
use App\Common\Traits\AdministrationTypesTrait;
use App\Common\Traits\SurveysTrait;
use App\Entity\AbstractEntity\AdministrationActiveHistory;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Response;
use App\Controller\AbstractController\ESGBUController;
use Nelmio\ApiDocBundle\Annotation\Model;
use App\Entity\AbstractEntity\Administrations;

/**
 * Class ActiveHistoryController
 * @package App\Controller
 * @SWG\Tag(name="Active history controller")
 */
class ActiveHistoryController extends ESGBUController
{
    use AdministrationTypesTrait,
        AdministrationsTrait,
        AdministrationActiveHistoryTrait,
        SurveysTrait;

    /**
     * Show all groups.
     * @SWG\Response(
     *     response="200",
     *     description="List active history of administration.",
     *     @SWG\Schema(type="array",
     *      @SWG\Items(type="object",
     *              @SWG\Property(property="administrationType", type="string"),
     *              @SWG\Property(property="surveyId", type="integer"),
     *              @SWG\Property(property="administration", ref=@Model(type=Administrations::class)),
     *              @SWG\Property(property="active", type="boolean")))
     * )
     * @SWG\Response(response="404", description="No active history found.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @Rest\Get(path="/active-history", name="app_active_history_list")
     * @Rest\QueryParam(name="administrationType", strict=true,
     *     requirements="institution|documentaryStructure|physicalLibrary", nullable=false,
     *     description="Administration type")
     * @Rest\QueryParam(name="administrationId", requirements="\d+", nullable=false, description="Administration Id")
     * @Rest\View
     * @param string|null $administrationType Administration type to get history.
     * @param int $administrationId Administration Id.
     * @return View Active history of administration in JSON object.
     */
    public function listAction(string $administrationType, int $administrationId) : View
    {
        try
        {
            $administrationTypeId = $this->getAdministrationTypeIdByName($administrationType);
            $administrationClass = $this->getAdministrationClassByAdministrationType($administrationTypeId);
            $administration = $this->getAdministrationById($administrationClass, $administrationId);
            $history = $this->getActiveHistory($administrationClass, $administration);

            foreach ($history as &$historyLine)
            {
                $historyLine = $this->formatHistoryForResponse($historyLine, $administrationType);
            }

            return $this->createView($history, Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Insert or create new active history line.
     * @SWG\Response(
     *     response="201",
     *     description="Active history line has been created.",
     *     @SWG\Schema(type="object",
     *          @SWG\Property(property="administrationType", type="string"),
     *          @SWG\Property(property="surveyId", type="integer"),
     *          @SWG\Property(property="administration", ref=@Model(type=Administrations::class)),
     *          @SWG\Property(property="active", type="boolean")))
     * )
     * @SWG\Response(
     *     response="200",
     *     description="Active history line has been updated.",
     *     @SWG\Schema(type="object",
     *          @SWG\Property(property="administrationType", type="string"),
     *          @SWG\Property(property="surveyId", type="integer"),
     *          @SWG\Property(property="administration", ref=@Model(type=Administrations::class)),
     *          @SWG\Property(property="active", type="boolean")))
     * )
     * @SWG\Response(response="404", description="No survey, administrationType or administration found.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="body", in="body", description="History line information.",
     *     @SWG\Schema(type="object",
     *     @SWG\Property(property="administrationType", type="string"),
     *     @SWG\Property(property="administrationId", type="integer"),
     *     @SWG\Property(property="surveyId", type="integer"),
     *     @SWG\Property(property="active", type="boolean"))
     * )
     * @Rest\Put(path="/active-history", name="app_active_history_insert_create")
     * @Rest\RequestParam(name="administrationType", nullable=false,
     *     requirements="institution|documentaryStructure|physicalLibrary")
     * @Rest\RequestParam(name="administrationId", nullable=false, requirements="[0-9]*")
     * @Rest\RequestParam(name="surveyId", nullable=false, requirements="[0-9]*")
     * @Rest\RequestParam(name="active", nullable=false)
     * @Rest\View
     * @param string $administrationType Administration type name.
     * @param int $administrationId Administration id.
     * @param int $surveyId Survey id.
     * @param bool $active True to set active, else false to disable for the survey.
     * @return View History line has just been created.
     */
    public function insertOrUpdate(string $administrationType, int $administrationId, int $surveyId, bool $active)
    : View
    {
        try
        {
            $this->checkRights([Role::ADMIN]);
            $administrationTypeId = $this->getAdministrationTypeIdByName($administrationType);
            $administrationClass = $this->getAdministrationClassByAdministrationType($administrationTypeId);

            $administration = $this->getAdministrationById($administrationClass, $administrationId);
            $survey = $this->getSurveyById($surveyId);

            $lastSurvey = $this->getLastSurvey();
            if ($survey->getId() === $lastSurvey->getId())
            {
                $administration->setActive($active);
            }

            $response = $this->insertOrUpdateActiveHistoryLine($administrationClass, $administration, $survey, $active);

            return $this->createView(
                $this->formatHistoryForResponse($response[0], $administrationType), $response[1], true);
        }
        catch (Exception $e)
        {
            print_r($e->getMessage());
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }

    /**
     * Format active history doctrine entity for http response.
     * @param AdministrationActiveHistory $history Doctrine entity.
     * @param string $administrationType Administration type name.
     * @return array Array that contains information for response.
     */
    private function formatHistoryForResponse(AdministrationActiveHistory $history, string $administrationType): array
    {
            return [
                'administration' => $history->getAdministration(),
                'surveyId' => $history->getSurvey()->getId(),
                'administrationType' => $administrationType,
                'active' => $history->getActive()
            ];
    }



}