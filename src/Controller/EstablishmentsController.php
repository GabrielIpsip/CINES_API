<?php

namespace App\Controller;

use App\Common\Enum\Role;
use App\Common\Enum\State;
use App\Common\Traits\AdministrationActiveHistoryTrait;
use App\Common\Traits\DepartmentsTrait;
use App\Common\Traits\EstablishmentsTrait;
use App\Common\Traits\EstablishmentTypesTrait;
use App\Common\Traits\SurveysTrait;
use App\Entity\DocumentaryStructures;
use App\Entity\Surveys;
use App\Utils\StringTools;
use Exception;
use FOS\RestBundle\View\View;
use App\Entity\Establishments;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use App\Controller\AbstractController\ESGBUController;

/**
 * Class EstablishmentsController
 * @package App\Controller
 * @SWG\Tag(name="Establishments")
 */
class EstablishmentsController extends ESGBUController
{
    use EstablishmentsTrait,
        EstablishmentTypesTrait,
        SurveysTrait,
        AdministrationActiveHistoryTrait,
        DepartmentsTrait;

    /**
     * Show establishment by id.
     * @SWG\Response(
     *     response="200",
     *     description="Return establishment select by id.",
     *     @Model(type=Establishments::class)
     * )
     * @SWG\Response(response="404", description="No establishement found.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="id",type="integer", in="path", description="Establishment id.")
     * @Rest\QueryParam(name="progress", strict=true, requirements="true|false", default="false")
     * @Rest\QueryParam(name="totalProgress", strict=true, requirements="true|false", default="false",
     *     description="Show total progress (establishment + all documentary structure + all physical library associated) for last open survey")
     * @Rest\Get(
     *      path="/establishments/{id}",
     *      name="app_establishments_show",
     *      requirements={"id"="\d+"}
     * )
     * @Rest\View
     * @param int $id Establishment id
     * @param string $progress Show progress for last open survey.
     * @param string $totalProgress Show total progress for last open survey.
     * @return View Establishment object with this id.
     */
    public function showAction(int $id, string $progress, string $totalProgress) : View
    {
        try
        {
            $establishment = $this->getEstablishmentById($id);

            $isAdmin = $this->checkRightsBool(
                [Role::ADMIN, Role::ADMIN_RO, Role::VALID_SURVEY_RESP, Role::SURVEY_ADMIN],
            null, null, null, false);
            if (!$isAdmin)
            {
                $this->checkRights([Role::USER], null, $establishment);
            }

            $progress = StringTools::stringToBool($progress);
            $totalProgress = StringTools::stringToBool($totalProgress);
            return $this->createView(
                $this->getFormattedEstablishmentForResponse([$establishment], false, $progress, $totalProgress),
                Response::HTTP_OK);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }


    /**
     * Show all establishments.
     * @SWG\Response(
     *     response="200",
     *     description="Return all establishment.",
     *     @SWG\Schema(type="array", @SWG\Items(ref=@Model(type=Establishments::class)))
     * )
     * @SWG\Response(response="404", description="No establishment found.")
     * @Rest\Get(path="/establishments", name="app_establishments_list")
     * @Rest\QueryParam(name="filters",default="")
     * @Rest\QueryParam(name="progress", strict=true, requirements="true|false", default="false",
     *     description="Show progress for last open survey")
     * @Rest\QueryParam(name="totalProgress", strict=true, requirements="true|false", default="false",
     *     description="Show total progress (establishment + all documentary structure + all physical library associated) for last open survey")
     * @Rest\View
     * @param string $filters Words list to filter result.
     * @param string $progress Show progress for last open survey.
     * @param string $totalProgress Show total progress for last open survey.
     * @return View Array with all establishment.
     */
    public function listAction(string $filters, string $progress, string $totalProgress) : View
    {
        try
        {
            $repo = $this->managerRegistry->getRepository(Establishments::class);
            $distrdOk = $this->checkRightsBool([Role::ADMIN, Role::ADMIN_RO]);
            $establishments = array();
            $progress = StringTools::stringToBool($progress);
            $totalProgress = StringTools::stringToBool($totalProgress);

            if ($distrdOk)
            {
                if ($filters)
                {
                    $filtersArray = StringTools::strToSearchKeywords($filters);
                    $establishments = $repo->search($filtersArray);
                }
                else
                {
                    $establishments = $repo->findBy(array(), array('active' => 'DESC', 'useName' => 'ASC'));
                }
            }
            else
            {
                $roles = [Role::VALID_SURVEY_RESP, Role::SURVEY_ADMIN, Role::USER];
                $docStructs = $this->managerRegistry->getRepository(DocumentaryStructures::class)
                    ->searchDocStructByEstablishment($this->getDocStructUser($roles),
                        StringTools::strToSearchKeywords($filters));
                foreach ($docStructs as $docStruct)
                {
                    if (!in_array($docStruct['establishment'], $establishments))
                    {
                        array_push($establishments, $docStruct['establishment']);
                    }
                }
            }
            if (count($establishments) === 0)
            {
                return $this->createView('No establishment found.', Response::HTTP_NOT_FOUND);
            }
            return $this->createView(
                $this->getFormattedEstablishmentForResponse($establishments, true, $progress, $totalProgress),
                Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Update an establishment.
     * @SWG\Response(
     *     response="200",
     *     description="Update an establishment selected by id.",
     *     @Model(type=Establishments::class)
     * )
     * @SWG\Response(response="404", description="Data missing in database")
     * @SWG\Response(response="400", description="Error to update establishment. Body not valid.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="id", type="integer", in="path", description="Establishment id to update.")
     * @SWG\Parameter(name="body", in="body", description="Establishment informations.",
     *     @SWG\Schema(type="object",
     *     @SWG\Property(property="officialName", type="string"),
     *     @SWG\Property(property="useName", type="string"),
     *     @SWG\Property(property="shortName", type="string"),
     *     @SWG\Property(property="acronym", type="string"),
     *     @SWG\Property(property="brand", type="string"),
     *     @SWG\Property(property="active", type="boolean"),
     *     @SWG\Property(property="address", type="string"),
     *     @SWG\Property(property="city", type="string"),
     *     @SWG\Property(property="postalCode", type="string"),
     *     @SWG\Property(property="website", type="string"),
     *     @SWG\Property(property="instruction", type="string"),
     *     @SWG\Property(property="typeId", type="integer"),
     *     @SWG\Property(property="departmentId", type="integer")
     *     ))
     * @Rest\Put(
     *     path="/establishments/{id}",
     *     name="app_establishments_update",
     *     requirements={"id"="\d+"}
     * )
     * @Rest\RequestParam(name="typeId", requirements="[0-9]*", nullable=false)
     * @Rest\RequestParam(name="departmentId", requirements="[0-9]*", nullable=false)
     * @ParamConverter("newEstablishment", converter="fos_rest.request_body")
     * @Rest\View
     * @param Establishments $newEstablishment New establishment information.
     * @param int $id Establishment id to update.
     * @param int $typeId New establishment type id.
     * @param int $departmentId
     * @param ConstraintViolationListInterface $validationErrors Assert violation list.
     * @return View Establishment has just been updated.
     */
    public function updateAction(Establishments $newEstablishment, int $id, int $typeId, int $departmentId,
                                 ConstraintViolationListInterface $validationErrors) : View
    {
        try
        {
            $em = $this->managerRegistry->getManager();
            $existingEstablishment = $this->getEstablishmentById($id);

            if (count($validationErrors) > 0)
            {
                return $this->createView($validationErrors, Response::HTTP_BAD_REQUEST, true);
            }

            // Check rights part : if user is not DISTRD, they can't modify use name and official name.
            $distrdOk = $this->checkRightsBool([Role::ADMIN]);
            $surveyAdminOk = false;
            if (!$distrdOk)
            {
                $surveyAdminOk = $this->checkRightsBool([Role::SURVEY_ADMIN], null, $existingEstablishment);

                if ($surveyAdminOk)
                {
                    $newEstablishment->setOfficialName($existingEstablishment->getOfficialName());
                    $newEstablishment->setActive($existingEstablishment->getActive());
                }
            }
            if (!$distrdOk && !$surveyAdminOk)
            {
                return $this->createView(
                    self::FORBIDDEN_ERROR, Response::HTTP_FORBIDDEN, true);
            }

            $newEstablishmentType = $existingEstablishment->getType();
            $newDepartment = $existingEstablishment->getDepartment();

            $existingEstablishmentTypeId = $newEstablishmentType->getId();
            $existingEstablishmentDepartmentId = $newDepartment->getId();
            if ($existingEstablishmentTypeId != $typeId)
            {
                $newEstablishmentType = $this->getEstablishmentTypeById($typeId);
            }
            if ($existingEstablishmentDepartmentId != $departmentId)
            {
                $newDepartment = $this->getDepartmentById($departmentId);
            }

            $existingEstablishment->update($newEstablishment, $newEstablishmentType, $newDepartment);
            $em->flush();

            $this->updateActiveHistoryForLastSurvey(
                Establishments::class, $existingEstablishment, $existingEstablishment->getActive());
            return $this->createView($existingEstablishment, Response::HTTP_OK, true);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }


    /**
     * Create new establishment.
     * @SWG\Response(
     *     response="201",
     *     description="Create an establishment.",
     *     @Model(type=Establishments::class)
     * )
     * @SWG\Response(response="404", description="No establishment type found.")
     * @SWG\Response(response="400", description="Bad request. Body not valid.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="body", in="body", description="Establishment informations.",
     *     @SWG\Schema(type="object",
     *     @SWG\Property(property="officialName", type="string"),
     *     @SWG\Property(property="useName", type="string"),
     *     @SWG\Property(property="shortName", type="string"),
     *     @SWG\Property(property="acronym", type="string"),
     *     @SWG\Property(property="brand", type="string"),
     *     @SWG\Property(property="active", type="boolean"),
     *     @SWG\Property(property="address", type="string"),
     *     @SWG\Property(property="city", type="string"),
     *     @SWG\Property(property="postalCode", type="string"),
     *     @SWG\Property(property="website", type="string"),
     *     @SWG\Property(property="instruction", type="string"),
     *     @SWG\Property(property="typeId", type="integer"),
     *     @SWG\Property(property="departmentId", type="integer")
     *     ))
     * @Rest\Post(path="/establishments", name="app_establishments_create")
     * @Rest\RequestParam(name="typeId", requirements="[0-9]*", nullable=false)
     * @Rest\RequestParam(name="departmentId", requirements="[0-9]*", nullable=false)
     * @Rest\View
     * @ParamConverter("establishment", converter="fos_rest.request_body")
     * @param Establishments $establishment Establishment set in body request.
     * @param int $typeId Id of type assigned to establishment.
     * @param int $departmentId
     * @param ConstraintViolationListInterface $validationErrors Assert violation list.
     * @return View Establishment has just been created.
     */
    public function createAction(Establishments $establishment, int $typeId, int $departmentId,
                                 ConstraintViolationListInterface $validationErrors) : View
    {
        try
        {
            $this->checkRights([Role::ADMIN]);

            if (count($validationErrors) > 0)
            {
                return $this->createView($validationErrors, Response::HTTP_BAD_REQUEST, true);
            }

            $establishmentType = $this->getEstablishmentTypeById($typeId);
            $establishment->setType($establishmentType);

            $department = $this->getDepartmentById($departmentId);
            $establishment->setDepartment($department);

            $em = $this->managerRegistry->getManager();

            $em->persist($establishment);
            $em->flush();

            $this->updateActiveHistoryForLastSurvey(
                Establishments::class, $establishment, $establishment->getActive(), true);

            return $this->createView($establishment, Response::HTTP_CREATED, true);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }

    /**
     * Get global progress for last active survey.
     * @SWG\Response(
     *     response="200",
     *     description="Global progress for last active survey.",
     *     @SWG\Schema(type="integer")
     * )
     * @SWG\Response(response="404", description="No establishment type found.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @Rest\Get(path="/establishments/global-progress", name="app_establishments_global_progress")
     * @Rest\View
     * @return View Global progress
     */
    public function globalProgress() : View
    {
        try
        {
            $this->checkRights([Role::ADMIN, Role::ADMIN_RO]);

            $establishments = $this->managerRegistry->getRepository(Establishments::class)
                ->findBy(array('active' => true));

            $nbrEstablishment = count($establishments);
            if ($nbrEstablishment === 0)
            {
                return $this->createView('No establishment found.', Response::HTTP_NOT_FOUND);
            }

            $survey = $this->initLastSurveyForProgress();

            $establishments = $this->getFormattedEstablishmentForResponse($establishments,
                true, false, true, $survey);

            $globalProgress = 0;
            foreach ($establishments as $establishment)
            {
                $globalProgress += $establishment['totalProgress'];
            }

            $response['survey'] = $survey;
            $response['globalProgress'] = $globalProgress / $nbrEstablishment;

            return $this->createView($response, Response::HTTP_OK);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Format an array of establishment for response.
     * @param array $establishmentArray Establishment doctrine entity or array representation.
     * @param bool $arrayResponse True for get response like array of establishment.
     * @param bool $progress Show progress of last survey for this establishment in response.
     * @param bool $totalProgress Show total progress of last survey for this establishment in response.
     * @param Surveys|null $survey Optional : Survey for progress, or last survey.
     * @return array|null Null if result $docStructArray is null, or array with all establishments formatted for
     *                    response.
     * @throws Exception 404 : Error to find open survey.
     */
    private function getFormattedEstablishmentForResponse(array $establishmentArray, bool $arrayResponse = false,
                                                          bool $progress = false, bool $totalProgress = false,
                                                          ?Surveys $survey = null)
    {
        if (!count($establishmentArray) === 0)
        {
            return null;
        }

        if ($progress || $totalProgress)
        {
            if ($survey)
            {
                $lastSurvey = $survey;
            }
            else
            {
                $lastSurvey = $this->initLastSurveyForProgress();
            }

            if ($establishmentArray[0] instanceof Establishments)
            {
                $this->getFormattedEstablishmentByArrayForResponse($establishmentArray);
            }
            if ($progress)
            {
                $this->getProgress($establishmentArray, $lastSurvey);
            }
            if ($totalProgress)
            {
                $this->getTotalProgress($establishmentArray, $lastSurvey);
            }
        }

        if (count($establishmentArray) === 1 && !$arrayResponse)
        {
            return $establishmentArray[0];
        }

        return $establishmentArray;
    }

    /**
     * Get last survey, use for show progress.
     * @return Surveys Last survey, depend if DISTRD or not.
     * @throws Exception 404 : No survey found.
     */
    private function initLastSurveyForProgress(): Surveys
    {
        $isDISTRD = $this->checkRightsBool([Role::ADMIN, Role::ADMIN_RO]);
        if ($isDISTRD)
        {
            return $this->getLastActiveSurvey();
        }
        else
        {
            return $this->getLastSurveyByState(State::OPEN);
        }
    }

    /**
     * Format establishments for response, replace doctrine establishment entity by array representation of
     * establishment.
     * @param array $establishmentArray Array of establishments doctrine entity.
     * @return void Modify $establishmentArray with establishment array representation.
     */
    private function getFormattedEstablishmentByArrayForResponse(array& $establishmentArray)
    {
        foreach ($establishmentArray as &$establishment)
        {
            $establishment = array('id' => $establishment->getId(),
                'officialName' => $establishment->getOfficialName(),
                'useName' => $establishment->getUseName(),
                'acronym' => $establishment->getAcronym(),
                'brand' => $establishment->getBrand(),
                'active' => $establishment->getActive(),
                'address' => $establishment->getAddress(),
                'city' => $establishment->getCity(),
                'postalCode' => $establishment->getPostalCode(),
                'website' => $establishment->getWebsite(),
                'instruction' => $establishment->getInstruction(),
                'type' => $establishment->getType(),
                'department' => $establishment->getDepartment());
        }
    }

    /**
     * Add progress for each establishment in array in parameter.
     * @param array $establishmentArray Array that contains all establishments to format.
     * @param Surveys $survey Progress for this survey.
     */
    private function getProgress(array &$establishmentArray, Surveys $survey)
    {
        $progress = $this->managerRegistry->getRepository(Establishments::class)
            ->getNbrResponse($establishmentArray, $survey);

        foreach ($establishmentArray as &$establishment)
        {
            if (!$progress)
            {
                $establishment['progress'] = 0;
            }
            else if (array_key_exists($establishment['id'], $progress))
            {
                $establishment['progress'] = $progress[$establishment['id']];
            }
            else
            {
                $establishment['progress'] = 0;
            }
        }
    }

    /**
     * Add total progress for each establishment in array in parameter.
     * @param array $establishmentArray Array that contains all establishments.
     * @param Surveys $survey Total progress for this survey.
     * @throws Exception 404 : Error to find open survey.
     */
    private function getTotalProgress(array &$establishmentArray, Surveys $survey)
    {
        $totalProgress = $this->managerRegistry->getRepository(Establishments::class)
            ->getTotalProgress($establishmentArray, $survey);

        foreach ($establishmentArray as &$establishment)
        {
            if (!$totalProgress)
            {
                $establishment['totalProgress'] = 0;
            }
            else if (array_key_exists($establishment['id'], $totalProgress))
            {
                $establishment['totalProgress'] = $totalProgress[$establishment['id']];
            }
            else
            {
                $establishment['totalProgress'] = 0;
            }
        }
    }
}
