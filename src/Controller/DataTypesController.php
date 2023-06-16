<?php

namespace App\Controller;

use App\Common\Interfaces\ITypes;
use App\Common\Enum\Role;
use App\Common\Traits\DataTypesTrait;
use App\Common\Traits\GroupsTrait;
use App\Entity\DataTypes;
use App\Entity\Types;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Response;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use App\Controller\AbstractController\ESGBUController;


/**
 * Class DataTypesController
 * @package App\Controller
 * @SWG\Tag(name="Data types")
 */
class DataTypesController extends ESGBUController implements ITypes
{

    use GroupsTrait,
        DataTypesTrait;

    private const TABLE_NAME = 'data_types';

    /**
     * Show all data types.
     *
     * @SWG\Response(
     *     response="200",
     *     description="Return data type select by group id.",
     *     @SWG\Schema(type="array",
     *         @SWG\Items(allOf={
     *          @SWG\Schema(ref=@Model(type=DataTypes::class))},
     *          @SWG\Property(property="groupId", type="integer"),
     *          @SWG\Property(property="name", type="string"),
     *          @SWG\Property(property="instruction", type="string"),
     *          @SWG\Property(property="measureUnit", type="string"),
     *          @SWG\Property(property="definition", type="string"),
     *          @SWG\Property(property="date", type="string"),
     *          @SWG\Property(property="type", ref=@Model(type=Types::class))))
     * )
     * @SWG\Response(response="404", description="No group or data type found.")
     * @SWG\Parameter(name="groupId",type="integer", in="query", description="Group id to get all data type linked.")
     * @SWG\Parameter(name="lang",type="string", in="query", description="Language code.")
     * @Rest\Get(
     *      path = "/data-types",
     *      name = "app_data_types_list"
     * )
     * @Rest\QueryParam(name="groupId", requirements="[0-9]*", nullable=true)
     * @Rest\QueryParam(name="lang", requirements="[a-z]{2}", default=ESGBUController::DEFAULT_LANG)
     * @Rest\View
     * @param string|null $groupId Group id to filter result.
     * @param string $lang Language code.
     * @return View Array with all data types.
     */
    public function listAction(?string $groupId, string $lang): View
    {
        $doctrine = $this->managerRegistry;
        try
        {
            if ($groupId)
            {
                $group = $this->getGroupById($groupId);
                $dataTypes = $doctrine->getRepository(DataTypes::class)
                    ->findBy(array('group' => $group), array('groupOrder' => 'ASC'));
            }
            else
            {
                $dataTypes = $this->getAllDataTypeOrdered();
            }
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }

        if (count($dataTypes) === 0)
        {
            return $this->createView("No data types found.", Response::HTTP_NOT_FOUND);
        }

        $formattedDataTypes = array();
        foreach ($dataTypes as $dataType)
        {
            array_push($formattedDataTypes, $this->getFormattedDataTypeForResponse($dataType, $lang));
        }
        return $this->createView($formattedDataTypes, Response::HTTP_OK);
    }

