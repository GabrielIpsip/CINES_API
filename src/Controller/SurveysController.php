<?php

namespace App\Controller;

use App\Common\Enum\Role;
use App\Common\Enum\State;
use App\Common\Traits\StatesTrait;
use App\Common\Traits\SurveysTrait;
use App\Entity\PhysicalLibraryActiveHistory;
use App\Entity\Surveys;
use App\Entity\States;
use App\Utils\StringTools;
use DateTime;
use DateTimeZone;
use Exception;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use App\Controller\AbstractController\ESGBUController;

/**
 * Class SurveysController
 * @package App\Controller
 * @SWG\Tag(name="Surveys")
 */
class SurveysController extends ESGBUController
{
    use SurveysTrait,
        StatesTrait;

    /**
     * Show survey by id.
     * @SWG\Response(
     *     response="200",
     *     description="Return survey select by id.",
     *     @Model(type=Surveys::class)
     * )
     * @SWG\Response(response="404", description="No survey found.")
     * @SWG\Parameter(name="id",type="integer", in="path", description="Survey id.")
     * @Rest\Get(
     *      path="/surveys/{id}",
     *      name="app_surveys_show",
     *      requirements={"id"="\d+"}
     * )
     * @Rest\View
     * @param int $id Survey id
     * @return View Survey object with this id.
     */
    public function showAction(int $id) : View
    {
        try
        {
            $survey = $this->getSurveyById($id);
            return $this->createView($survey, Response::HTTP_OK);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Show all surveys.
     * @SWG\Response(
     *     response="200",
     *     description="Return surveys.",
     *     @SWG\Schema(type="array", @SWG\Items(ref=@Model(type=Surveys::class)))
     * )
     * @SWG\Response(response="404", description="No surveys found.")
     * @SWG\Parameter(name="open",type="boolean", in="query",
     *                description="True to show only open survey, else false.")
     * @Rest\Get(path="/surveys", name="app_surveys_list")
     * @Rest\QueryParam(name="open", requirements="true|false", default="false")
     * @Rest\View
     * @param string $open True to show only open surveys, else false;
     * @return View Array with all surveys.
     */
    public function listAction(string $open) : View
    {
        try
        {
            $open = StringTools::stringToBool($open);
            if ($open)
            {
                $openState = $this->getStateById(State::OPEN);
                $surveys = $this->getSurveyByCriteriaOrdered(array('state' => $openState),
                    array('creation' => 'DESC'));
            }
            else
            {
                $surveys = $this->getSurveyByCriteriaOrdered(array(), array('dataCalendarYear' => 'DESC'));
            }
            return $this->createView($surveys, Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Show all surveys.
     * @SWG\Response(
     *     response="200",
     *     description="Return surveys.",
     *    @SWG\Schema(type="array",
     *      @SWG\Items(type="object",
     *          @SWG\Property(property="id", type="integer"),
     *          @SWG\Property(property="name", type="string"),
     *          @SWG\Property(property="calendarYear", type="string"),
     *          @SWG\Property(property="dataCalendarYear", type="string"),
     *          @SWG\Property(property="start", type="string"),
     *          @SWG\Property(property="end", type="string"),
     *          @SWG\Property(property="state", ref=@Model(type=States::class))))
     * )
     * @SWG\Response(response="404", description="No surveys found.")
     * @SWG\Parameter(name="open",type="boolean", in="query",
     *                description="True to show only open survey, else false.")
     * @Rest\Get(path="/public/surveys", name="app_public_surveys_list")
     * @Rest\QueryParam(name="open", requirements="true|false", default="false")
     * @Rest\View
     * @param string $open True to show only open surveys, else false;
     * @return View Array with all surveys.
     */
    public function publicListAction(string $open) : View
    {
        try
        {
            $open = StringTools::stringToBool($open);
            if ($open)
            {
                $openState = $this->getStateById(State::OPEN);
                $surveys = $this->getSurveyByCriteriaOrdered(array('state' => $openState), array('creation' => 'DESC'));
            }
            else
            {
                $surveys = $this->getSurveyByCriteriaOrdered(array(), array('creation' => 'DESC'));
            }

            foreach ($surveys as &$survey)
            {
                $survey = $this->formatSurveyForPublic($survey);
            }
            return $this->createView($surveys, Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Update a survey.
     * @SWG\Response(
     *     response="200",
     *     description="Update a survey selected by id.",
     *     @Model(type=Surveys::class)
     * )
     * @SWG\Response(response="404", description="Data missing in database")
     * @SWG\Response(response="400", description="Error to update survey. Body not valid.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="id", type="integer", in="path", description="Survey id to update.")
     * @SWG\Parameter(name="body", in="body", description="Survey informations.",
     *     @SWG\Schema(type="object",
     *     @SWG\Property(property="name", type="string"),
     *     @SWG\Property(property="calendarYear", type="string"),
     *     @SWG\Property(property="dataCalendarYear", type="string"),
     *     @SWG\Property(property="start", type="string"),
     *     @SWG\Property(property="end", type="string"),
     *     @SWG\Property(property="instruction", type="string"),
     *     @SWG\Property(property="stateId", type="integer"),
     *     ))
     * @Rest\Put(
     *     path="/surveys/{id}",
     *     name="app_surveys_update",
     *     requirements={"id"="\d+"}
     * )
     * @Rest\RequestParam(name="stateId", requirements="[0-9]*", nullable=false)
     * @ParamConverter("newSurvey", converter="fos_rest.request_body")
     * @Rest\View
     * @param Surveys $newSurvey New survey information.
     * @param int $id Survey id to update.
     * @param int $stateId New survey state id.
     * @param ConstraintViolationListInterface $validationErrors Assert violation list.
     * @return View Establishment has just been updated.
     */
    public function updateAction(Surveys $newSurvey, int $id, int $stateId,
                                 ConstraintViolationListInterface $validationErrors) : View
    {
        try
        {
            $this->checkRights([Role::ADMIN]);

            $em = $this->managerRegistry->getManager();
            $existingSurvey = $this->getSurveyById($id);

            if (count($validationErrors) > 0)
            {
                return $this->createView($validationErrors, Response::HTTP_BAD_REQUEST, true);
            }

            $newSurveyState = $existingSurvey->getState();
            $existingSurveyStateId = $newSurveyState->getId();
            if ($existingSurveyStateId != $stateId)
            {
                $newSurveyState = $this->getStateById($stateId);
            }

            $existingSurvey->update($newSurvey, $newSurveyState);
            $em->flush();

            return $this->createView($existingSurvey, Response::HTTP_OK, true);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }

    /**
     * Create new survey.
     * @SWG\Response(
     *     response="201",
     *     description="Create a survey.",
     *     @Model(type=Surveys::class)
     * )
     * @SWG\Response(response="404", description="No survey state found.")
     * @SWG\Response(response="400", description="Bad request. Body not valid.")
     * @SWG\Response(response="409", description="Survey with same name already exists.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="body", in="body", description="Survey informations.",
     *     @SWG\Schema(type="object",
     *     @SWG\Property(property="name", type="string"),
     *     @SWG\Property(property="calendarYear", type="string"),
     *     @SWG\Property(property="dataCalendarYear", type="string"),
     *     @SWG\Property(property="start", type="string"),
     *     @SWG\Property(property="end", type="string"),
     *     @SWG\Property(property="instruction", type="string"),
     *     @SWG\Property(property="stateId", type="integer"),
     *     ))
     * @Rest\Post(path="/surveys", name="app_surveys_create")
     * @Rest\RequestParam(name="stateId", requirements="[0-9]*", nullable=false)
     * @Rest\View
     * @ParamConverter("survey", converter="fos_rest.request_body")
     * @param Surveys $survey Survey set in body request.
     * @param int $stateId Id of state assigned to survey.
     * @param ConstraintViolationListInterface $validationErrors Assert violation list.
     * @return View Survey has just been created.
     */
    public function createAction(Surveys $survey, int $stateId,
                                 ConstraintViolationListInterface $validationErrors) : View
    {
        try {
            $this->checkRights([Role::ADMIN]);
            $this->checkIfNameUnique($survey->getName());

            if (count($validationErrors) > 0) {
                return $this->createView($validationErrors, Response::HTTP_BAD_REQUEST, true);
            }

            $state = $this->getStateById($stateId);
            $survey->setState($state);

            $date = new DateTime();
            $date = $date->setTimezone(new DateTimeZone("UTC"));
            $survey->setCreation($date);

            $em = $this->managerRegistry->getManager();

            $lastSurvey = $this->managerRegistry->getRepository(Surveys::class)->getLastSurvey();

            $em->persist($survey);
            $em->flush();

            $activeHistories = $this->managerRegistry->getRepository(PhysicalLibraryActiveHistory::class)->findBySurvey($lastSurvey);

            foreach ($activeHistories as $activeHistory) {
                $physicalLibActiveHistory = new PhysicalLibraryActiveHistory($activeHistory->getAdministration(), $survey, $activeHistory->getActive());
                $em->persist($physicalLibActiveHistory);
            }
            $em->flush();

            return $this->createView($survey, Response::HTTP_CREATED, true);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }

    public static function formatSurveyForPublic(Surveys $survey): array
    {
        return [
            'id' => $survey->getId(),
            'name' => $survey->getName(),
            'calendarYear' => $survey->getCalendarYear(),
            'dataCalendarYear' => $survey->getDataCalendarYear(),
            'creation' => $survey->getCreation(),
            'start' => $survey->getStart(),
            'end' => $survey->getEnd(),
            'state' => $survey->getState()
        ];
    }

}
