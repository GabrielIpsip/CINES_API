<?php

namespace App\Controller;

use App\Common\Enum\Role;
use App\Common\Enum\State;
use App\Common\Traits\AdministrationActiveHistoryTrait;
use App\Common\Traits\DepartmentsTrait;
use App\Common\Traits\DocumentaryStructuresTrait;
use App\Common\Traits\PhysicalLibrariesTrait;
use App\Common\Traits\PhysicalLibraryLinkHistoryTrait;
use App\Common\Traits\SurveysTrait;
use App\Entity\PhysicalLibraries;
use App\Entity\Surveys;
use App\Utils\StringTools;
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
 * Class PhysicalLibrariesController
 * @package App\Controller
 * @SWG\Tag(name="Physical libraries")
 */
class PhysicalLibrariesController extends ESGBUController
{
    use PhysicalLibrariesTrait,
        DocumentaryStructuresTrait,
        SurveysTrait,
        AdministrationActiveHistoryTrait,
        PhysicalLibraryLinkHistoryTrait,
        DepartmentsTrait;

    /**
     * Show physical library by id.
     * @SWG\Response(
     *     response="200",
     *     description="Return physical library select by id.",
     *     @SWG\Items(allOf={
     *        @SWG\Schema(ref=@Model(type=PhysicalLibraries::class))},
     *        @SWG\Property(property="docStructId", type="integer")))
     * )
     * @SWG\Response(response="404", description="No physical library found.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="id",type="integer", in="path", description="Physical library id.")
     * @Rest\QueryParam(name="progress", strict=true, requirements="true|false", default="false")
     * @Rest\Get(
     *      path="/physical-libraries/{id}",
     *      name="app_physical_libraries_show",
     *      requirements={"id"="\d+"}
     * )
     * @Rest\View
     * @param int $id Physical library id.
     * @param string $progress Show progress for last open survey.
     * @return View Physical library object with this id.
     */
    public function showAction(int $id, string $progress) : View
    {
        try
        {
            $physicLib = $this->getPhysicalLibraryById($id);

            $isAdmin = $this->checkRightsBool(
                [Role::ADMIN, Role::ADMIN_RO, Role::VALID_SURVEY_RESP, Role::SURVEY_ADMIN],
                null, null, null, false);
            if (!$isAdmin)
            {
                $this->checkRights([Role::USER], null, null, $physicLib);
            }

            $progress = StringTools::stringToBool($progress);
            return $this->createView($this->getFormattedPhysicLibForResponse([$physicLib], false, $progress),
                Response::HTTP_OK);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }


    /**
     * Show all physical libraries.
     * @SWG\Response(
     *     response="200",
     *     description="Return all physical libraries.",
     *     @SWG\Schema(type="array",
     *         @SWG\Items(allOf={
     *          @SWG\Schema(ref=@Model(type=PhysicalLibraries::class))},
     *          @SWG\Property(property="docStructId", type="integer")))
     * )
     * @SWG\Response(response="404", description="No physical library found.")
     * @SWG\Parameter(name="docStructId",type="integer", in="query",
     *     description="Documentary structure id to get all physical library linked.")
     * @Rest\Get(path="/physical-libraries", name="app_physical_libraries_list")
     * @Rest\QueryParam(name="filters",default="")
     * @Rest\QueryParam(name="docStructId",requirements="\d+",nullable=true,
     *     description="All physical libraries are linked with this documentary structure")
     * @Rest\QueryParam(name="progress", strict=true, requirements="true|false", default="false",
     *     description="Show progress for last open survey")
     * @Rest\QueryParam(name="active", default=null)
     * @Rest\QueryParam(name="surveyId", default=null)
     * @Rest\View
     * @param string $filters Words list to filter result.
     * @param string|null $docStructId All physical libraries are linked with this documentary structure.
     * @param string $progress Show progress for last open survey.
     * @param bool $active To filter active libraries
     * @param string $surveyId To filter active libraries in survey
     * @return View Array with all physical libraries.
     */
    public function listAction(string $filters, ?string $docStructId, string $progress, ?bool $active, ?string $surveyId) : View
    {
        try
        {
            $repo = $this->managerRegistry->getRepository(PhysicalLibraries::class);
            $criteria = array();
            $orderBy = array('active' => 'DESC', 'useName' => 'ASC');
            $docStruct = null;
            $survey = null;
            $progress = StringTools::stringToBool($progress);

            if ($docStructId)
            {
                $docStruct = $this->getDocStructById($docStructId);
                $criteria['documentaryStructure'] = $docStruct;
                $orderBy = array('sortOrder' => 'ASC');
            }

            if ($surveyId) {
                $survey = $this->getSurveyById($surveyId);
                $orderBy = array('sortOrder' => 'ASC');
            }

            if ($active) {
                $criteria['active'] = $active;
            }

            $isDISTRD = $this->checkRightsBool([Role::ADMIN, Role::ADMIN_RO],
                null, null, null, false);
            $isAdmin = $this->checkRightsBool(
                [Role::ADMIN, Role::ADMIN_RO,  Role::SURVEY_ADMIN, Role::VALID_SURVEY_RESP, Role::USER],
                null, null, null, false);
            $physicLibArray = array();

            if ($isDISTRD || ($isAdmin && $docStructId))
            {
                if ($filters)
                {
                    $physicLibArray = $repo->search(StringTools::strToSearchKeywords($filters), $orderBy, $docStruct);
                }
                else
                {
                    if ($active) {
                        $physicLibArray = $repo->searchActive($survey, $orderBy, $docStruct);
                    } else {
                        $physicLibArray = $repo->findBy($criteria, $orderBy);
                    }
                }
            }
            else
            {
                $roles = [Role::VALID_SURVEY_RESP, Role::SURVEY_ADMIN, Role::USER];
                $docStructFilter = $this->getDocStructUser($roles);
                if ($docStructFilter)
                {
                    $physicLibArray = $repo->search(
                        StringTools::strToSearchKeywords($filters), $orderBy, $docStruct, $docStructFilter
                    );
                }
            }

            $physicLibArray = $this->getFormattedPhysicLibForResponse($physicLibArray, true, $progress);

            if (count($physicLibArray) === 0)
            {
                return $this->createView('No physical library found.', Response::HTTP_NOT_FOUND);
            }

            return $this->createView($physicLibArray, Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Update a physical library.
     * @SWG\Response(
     *     response="200",
     *     description="Update a physical library selected by id.",
     *     @SWG\Items(allOf={
     *        @SWG\Schema(ref=@Model(type=PhysicalLibraries::class))},
     *        @SWG\Property(property="docStructId", type="integer")))
     * )
     * @SWG\Response(response="404", description="Data missing in database")
     * @SWG\Response(response="400", description="Error to update physical library. Body not valid.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="id", type="integer", in="path", description="Physical library id to update.")
     * @SWG\Parameter(name="body", in="body", description="Physical library informations.",
     *     @SWG\Schema(type="object",
     *     @SWG\Property(property="officialName", type="string"),
     *     @SWG\Property(property="useName", type="string"),
     *     @SWG\Property(property="active", type="boolean"),
     *     @SWG\Property(property="fictitious", type="boolean"),
     *     @SWG\Property(property="address", type="string"),
     *     @SWG\Property(property="city", type="string"),
     *     @SWG\Property(property="postalCode", type="string"),
     *     @SWG\Property(property="instruction", type="string"),
     *     @SWG\Property(property="sortOrder", type="integer"),
     *     @SWG\Property(property="docStructId", type="integer"),
     *     @SWG\Property(property="departmentId", type="integer")
     *     ))
     * @Rest\Put(
     *     path="/physical-libraries/{id}",
     *     name="app_physical_libraries_update",
     *     requirements={"id"="\d+"}
     * )
     * @Rest\RequestParam(name="docStructId", requirements="[0-9]*", nullable=false)
     * @Rest\RequestParam(name="departmentId", requirements="[0-9]*", nullable=false)
     * @ParamConverter("newPhysicLib", converter="fos_rest.request_body")
     * @Rest\View
     * @param PhysicalLibraries $newPhysicLib New physical library information.
     * @param int $id Physical library id to update.
     * @param int $docStructId Linked documentary structure id.
     * @param ConstraintViolationListInterface $validationErrors Assert violation list.
     * @return View Establishment has just been updated.
     */
    public function updateAction(PhysicalLibraries $newPhysicLib, int $id, int $docStructId, int $departmentId,
                                 ConstraintViolationListInterface $validationErrors) : View
    {
        try
        {
            if (count($validationErrors) > 0)
            {
                return $this->createView($validationErrors, Response::HTTP_BAD_REQUEST, true);
            }

            $existingPhysicLib = $this->getPhysicalLibraryById($id);
            $docStruct = $this->getDocStructById($docStructId);

            // Check rights part : if user is not DISTRD, they can't modify use name and official name.
            $distrdOk = $this->checkRightsBool([Role::ADMIN]);
            $surveyAdminOk = $this->checkRightsBool([Role::SURVEY_ADMIN, Role::VALID_SURVEY_RESP], $docStruct);

            if (!$distrdOk && !$surveyAdminOk)
            {
                return $this->createView(self::FORBIDDEN_ERROR, Response::HTTP_FORBIDDEN, true);
            }

            if ($surveyAdminOk && !$distrdOk)
            {
                $newPhysicLib->setOfficialName($existingPhysicLib->getOfficialName());
            }

            if ($newPhysicLib->getSortOrder() && $newPhysicLib->getSortOrder() != $existingPhysicLib->getSortOrder())
            {
                $this->updateSortOrderForPhysicLib($existingPhysicLib, $newPhysicLib, $docStruct);
            }
            else if ($newPhysicLib->getSortOrder() &&
                $existingPhysicLib->getDocumentaryStructure()->getId() != $docStructId)
            {
                $this->AddOrCreateSortOrder($newPhysicLib, $docStruct);
            }
            else
            {
                $newPhysicLib->setSortOrder($existingPhysicLib->getSortOrder());
            }

            $newDepartment = $existingPhysicLib->getDepartment();

            $existingPhysicLibDepartmentId = $newDepartment->getId();
            if ($existingPhysicLibDepartmentId != $departmentId)
            {
                $newDepartment = $this->getDepartmentById($departmentId);
            }

            $existingPhysicLib->update($newPhysicLib, $docStruct, $newDepartment);
            $this->managerRegistry->getManager()->flush();

            $this->updateActiveHistoryForLastSurvey(
                PhysicalLibraries::class, $existingPhysicLib, $existingPhysicLib->getActive());
            $this->updateDocStructLinkHistoryForLastSurvey(
                $existingPhysicLib, $existingPhysicLib->getDocumentaryStructure()->getId());

            return $this->createView(
                $this->getFormattedPhysicLibForResponse([$existingPhysicLib]), Response::HTTP_OK, true);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }


    /**
     * Create new physical library.
     * @SWG\Response(
     *     response="201",
     *     description="Create a physical library.",
     *     @SWG\Items(allOf={
     *        @SWG\Schema(ref=@Model(type=PhysicalLibraries::class))},
     *        @SWG\Property(property="docStructId", type="integer")))
     * )
     * @SWG\Response(response="400", description="Bad request. Body not valid.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="body", in="body", description="Physical library informations.",
     *     @SWG\Schema(type="object",
     *     @SWG\Property(property="officialName", type="string"),
     *     @SWG\Property(property="useName", type="string"),
     *     @SWG\Property(property="active", type="boolean"),
     *     @SWG\Property(property="fictitious", type="boolean"),
     *     @SWG\Property(property="address", type="string"),
     *     @SWG\Property(property="city", type="string"),
     *     @SWG\Property(property="postalCode", type="string"),
     *     @SWG\Property(property="instruction", type="string"),
     *     @SWG\Property(property="sortOrder", type="integer"),
     *     @SWG\Property(property="docStructId", type="integer"),
     *     @SWG\Property(property="departmentId", type="integer")
     *     ))
     * @Rest\Post(path="/physical-libraries", name="app_physical_library_create")
     * @Rest\RequestParam(name="docStructId", requirements="[0-9]*", nullable=false)
     * @Rest\RequestParam(name="departmentId", requirements="[0-9]*", nullable=false)
     * @Rest\View
     * @ParamConverter("physicLib", converter="fos_rest.request_body")
     * @param PhysicalLibraries $physicLib Physical library set in body request.
     * @param int $docStructId Id of documentary structure assigned to physical library.
     * @param ConstraintViolationListInterface $validationErrors Assert violation list.
     * @return View Establishment has just been created.
     */
    public function createAction(PhysicalLibraries $physicLib, int $docStructId, int $departmentId,
                                 ConstraintViolationListInterface $validationErrors) : View
    {
        try
        {
            if (count($validationErrors) > 0)
            {
                return $this->createView($validationErrors, Response::HTTP_BAD_REQUEST, true);
            }

            $docStruct = $this->getDocStructById($docStructId);
            $department = $this->getDepartmentById($departmentId);

            $this->checkRights([Role::ADMIN, Role::SURVEY_ADMIN, Role::VALID_SURVEY_RESP], $docStruct);

            $this->AddOrCreateSortOrder($physicLib, $docStruct);

            $physicLib->setDocumentaryStructure($docStruct);
            $physicLib->setDepartment($department);

            $em = $this->getDoctrine()->getManager();

            $em->persist($physicLib);
            $em->flush();

            $this->updateActiveHistoryForLastSurvey(
                PhysicalLibraries::class, $physicLib, $physicLib->getActive(), true);
            $this->updateDocStructLinkHistoryForLastSurvey(
                $physicLib, $physicLib->getDocumentaryStructure()->getId());

            return $this->createView(
                $this->getFormattedPhysicLibForResponse([$physicLib]), Response::HTTP_CREATED, true);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }

    /**
     * Format an array of physical libraries for response.
     * @param array $physicLibArray Physical library doctrine entity or array representation.
     * @param bool $arrayResponse True for get response like array of physical library.
     * @param bool $progress Show progress of last survey for this physical library in response.
     * @return array|null Null if result $physicLibArray is null, or array with all physical libraries formatted for
     *                    response.
     * @throws Exception 404 : Error to find open survey.
     */
    private function getFormattedPhysicLibForResponse(array $physicLibArray, bool $arrayResponse = false,
                                                      bool $progress = false) : ?array
    {
        if (!count($physicLibArray) === 0)
        {
            return null;
        }

        $result = array();

        foreach ($physicLibArray as $physicLib)
        {

            if ($physicLib instanceof PhysicalLibraries)
            {
                array_push($result, $this->getFormattedPhysicLibByEntityForResponse($physicLib));
            } else
            {
                array_push($result, $this->getFormattedPhysicLibByArrayForResponse($physicLib));
            }
        }

        if ($progress)
        {
            $isDISTRD = $this->checkRightsBool([Role::ADMIN, Role::ADMIN_RO]);
            if ($isDISTRD)
            {
                $lastSurvey = $this->getLastActiveSurvey();
            }
            else
            {
                $lastSurvey = $this->getLastSurveyByState(State::OPEN);
            }

            $this->getProgress($result, $lastSurvey);
        }

        if (count($result) === 1 && !$arrayResponse)
        {
            return $result[0];
        }

        return $result;
    }

    /**
     * Format physical library for response.
     * @param PhysicalLibraries $physicLib Physical library doctrine entity.
     * @return array Formatted physical library in array format.
     */
    private function getFormattedPhysicLibByEntityForResponse(PhysicalLibraries $physicLib): array
    {
        return array('id' => $physicLib->getId(),
            'officialName' => $physicLib->getOfficialName(),
            'useName' => $physicLib->getUseName(),
            'address' => $physicLib->getAddress(),
            'postalCode' => $physicLib->getPostalCode(),
            'city' => $physicLib->getCity(),
            'active' => $physicLib->getActive(),
            'instruction' => $physicLib->getInstruction(),
            'sortOrder' => $physicLib->getSortOrder(),
            'fictitious' => $physicLib->getFictitious(),
            'docStructId' => $physicLib->getDocumentaryStructure()->getId(),
            'department' => $physicLib->getDepartment());
    }

    /**
     * Format physical library for response.
     * @param array $physicLib Physical library array representation. (no doctrine entity)
     * @return array Formatted physical library in array format.
     */
    private function getFormattedPhysicLibByArrayForResponse(array $physicLib): array
    {
        return array('id' => $physicLib['id'],
            'officialName' => $physicLib['officialName'],
            'useName' => $physicLib['useName'],
            'address' => $physicLib['address'],
            'postalCode' => $physicLib['postalCode'],
            'city' => $physicLib['city'],
            'active' => $physicLib['active'],
            'instruction' => $physicLib['instruction'],
            'sortOrder' => $physicLib['sortOrder'],
            'fictitious' => $physicLib['fictitious'],
            'docStructId' => $physicLib['documentaryStructure']['id'],
            'department' => $physicLib['department']);
    }

    /**
     * Add progress for each physical libraries in array in parameter.
     * @param array $physicLibArray Array that contains all physical libraries to format.
     * @param Surveys $survey
     * @throws Exception 404 : Error to find open survey.
     */
    private function getProgress(array &$physicLibArray, Surveys $survey)
    {
        $progress = $this->getDoctrine()->getRepository(PhysicalLibraries::class)
            ->getNbrResponse($physicLibArray, $survey);

        foreach ($physicLibArray as &$physicLib)
        {
            if (!$progress)
            {
                $physicLib['progress'] = 0;
            }
            else if (array_key_exists($physicLib['id'], $progress))
            {
                $physicLib['progress'] = $progress[$physicLib['id']];
            }
            else
            {
                $physicLib['progress'] = 0;
            }
        }
    }
}
