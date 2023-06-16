<?php

namespace App\Controller;

use App\Common\Enum\Role;
use App\Common\Enum\State;
use App\Common\Traits\AdministrationActiveHistoryTrait;
use App\Common\Traits\DepartmentsTrait;
use App\Common\Traits\DocumentaryStructureLinkHistoryTrait;
use App\Common\Traits\DocumentaryStructuresTrait;
use App\Common\Traits\EstablishmentsTrait;
use App\Common\Traits\SurveysTrait;
use App\Entity\DocumentaryStructures;
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
 * Class DocumentaryStructures
 * @package App\Controller
 * @SWG\Tag(name="Documentary structures")
 */
class DocumentaryStructuresController extends ESGBUController
{
    use DocumentaryStructuresTrait,
        EstablishmentsTrait,
        SurveysTrait,
        AdministrationActiveHistoryTrait,
        DocumentaryStructureLinkHistoryTrait,
        DepartmentsTrait;

    /**
     * Show documentary structure by id.
     * @SWG\Response(
     *     response="200",
     *     description="Return documentary structure select by id.",
     *     @SWG\Items(allOf={
     *        @SWG\Schema(ref=@Model(type=DocumentaryStructures::class))},
     *        @SWG\Property(property="establishmentId", type="integer")))
     * )
     * @SWG\Response(response="404", description="No documentary structure found.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="id",type="integer", in="path", description="Documentary structure id.")
     * @Rest\QueryParam(name="progress", strict=true, requirements="true|false", default="false",
     *     description="Show progress for last open survey")
     * @Rest\QueryParam(name="totalProgress", strict=true, requirements="true|false", default="false",
     *     description="Show total progress (documentary structure + all physical library associated) for last open survey")
     * @Rest\Get(
     *      path="/documentary-structures/{id}",
     *      name="app_documentary_structures_show",
     *      requirements={"id"="\d+"}
     * )
     * @Rest\View
     * @param int $id Documentary structure id.
     * @param string $progress Show progress for last open survey.
     * @param string $totalProgress Show total progress for last open survey.
     * @return View Documentary structure object with this id.
     */
    public function showAction(int $id, string $progress, string $totalProgress) : View
    {

        try
        {
            $docStruct = $this->getDocStructById($id);

            $isAdmin = $this->checkRightsBool(
                [Role::ADMIN, Role::ADMIN_RO, Role::VALID_SURVEY_RESP, Role::SURVEY_ADMIN],
                null, null, null, false);

            if (!$isAdmin)
            {
                $this->checkRights([Role::USER], $docStruct);
            }

            $progress = StringTools::stringToBool($progress);
            $totalProgress = StringTools::stringToBool($totalProgress);
            return $this->createView(
                $this->getFormattedSerialDocStructForResponse(
                    [$docStruct], false, false, $progress, $totalProgress),
                Response::HTTP_OK);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }

    }


