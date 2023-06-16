<?php


namespace App\Controller;

use App\Common\Enum\Role;
use App\Common\Traits\DataTypesTrait;
use App\Common\Traits\OperationsTrait;
use App\Entity\Operations;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use App\Controller\AbstractController\ESGBUController;

/**
 * Class OperationsController
 * @package App\Controller
 * @SWG\Tag(name="Operations")
 */
class OperationsController extends ESGBUController
{

    use OperationsTrait,
        DataTypesTrait;

    /**
     * Show all operations information.
     * @SWG\Response(
     *     response="200",
     *     description="Array with all operations information.",
     *     @SWG\Schema(type="array",
     *     @SWG\Items(allOf={
     *          @SWG\Schema(ref=@Model(type=Operations::class))},
     *          @SWG\Property(property="dataTypeId", type="integer")))
     * )
     * @SWG\Response(
     *     response="404",
     *     description="No operation information found.",
     * )
     * @Rest\Get(
     *      path = "/operations",
     *      name = "app_operations_list"
     * )
     * @Rest\View
     * @return View Array with all numbers information.
     */
    public function listAction() : View
    {
        try
        {
            $operations = $this->getAllOperation();

            foreach ($operations as &$operation)
            {
                $operation = $this->formatOperationForResponse($operation);
            }
            return $this->createView($operations, Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Create a new operation information.
     *
     * @SWG\Response(
     *     response="200",
     *     description="Return operation information created",
     *     @SWG\Schema(type="object", allOf={
     *          @SWG\Schema(ref=@Model(type=Operations::class))},
     *              @SWG\Property(property="dataTypeId", type="integer"))
     * )
     * @SWG\Response(response="404", description="No data type found.")
     * @SWG\Response(response="400", description="Bad value. Formula error.")
     * @SWG\Response(response="409", description="Data type already have operations information.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="body", in="body", description="Data type informations.
     - Formula can be an arythmetic operation with data type code ex : (code1+code2)/(code2-code4)*code5
     - Fonctions : sum(code1, code2, code3, ...), avg(code1, code2, code3, ...)",
     *     @SWG\Schema(type="object", allOf={
     *          @SWG\Schema(ref=@Model(type=Operations::class))},
     *              @SWG\Property(property="dataTypeId", type="integer"))
     * )
     * @Rest\Post(path="/operations", name="app_operations_create")
     * @Rest\RequestParam(name="dataTypeId", requirements="[0-9]*", nullable=false)
     * @Rest\RequestParam(name="formula", requirements="([0-9A-Za-z ,_\.]|\+|\*|\-|\/|\(|\))+[^.,]$", nullable=false)
     * @Rest\View
     * @param string $formula
     * @param int $dataTypeId Data type id linked with this information.
     * @return View Number information has just been created.
     */
    public function createAction(string $formula, int $dataTypeId) : View
    {
        try
        {
            $this->checkRights([Role::ADMIN]);
            $dataType = $this->getDataTypeById($dataTypeId);
            $this->checkAlreadyExistsOperation($dataTypeId);
            $this->checkFormula($formula);

            $operation = new Operations($formula, $dataType);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($operation);
        $em->flush();

        return $this->createView($this->formatOperationForResponse($operation), Response::HTTP_OK, true);

    }

    /**
     * Update an operation information.
     *
     * @SWG\Response(
     *     response="200",
     *     description="Return operation information updated",
     *     @SWG\Schema(type="object", allOf={
     *          @SWG\Schema(ref=@Model(type=Operations::class))},
     *              @SWG\Property(property="dataTypeId", type="integer"))
     * )
     * @SWG\Response(response="404", description="No data type found.")
     * @SWG\Response(response="400", description="Bad value. Formula error.")
     * @SWG\Response(response="409", description="Data type already have operations information.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="body", in="body", description="Data type informations.
    - Formula can be an arythmetic operation with data type code ex : (code1+code2)/(code2-code4)*code5
    - Fonctions : sum(code1, code2, code3, ...), avg(code1, code2, code3, ...)",
     *     @SWG\Schema(type="object", allOf={
     *          @SWG\Schema(ref=@Model(type=Operations::class))},
     *              @SWG\Property(property="dataTypeId", type="integer"))
     * )
     * @Rest\Put(path="/operations/{id}", name="app_operations_update", requirements={"id"="\d+"})
     * @Rest\RequestParam(name="dataTypeId", requirements="[0-9]*", nullable=false)
     * @Rest\RequestParam(name="formula", requirements="([0-9A-Za-z ,_\.]|\+|\*|\-|\/|\(|\))+[^.,]$", nullable=false)
     * @Rest\View
     * @param int $id Id of data type which identify operation information.
     * @param string $formula
     * @param int $dataTypeId Data type id linked with this information.
     * @return View Number information has just been updated.
     */
    public function updateAction(string $formula, int $id, int $dataTypeId) : View
    {
        try
        {
            $this->checkRights([Role::ADMIN]);

            $dataType = $this->getDataTypeById($id);
            $existingOperation = $this->getOperationByDataType($dataType);

            $newDataType = ($dataTypeId) ? $this->getDataTypeById($dataTypeId) : $existingOperation->getDataType();

            if ($dataTypeId && $dataTypeId != $id)
            {
                $this->checkAlreadyExistsOperation($dataTypeId);
            }

            $this->checkFormula($formula);

            $existingOperation->update($formula, $newDataType);

            $em = $this->getDoctrine()->getManager();
            $em->flush();

            return $this->createView($this->formatOperationForResponse($existingOperation), Response::HTTP_OK, true);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }

    /**
     * Format operation for response.
     * @param Operations|null $operation Operation doctrine entity.
     * @return array|null Array with all operation information for response.
     */
    private function formatOperationForResponse(?Operations $operation): ?array
    {
        if (!$operation)
        {
            return null;
        }

        return array(
            'dataTypeId' => $operation->getDataType()->getId(),
            'regex' => $operation->getFormula());
    }
}