    /**
     * Show all data types.
     *
     * @SWG\Response(
     *     response="200",
     *     description="Return data type select by group id.",
     *     @SWG\Schema(type="array",
     *         @SWG\Items(allOf={
     *          @SWG\Schema(ref=@Model(type=DataTypes::class))},
     *          @SWG\Property(property="groupId", type="integer"),
     *          @SWG\Property(property="name", type="string"),
     *          @SWG\Property(property="instruction", type="string"),
     *          @SWG\Property(property="measureUnit", type="string"),
     *          @SWG\Property(property="definition", type="string"),
     *          @SWG\Property(property="date", type="string"),
     *          @SWG\Property(property="type", ref=@Model(type=Types::class))))
     * )
     * @SWG\Response(response="404", description="No group or data type found.")
     * @SWG\Parameter(name="lang",type="string", in="query", description="Language code.")
     * @Rest\Get(
     *      path = "/public/data-types",
     *      name = "app_public_data_types_list"
     * )
     * @Rest\QueryParam(name="lang", requirements="[a-z]{2}", default=ESGBUController::DEFAULT_LANG)
     * @Rest\View
     * @param string $lang Language code.
     * @return View
     */
    public function publicListAction(string $lang): View
    {
        try
        {
            $dataTypes = $this->getAllDataTypeOrdered(true);
            foreach ($dataTypes as &$dataType)
            {
                $dataType = $this->getFormattedDataTypeForPublicResponse($dataType, $lang);
            }
            return $this->createView($dataTypes, Response::HTTP_OK);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Show data types by id.
     *
     * @SWG\Response(
     *     response="200",
     *     description="Return data type select by id.",
     *     @SWG\Schema(type="object", allOf={
     *          @SWG\Schema(ref=@Model(type=DataTypes::class))},
     *          @SWG\Property(property="groupId", type="integer"),
     *          @SWG\Property(property="name", type="string"),
     *          @SWG\Property(property="instruction", type="string"),
     *          @SWG\Property(property="measureUnit", type="string"),
     *          @SWG\Property(property="definition", type="string"),
     *          @SWG\Property(property="date", type="string"),
     *          @SWG\Property(property="type", ref=@Model(type=Types::class))))
     * )
     * @SWG\Response(response="404", description="No data type found.")
     * @SWG\Parameter(name="lang",type="string", in="query", description="Language code.")
     * @Rest\Get(
     *      path = "/data-types/{id}",
     *      name = "app_data_types_show",
     *     requirements = {"id"="\d+"}
     * )
     * @Rest\QueryParam(name="lang", requirements="[a-z]{2}", default=ESGBUController::DEFAULT_LANG)
     * @Rest\View
     * @param int $id Data type id.
     * @param string $lang Language code.
     * @return View Array with all data types.
     */
    public function showAction(int $id, string $lang): View
    {
        try
        {
            $dataType = $this->getDataTypeById($id);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
        return $this->createView($this->getFormattedDataTypeForResponse($dataType, $lang), Response::HTTP_OK);
    }

    /**
     * Create a new data type.
     *
     * @SWG\Response(
     *     response="200",
     *     description="Return data type created.",
     *         @SWG\Schema(type="object", allOf={
     *          @SWG\Schema(ref=@Model(type=DataTypes::class))},
     *          @SWG\Property(property="groupId", type="integer"),
     *          @SWG\Property(property="name", type="string"),
     *          @SWG\Property(property="instruction", type="string"),
     *          @SWG\Property(property="measureUnit", type="string"),
     *          @SWG\Property(property="definition", type="string"),
     *          @SWG\Property(property="date", type="string"),
     *          @SWG\Property(property="type", ref=@Model(type=Types::class))))
     * )
     * @SWG\Response(response="404", description="No group, type or lang found.")
     * @SWG\Response(response="400", description="Default language is required.")
     * @SWG\Response(response="409", description="Data type already exists with this code.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="body", in="body", description="Data type informations.",
     *     @SWG\Schema(type="object",
     *              @SWG\Property(property="code", type="string"),
     *              @SWG\Property(property="codeEu", type="string"),
     *              @SWG\Property(property="groupId", type="integer"),
     *              @SWG\Property(property="typeId", type="integer"),
     *              @SWG\Property(property="groupOrder", type="integer"),
     *              @SWG\Property(property="administrator", type="boolean"),
     *              @SWG\Property(property="private", type="boolean"),
     *              @SWG\Property(property="facet", type="boolean"),
     *              @SWG\Property(property="simplifiedFacet", type="boolean"),
     *              @SWG\Property(property="names", type="array",
     *                  @SWG\Items(type="object",
     *                      @SWG\Property(property="lang", type="string"),
     *                      @SWG\Property(property="value", type="string"))),
     *     @SWG\Property(property="measureUnits", type="array",
     *                  @SWG\Items(type="object",
     *                      @SWG\Property(property="lang", type="string"),
     *                      @SWG\Property(property="value", type="string"))),
     *     @SWG\Property(property="instructions", type="array",
     *                  @SWG\Items(type="object",
     *                      @SWG\Property(property="lang", type="string"),
     *                      @SWG\Property(property="value", type="string"))),
     *     @SWG\Property(property="definitions", type="array",
     *                  @SWG\Items(type="object",
     *                      @SWG\Property(property="lang", type="string"),
     *                      @SWG\Property(property="value", type="string"))),
     *     @SWG\Property(property="dates", type="array",
     *                  @SWG\Items(type="object",
     *                      @SWG\Property(property="lang", type="string"),
     *                      @SWG\Property(property="value", type="string"))))
     * )
     * @SWG\Parameter(name="lang",type="string", in="query", description="Language code.")
     * @Rest\Post(path="/data-types", name="app_data_types_create")
     * @Rest\RequestParam(name="groupId", requirements="[0-9]*", nullable=false)
     * @Rest\RequestParam(name="typeId", requirements="[0-9]*", nullable=false)
     * @Rest\RequestParam(name="names", nullable=false)
     * @Rest\RequestParam(name="definitions", nullable=true)
     * @Rest\RequestParam(name="instructions", nullable=true)
     * @Rest\RequestParam(name="measureUnits", nullable=true)
     * @Rest\RequestParam(name="dates", nullable=true)
     * @Rest\QueryParam(name="lang", requirements="[a-z]{2}", default=ESGBUController::DEFAULT_LANG)
     * @Rest\View
     * @ParamConverter("dataType", converter="fos_rest.request_body")
     * @param DataTypes $dataType Data type in body.
     * @param int $groupId Id of group which contains this type.
     * @param int $typeId Type id of value.
     * @param array $names List of names in each language.
     * @param array|null $definitions List of definitions in each language.
     * @param array|null $instructions List of instructions in each language.
     * @param array|null $measureUnits List of measure units in each language.
     * @param array|null $dates List of dates in each language.
     * @param string $lang Language code.
     * @param ConstraintViolationListInterface $validationErrors Assert violation list.
     * @return View Data type has just been created.
     */
    public function createAction(DataTypes $dataType, int $groupId, int $typeId, array $names,
                                 ?array $definitions, ?array $instructions, ?array $measureUnits, ?array $dates,
                                 string $lang, ConstraintViolationListInterface $validationErrors) : View
    {
        try
        {
            $this->checkRights([Role::ADMIN]);

            if (count($validationErrors) > 0)
            {
                return $this->createView($validationErrors, Response::HTTP_BAD_REQUEST, true);
            }

            $em = $this->managerRegistry->getManager();

            $this->checkIfAlreadyExistsCode($dataType->getCode());

            $group = $this->getGroupById($groupId);
            $type = $this->getTypeById($typeId);

            $nameContent = $this->addTranslation($names, self::TABLE_NAME);
            $definitionContent = $this->addTranslation($definitions, self::TABLE_NAME);
            $instructionContent = $this->addTranslation($instructions, self::TABLE_NAME);
            $measureUnitContent = $this->addTranslation($measureUnits, self::TABLE_NAME);
            $dateContent = $this->addTranslation($dates, self::TABLE_NAME);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }

        $dataType->setName($nameContent);
        $dataType->setDefinition($definitionContent);
        $dataType->setInstruction($instructionContent);
        $dataType->setMeasureUnit($measureUnitContent);
        $dataType->setDate($dateContent);

        $dataType->setGroup($group);
        $dataType->setType($type);

        $this->updateGroupOrderForEachDataTypeOfGroup($dataType);

        $em->persist($dataType);
        $em->flush();

        return $this->createView($this->getFormattedDataTypeForResponse($dataType, $lang),
                            Response::HTTP_CREATED, true);
    }

    /**
     * Update a new data type.
     *
     * @SWG\Response(
     *     response="200",
     *     description="Return data type updated.",
     *         @SWG\Schema(type="object", allOf={
     *          @SWG\Schema(ref=@Model(type=DataTypes::class))},
     *          @SWG\Property(property="groupId", type="integer"),
     *          @SWG\Property(property="name", type="string"),
     *          @SWG\Property(property="instruction", type="string"),
     *          @SWG\Property(property="measureUnit", type="string"),
     *          @SWG\Property(property="definition", type="string"),
     *          @SWG\Property(property="date", type="string"),
     *          @SWG\Property(property="type", ref=@Model(type=Types::class))))
     * )
     * @SWG\Response(response="404", description="No group, type or lang found.")
     * @SWG\Response(response="400", description="Default language is required.")
     * @SWG\Response(response="409", description="Data type already exists with this code.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="body", in="body", description="Data type informations.",
     *     @SWG\Schema(type="object",
     *              @SWG\Property(property="code", type="string"),
     *              @SWG\Property(property="codeEu", type="string"),
     *              @SWG\Property(property="groupId", type="integer"),
     *              @SWG\Property(property="typeId", type="integer"),
     *              @SWG\Property(property="groupOrder", type="integer"),
     *              @SWG\Property(property="administrator", type="boolean"),
     *              @SWG\Property(property="private", type="boolean"),
     *              @SWG\Property(property="facet", type="boolean"),
     *              @SWG\Property(property="simplifiedFacet", type="boolean"),
     *              @SWG\Property(property="names", type="array",
     *                  @SWG\Items(type="object",
     *                      @SWG\Property(property="lang", type="string"),
     *                      @SWG\Property(property="value", type="string"))),
     *     @SWG\Property(property="measureUnits", type="array",
     *                  @SWG\Items(type="object",
     *                      @SWG\Property(property="lang", type="string"),
     *                      @SWG\Property(property="value", type="string"))),
     *     @SWG\Property(property="instructions", type="array",
     *                  @SWG\Items(type="object",
     *                      @SWG\Property(property="lang", type="string"),
     *                      @SWG\Property(property="value", type="string"))),
     *     @SWG\Property(property="definitions", type="array",
     *                  @SWG\Items(type="object",
     *                      @SWG\Property(property="lang", type="string"),
     *                      @SWG\Property(property="value", type="string"))),
     *     @SWG\Property(property="dates", type="array",
     *                  @SWG\Items(type="object",
     *                      @SWG\Property(property="lang", type="string"),
     *                      @SWG\Property(property="value", type="string"))))
     * )
     * @SWG\Parameter(name="lang",type="string", in="query", description="Language code.")
     * @Rest\Put(path="/data-types/{id}", name="app_data-types_update", requirements={"id"="\d+"})
     * @Rest\RequestParam(name="groupId", requirements="[0-9]*", nullable=false)
     * @Rest\RequestParam(name="typeId", requirements="[0-9]*", nullable=false)
     * @Rest\RequestParam(name="names", nullable=false)
     * @Rest\RequestParam(name="definitions", nullable=true)
     * @Rest\RequestParam(name="instructions", nullable=true)
     * @Rest\RequestParam(name="measureUnits", nullable=true)
     * @Rest\RequestParam(name="dates", nullable=true)
     * @Rest\QueryParam(name="lang", requirements="[a-z]{2}", default=ESGBUController::DEFAULT_LANG)
     * @Rest\View
     * @ParamConverter("dataType", converter="fos_rest.request_body")
     * @param DataTypes $dataType Data type in body.
     * @param int $id Id of data type to update.
     * @param int $groupId Id of group which contains this type.
     * @param int $typeId Type id of value.
     * @param array $names List of names in each language.
     * @param string $lang Language code.
     * @param array|null $definitions List of definitions in each language.
     * @param array|null $instructions List of instructions in each language.
     * @param array|null $measureUnits List of measure units in each language.
     * @param array|null $dates List of dates in each language.
     * @param ConstraintViolationListInterface $validationErrors Assert violation list.
     * @return View Data type has just been created.
     */
    public function updateAction(DataTypes $dataType, int $id, int $groupId, int $typeId, array $names, string $lang,
                                 ?array $definitions, ?array $instructions, ?array $measureUnits, ?array $dates,
                                 ConstraintViolationListInterface $validationErrors) : View
    {
        try
        {
            $this->checkRights([Role::ADMIN]);

            if (count($validationErrors) > 0)
            {
                return $this->createView($validationErrors, Response::HTTP_BAD_REQUEST, true);
            }

            $existingDataType = $this->getDataTypeById($id);
            $group = $this->getGroupById($groupId);
            $type = $this->getTypeById($typeId);

            $existingDataType->update($dataType, $group, $type);
            $this->checkIfAlreadyExistsCode($existingDataType->getCode(), $id);
            $this->updateGroupOrderForEachDataTypeOfGroup($existingDataType);

            $this->updateOrCreateName($existingDataType, $names);
            $this->updateOrCreateDefinition($existingDataType, $definitions);
            $this->updateOrCreateInstruction($existingDataType, $instructions);
            $this->updateOrCreateMeasureUnit($existingDataType, $measureUnits);
            $this->updateOrCreateDate($existingDataType, $dates);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }

        $em = $this->managerRegistry->getManager();
        $em->flush();

        return $this->createView($this->getFormattedDataTypeForResponse($existingDataType, $lang),
            Response::HTTP_CREATED, true);
    }


    /**
     * @param DataTypes $dataType DataType to format for json response for public request.
     * @param string $lang Code lang to choose desired language for the response.
     * @return array Array that represents the dataType.
     */
    private function getFormattedDataTypeForPublicResponse(DataTypes $dataType, string $lang): array
    {
        $name = $this->getTranslation($lang, $dataType->getName());
        $measureUnit = $this->getTranslation($lang, $dataType->getMeasureUnit());
        $constraint = $this->getConstraint($dataType);

        return array(
            'id' => $dataType->getId(),
            'name' => $name,
            'code' => $dataType->getCode(),
            'codeEu' => $dataType->getCodeEu(),
            'groupOrder' => $dataType->getGroupOrder(),
            'measureUnit' => $measureUnit,
            'groupId' => $dataType->getGroup()->getId(),
            'type' => $dataType->getType(),
            'facet' => $dataType->getFacet(),
            'simplifiedFacet' => $dataType->getSimplifiedFacet(),
            'constraint' => $constraint
        );
    }


    /**
     * @param DataTypes|null $dataType DataType to format for json response.
     * @param string $lang Code lang to choose desired language for the response.
     * @return array|null Array that represents the dataType.
     */
    private function getFormattedDataTypeForResponse(?DataTypes $dataType, string $lang) : ?array
    {
        if (!$dataType)
        {
            return null;
        }
        $name = $this->getTranslation($lang, $dataType->getName());
        $measureUnit = $this->getTranslation($lang, $dataType->getMeasureUnit());
        $definition = $this->getTranslation($lang, $dataType->getDefinition());
        $instruction = $this->getTranslation($lang, $dataType->getInstruction());
        $date = $this->getTranslation($lang, $dataType->getDate());
        $constraint = $this->getConstraint($dataType);

        return array(
            'id' => $dataType->getId(),
            'name' => $name,
            'code' => $dataType->getCode(),
            'codeEu' => $dataType->getCodeEu(),
            'groupOrder' => $dataType->getGroupOrder(),
            'date' => $date,
            'measureUnit' => $measureUnit,
            'groupId' => $dataType->getGroup()->getId(),
            'instruction' => $instruction,
            'definition' => $definition,
            'administrator' => $dataType->getAdministrator(),
            'private' => $dataType->getPrivate(),
            'facet' => $dataType->getFacet(),
            'simplifiedFacet' => $dataType->getSimplifiedFacet(),
            'type' => $dataType->getType(),
            'constraint' => $constraint
        );
    }
}