    /**
     * Show all documentary structures.
     * @SWG\Response(
     *     response="200",
     *     description="Return all documentary structures.",
     *     @SWG\Schema(type="array",
     *         @SWG\Items(allOf={
     *          @SWG\Schema(ref=@Model(type=DocumentaryStructures::class))},
     *          @SWG\Property(property="establishmentId", type="integer")))
     * )
     * @SWG\Response(response="404", description="No documentary structure found.")
     * @Rest\Get(path="/documentary-structures", name="app_documentary_structures_list")
     * @Rest\QueryParam(name="filters",default="")
     * @Rest\QueryParam(name="establishmentId",requirements="\d+",nullable=true)
     * @Rest\QueryParam(name="progress", strict=true, requirements="true|false", default="false",
     *     description="Show progress for last open survey")
     * @Rest\QueryParam(name="totalProgress", strict=true, requirements="true|false", default="false",
     *     description="Show total progress (documentary structure + all physical library associated) for last open survey")
     * @Rest\View
     * @param string $filters Words list to filter result.
     * @param string|null $establishmentId All documentary structures are linked with this establishment.
     * @param string $progress Show progress for last open survey.
     * @param string $totalProgress Show total progress for last open survey.
     * @return View Array with all documentary structures.
     */
    public function listAction(string $filters, ?string $establishmentId, string $progress, string $totalProgress): View
    {
        try
        {
            $repo = $this->managerRegistry->getRepository(DocumentaryStructures::class);
            $docStructArray = array();

            $isAnonymous = $this->isAnonymousUser();
            $establishment = null;
            $progress = StringTools::stringToBool($progress);
            $totalProgress = StringTools::stringToBool($totalProgress);

            $isDISTRD = $this->checkRightsBool([Role::ADMIN, Role::ADMIN_RO]);
            $isAdmin = $this->checkRightsBool(
                [Role::ADMIN, Role::ADMIN_RO, Role::SURVEY_ADMIN, Role::VALID_SURVEY_RESP, Role::USER],
                    null, null, null, false);

            if ($establishmentId)
            {
                $establishment = $this->getEstablishmentById($establishmentId);
                $criteria['establishment'] = $establishment;
            }

            if ($isDISTRD || ($isAdmin && $establishmentId) || $isAnonymous)
            {
                if ($filters)
                {
                    $filtersArray = StringTools::strToSearchKeywords($filters);
                    $docStructArray = $repo->search($filtersArray, $establishment);
                }
                else
                {
                    $criteria = array();
                    if ($establishment)
                    {
                        $criteria['establishment'] = $establishment;
                    }
                    $docStructArray = $repo->findBy($criteria, array('active' => 'DESC', 'useName' => 'ASC'));
                }
            }
            else
            {
                $roles = [Role::USER, Role::VALID_SURVEY_RESP, Role::SURVEY_ADMIN];
                $docStructFilter = $this->getDocStructUser($roles);
                if ($docStructFilter)
                {
                    $docStructArray = $repo->search(StringTools::strToSearchKeywords($filters),
                        $establishment, $docStructFilter);
                }
            }

            $docStructArray = $this->getFormattedSerialDocStructForResponse(
                $docStructArray, $isAnonymous, true, $progress, $totalProgress);

            if (count($docStructArray) === 0)
            {
                return $this->createView('No documentary structure found.', Response::HTTP_NOT_FOUND);
            }

            return $this->createView($docStructArray, Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Update a documentary structure.
     * @SWG\Response(
     *     response="200",
     *     description="Update a documentary structure selected by id.",
     *     @SWG\Items(allOf={
     *        @SWG\Schema(ref=@Model(type=DocumentaryStructures::class))},
     *        @SWG\Property(property="establishmentId", type="integer")))
     * )
     * @SWG\Response(response="404", description="Data missing in database")
     * @SWG\Response(response="400", description="Error to update documentary structure. Body not valid.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="id", type="integer", in="path", description="Documentary structure id to update.")
     * @SWG\Parameter(name="body", in="body", description="Documentary structure informations.",
     *     @SWG\Schema(type="object",
     *     @SWG\Property(property="officialName", type="string"),
     *     @SWG\Property(property="useName", type="string"),
     *     @SWG\Property(property="acronym", type="string"),
     *     @SWG\Property(property="active", type="boolean"),
     *     @SWG\Property(property="address", type="string"),
     *     @SWG\Property(property="city", type="string"),
     *     @SWG\Property(property="postalCode", type="string"),
     *     @SWG\Property(property="website", type="string"),
     *     @SWG\Property(property="instruction", type="string"),
     *     @SWG\Property(property="establishmentId", type="integer"),
     *     @SWG\Property(property="departmentId", type="integer")
     *     ))
     * @Rest\Put(
     *     path="/documentary-structures/{id}",
     *     name="app_documentary_structures_update",
     *     requirements={"id"="\d+"}
     * )
     * @Rest\RequestParam(name="establishmentId", requirements="[0-9]*", nullable=false)
     * @Rest\RequestParam(name="departmentId", requirements="[0-9]*", nullable=false)
     * @ParamConverter("newDocStruct", converter="fos_rest.request_body")
     * @Rest\View
     * @param DocumentaryStructures $newDocStruct New documentary structure information.
     * @param int $id Documentary structure id to update.
     * @param int $establishmentId New establishment type id.
     * @param int $departmentId
     * @param ConstraintViolationListInterface $validationErrors Assert violation list.
     * @return View Establishment has just been updated.
     */
    public function updateAction(DocumentaryStructures $newDocStruct, int $id, int $establishmentId, int $departmentId,
                                 ConstraintViolationListInterface $validationErrors) : View
    {
        try
        {
            if (count($validationErrors) > 0)
            {
                return $this->createView($validationErrors, Response::HTTP_BAD_REQUEST, true);
            }

            $em = $this->managerRegistry->getManager();

            $existingDocStruct = $this->getDocStructById($id);

            // Check rights part : if user is not DISTRD, they can't modify use name and official name.
            $distrdOk = $this->checkRightsBool([Role::ADMIN]);
            $surveyAdminOk = false;

            if (!$distrdOk)
            {
                $surveyAdminOk = $this->checkRightsBool([Role::SURVEY_ADMIN, Role::VALID_SURVEY_RESP],
                    $existingDocStruct);

                if ($surveyAdminOk)
                {
                    $newDocStruct->setOfficialName($existingDocStruct->getOfficialName());
                    $newDocStruct->setActive($existingDocStruct->getActive());
                }
            }
            if (!$distrdOk && !$surveyAdminOk)
            {
                return $this->createView(
                    self::FORBIDDEN_ERROR, Response::HTTP_FORBIDDEN, true);
            }

            $establishment = $this->getEstablishmentById($establishmentId);
            $newDepartment = $existingDocStruct->getDepartment();

            $existingDocStructDepartmentId = $newDepartment->getId();
            if ($existingDocStructDepartmentId != $departmentId)
            {
                $newDepartment = $this->getDepartmentById($departmentId);
            }

            $existingDocStruct->update($newDocStruct, $establishment, $newDepartment);
            $em->flush();

            $this->updateActiveHistoryForLastSurvey(
                DocumentaryStructures::class, $existingDocStruct, $existingDocStruct->getActive());
            $this->updateDocStructLinkHistoryForLastSurvey(
                $existingDocStruct, $existingDocStruct->getEstablishment()->getId());

            return $this->createView(
                $this->getFormattedSerialDocStructForResponse(
                    [$existingDocStruct]), Response::HTTP_OK, true);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }


    /**
     * Create new documentary structure.
     * @SWG\Response(
     *     response="201",
     *     description="Create a documentary structure.",
     *     @SWG\Items(allOf={
     *        @SWG\Schema(ref=@Model(type=DocumentaryStructures::class))},
     *        @SWG\Property(property="establishmentId", type="integer")))
     * )
     * @SWG\Response(response="400", description="Bad request. Body not valid.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="body", in="body", description="Documentary structure informations.",
     *     @SWG\Schema(type="object",
     *     @SWG\Property(property="officialName", type="string"),
     *     @SWG\Property(property="useName", type="string"),
     *     @SWG\Property(property="acronym", type="string"),
     *     @SWG\Property(property="active", type="boolean"),
     *     @SWG\Property(property="address", type="string"),
     *     @SWG\Property(property="city", type="string"),
     *     @SWG\Property(property="postalCode", type="string"),
     *     @SWG\Property(property="website", type="string"),
     *     @SWG\Property(property="instruction", type="string"),
     *     @SWG\Property(property="establishmentId", type="integer"),
     *     @SWG\Property(property="departmentId", type="integer")
     *     ))
     * @Rest\Post(path="/documentary-structures", name="app_documentary_structure_create")
     * @Rest\RequestParam(name="establishmentId", requirements="[0-9]*", nullable=false)
     * @Rest\RequestParam(name="departmentId", requirements="[0-9]*", nullable=false)
     * @Rest\View
     * @ParamConverter("docStruct", converter="fos_rest.request_body")
     * @param DocumentaryStructures $docStruct Documentary structure set in body request.
     * @param int $establishmentId Id of establishment assigned to documentary structure.
     * @param int $departmentId
     * @param ConstraintViolationListInterface $validationErrors Assert violation list.
     * @return View Establishment has just been created.
     */
    public function createAction(DocumentaryStructures $docStruct, int $establishmentId, int $departmentId,
                                 ConstraintViolationListInterface $validationErrors) : View
    {
        try
        {
            $this->checkRights([Role::ADMIN]);

            if (count($validationErrors) > 0)
            {
                return $this->createView($validationErrors, Response::HTTP_BAD_REQUEST, true);
            }

            $establishment = $this->getEstablishmentById($establishmentId);
            $department = $this->getDepartmentById($departmentId);

            $docStruct->setEstablishment($establishment);
            $docStruct->setDepartment($department);

            $em = $this->managerRegistry->getManager();

            $em->persist($docStruct);
            $em->flush();

            $this->updateActiveHistoryForLastSurvey(
                DocumentaryStructures::class, $docStruct, $docStruct->getActive(), true);
            $this->updateDocStructLinkHistoryForLastSurvey(
                $docStruct, $docStruct->getEstablishment()->getId());

            return $this->createView($this->getFormattedSerialDocStructForResponse([$docStruct]),
                Response::HTTP_CREATED, true);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }


    /**
     * Format an array of documentary structure for response.
     * @param array $docStructArray Documentary structure doctrine entity or array representation.
     * @param bool $isAnonymous True the response is for anonymous user.
     * @param bool $arrayResponse True for get response like array of documentary structure.
     * @param bool $progress Show progress of last survey for this documentary structure in response.
     * @param bool $totalProgress Show total progress of last survey for this documentary structure in response.
     * @return array|null Null if result $docStructArray is null, or array with all documentary structure formatted for
     *                    response.
     * @throws Exception 404 : Error to find open survey.
     */
    private function getFormattedSerialDocStructForResponse(array $docStructArray, bool $isAnonymous = false,
                                                            bool $arrayResponse = false, bool $progress = false,
                                                            bool $totalProgress = false): ?array
    {
        if (!count($docStructArray) === 0)
        {
            return null;
        }

        if ($progress || $totalProgress)
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
        }

        $result = array();

        foreach ($docStructArray as $docStruct)
        {
            if ($isAnonymous) {
                if ($docStruct instanceof DocumentaryStructures) {
                    array_push($result, $this->getFormattedByDocStructEntityForAnonymousUser($docStruct));
                } else {
                    array_push($result, $this->getFormattedByArrayForAnonymousUser($docStruct));
                }
            }
            else
            {
                if ($docStruct instanceof DocumentaryStructures)
                {
                    array_push($result, $this->getFormattedDocStructByEntityForResponse($docStruct));
                }
                else
                {
                    array_push($result, $this->getFormattedDocStructByArrayForResponse($docStruct));
                }
            }
        }

        if ($progress)
        {
            $this->getProgress($result, $lastSurvey);
        }

        if ($totalProgress)
        {
            $this->getTotalProgress($result, $lastSurvey);
        }

        if (count($result) === 1 && !$arrayResponse)
        {
            return $result[0];
        }

        return $result;
    }

    /**
     * Get formatted documentary structure for response for anonymous user.
     * @param DocumentaryStructures $docStruct Documentary structure doctrine entity.
     * @return array Formatted documentary structure in array format.
     */
    private function getFormattedByDocStructEntityForAnonymousUser(DocumentaryStructures $docStruct): array
    {
        return array(
            'id' => $docStruct->getId(),
            'useName' => $docStruct->getUseName());
    }

    /**
     * Get formatted documentary structure for response for anonymous user.
     * @param array $docStruct Documentary structure array representation. (no doctrine entity)
     * @return array Formatted documentary structure in array format.
     */
    private function getFormattedByArrayForAnonymousUser(array $docStruct): array
    {
        return array(
            'id' => $docStruct['id'],
            'useName' => $docStruct['useName']);
    }

    /**
     * Format documentary structure for response.
     * @param DocumentaryStructures $docStruct Documentary structure doctrine entity.
     * @return array Formatted documentary structure in array format.
     */
    public static function getFormattedDocStructByEntityForResponse(DocumentaryStructures $docStruct): array
    {
        return array('id' => $docStruct->getId(),
            'officialName' => $docStruct->getOfficialName(),
            'useName' => $docStruct->getUseName(),
            'acronym' => $docStruct->getAcronym(),
            'address' => $docStruct->getAddress(),
            'postalCode' => $docStruct->getPostalCode(),
            'city' => $docStruct->getCity(),
            'website' => $docStruct->getWebsite(),
            'active' => $docStruct->getActive(),
            'instruction' => $docStruct->getInstruction(),
            'establishmentId' => $docStruct->getEstablishment()->getId(),
            'department' => $docStruct->getDepartment());
    }

    /**
     * Format documentary structure for response.
     * @param array $docStruct Documentary structure array representation. (no doctrine entity)
     * @return array Formatted documentary structure in array format.
     */
    public static function getFormattedDocStructByArrayForResponse(array $docStruct): array
    {
        return array('id' => $docStruct['id'],
            'officialName' => $docStruct['officialName'],
            'useName' => $docStruct['useName'],
            'acronym' => $docStruct['acronym'],
            'address' => $docStruct['address'],
            'postalCode' => $docStruct['postalCode'],
            'city' => $docStruct['city'],
            'website' => $docStruct['website'],
            'active' => $docStruct['active'],
            'instruction' => $docStruct['instruction'],
            'establishmentId' => $docStruct['establishment']['id'],
            'department' => $docStruct['department']);
    }

    /**
     * Add progress for each documentary structure in array in parameter.
     * @param array $docStructArray Array that contains all documentary structures to format.
     * @param Surveys $survey Progress for this survey.
     */
    private function getProgress(array &$docStructArray, Surveys $survey)
    {
        $progress = $this->managerRegistry->getRepository(DocumentaryStructures::class)
            ->getNbrResponse($docStructArray, $survey);

        foreach ($docStructArray as &$docStruct)
        {
            if (!$progress)
            {
                $docStruct['progress'] = 0;
            }
            else if (array_key_exists($docStruct['id'], $progress))
            {
                $docStruct['progress'] = $progress[$docStruct['id']];
            }
            else
            {
                $docStruct['progress'] = 0;
            }
        }
    }


    /**
     * Add total progress for each documentary structure in array in parameter.
     * @param array $docStructArray Array that contains all documentary structures.
     * @param Surveys $survey Total progress for this survey
     * @throws Exception 404 : Error to find open survey.
     */
    private function getTotalProgress(array &$docStructArray, Surveys $survey)
    {
        $totalProgress = $this->managerRegistry->getRepository(DocumentaryStructures::class)
            ->getTotalProgress($docStructArray, $survey);

        foreach ($docStructArray as &$docStruct)
        {
            if (!$totalProgress)
            {
                $docStruct['totalProgress'] = 0;
            }
            else if (array_key_exists($docStruct['id'], $totalProgress))
            {
                $docStruct['totalProgress'] = $totalProgress[$docStruct['id']];
            }
            else
            {
                $docStruct['totalProgress'] = 0;
            }
        }
    }
}
