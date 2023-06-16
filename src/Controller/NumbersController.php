<?php


namespace App\Controller;

use App\Common\Enum\Role;
use App\Common\Traits\DataTypesTrait;
use App\Common\Traits\NumbersTrait;
use App\Entity\Numbers;
use App\Utils\StringTools;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Response;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use App\Controller\AbstractController\ESGBUController;

/**
 * Class NumbersController
 * @package App\Controller
 * @SWG\Tag(name="Numbers")
 */
class NumbersController extends ESGBUController
{

    use NumbersTrait,
        DataTypesTrait;

    /**
     * Show all numbers information.
     * @SWG\Response(
     *     response="200",
     *     description="Array with all numbers information.",
     *     @SWG\Schema(type="array",
     *     @SWG\Items(allOf={
     *          @SWG\Schema(ref=@Model(type=Numbers::class))},
     *          @SWG\Property(property="dataTypeId", type="integer")))
     * )
     * @SWG\Response(
     *     response="404",
     *     description="No number information found.",
     * )
     *  @Rest\QueryParam(name="dataTypeId",requirements="^\d+(\d+|,)*",nullable=true,
     *     description="Id can be separated by comma without space: ex: 12,34,1")
     * @Rest\Get(
     *      path = "/public/numbers",
     *      name = "app_public_numbers_list"
     * )
     * @Rest\View
     * @param string|null $dataTypeId Data type id to filter result.
     * @return View Array with all numbers information.
     */
    public function publicListAction(?string $dataTypeId) : View
    {
        try
        {
            if ($dataTypeId)
            {
                $dataTypeId = StringTools::commaSplit($dataTypeId);
                $numbers = $this->getAllNumbersByDataTypeId($dataTypeId);
            }
            else
            {
                $numbers = $this->getAllNumbers();
            }

            $formattedNumbers = array();
            foreach ($numbers as $number)
            {
                array_push($formattedNumbers, $this->formatNumberForResponse($number));
            }
            return $this->createView($formattedNumbers, Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Show all numbers information.
     * @SWG\Response(
     *     response="200",
     *     description="Array with all numbers information.",
     *     @SWG\Schema(type="array",
     *     @SWG\Items(allOf={
     *          @SWG\Schema(ref=@Model(type=Numbers::class))},
     *          @SWG\Property(property="dataTypeId", type="integer")))
     * )
     * @SWG\Response(
     *     response="404",
     *     description="No number information found.",
     * )
     * @Rest\Get(
     *      path = "/numbers",
     *      name = "app_numbers_list"
     * )
     * @Rest\View
     * @return View Array with all numbers information.
     */
    public function listAction() : View
    {
        try
        {
            $numbers = $this->getAllNumbers();

            $formattedNumbers = array();
            foreach ($numbers as $number)
            {
                array_push($formattedNumbers, $this->formatNumberForResponse($number));
            }
            return $this->createView($formattedNumbers, Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Create a new number information.
     *
     * @SWG\Response(
     *     response="200",
     *     description="Return number information created.",
     *     @SWG\Schema(type="object", allOf={
     *          @SWG\Schema(ref=@Model(type=Numbers::class))},
     *              @SWG\Property(property="dataTypeId", type="integer"))
     * )
     * @SWG\Response(response="404", description="No data type found.")
     * @SWG\Response(response="400", description="Bad value.")
     * @SWG\Response(response="409", description="Data type already have number information")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="body", in="body", description="Data type informations.",
     *     @SWG\Schema(type="object", allOf={
     *          @SWG\Schema(ref=@Model(type=Numbers::class))},
     *              @SWG\Property(property="dataTypeId", type="integer"))
     * )
     * @Rest\Post(path="/numbers", name="app_numbers_create")
     * @Rest\RequestParam(name="dataTypeId", requirements="[0-9]*", nullable=false)
     * @Rest\View
     * @ParamConverter("number", converter="fos_rest.request_body")
     * @param Numbers $number Number information.
     * @param int $dataTypeId Data type id linked with this information.
     * @param ConstraintViolationListInterface $validationErrors Assert violation list.
     * @return View Number information has just been created.
     */
    public function createAction(Numbers $number, int $dataTypeId, ConstraintViolationListInterface $validationErrors)
    : View
    {
        try
        {
            $this->checkRights([Role::ADMIN]);
            $this->checkIfValidNumber($number, $validationErrors);
            $this->checkIfAlreadyHasNumberInfo($dataTypeId);

            $dataType = $this->getDataTypeById($dataTypeId);
            $number->setDataType($dataType);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }

        $em = $this->managerRegistry->getManager();
        $em->persist($number);
        $em->flush();

        return $this->createView($this->formatNumberForResponse($number), Response::HTTP_CREATED, true);
    }

    /**
     * Update a number information.
     *
     * @SWG\Response(
     *     response="200",
     *     description="Return number information updated.",
     *     @SWG\Schema(type="object", allOf={
     *          @SWG\Schema(ref=@Model(type=Numbers::class))},
     *              @SWG\Property(property="dataTypeId", type="integer"))
     * )
     * @SWG\Response(response="404", description="No data type found.")
     * @SWG\Response(response="400", description="Bad value.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Response(response="409", description="Data type already have number information.")
     * @SWG\Parameter(name="body", in="body", description="Data type informations.",
     *     @SWG\Schema(type="object", allOf={
     *          @SWG\Schema(ref=@Model(type=Numbers::class))},
     *              @SWG\Property(property="dataTypeId", type="integer"))
     * )
     * @Rest\Put(path="/numbers/{id}", name="app_numbers_update", requirements={"id"="\d+"})
     * @Rest\RequestParam(name="dataTypeId", requirements="[0-9]*", nullable=true)
     * @Rest\View
     * @ParamConverter("number", converter="fos_rest.request_body")
     * @param Numbers $number Number information.
     * @param int $id Id of data type which identify number information.
     * @param int|null $dataTypeId Data type id linked with this information.
     * @param ConstraintViolationListInterface $validationErrors Assert violation list.
     * @return View Number information has just been updated.
     */
    public function updateAction(Numbers $number, int $id, ?int $dataTypeId,
                                 ConstraintViolationListInterface $validationErrors) : View
    {
        try
        {
            $this->checkRights([Role::ADMIN]);
            $this->checkIfValidNumber($number, $validationErrors);

            $dataType = $this->getDataTypeById($id);
            $existingNumber = $this->getNumberByDataType($dataType);

            $newDataType = ($dataTypeId) ? $this->getDataTypeById($dataTypeId) : $existingNumber->getDataType();

            if ($dataTypeId && $dataTypeId != $id)
            {
                $this->checkIfAlreadyHasNumberInfo($dataTypeId);
            }

            $existingNumber->update($number, $newDataType);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }

        $em = $this->getDoctrine()->getManager();
        $em->flush();
        return $this->createView($this->formatNumberForResponse($existingNumber), Response::HTTP_CREATED, true);
    }


    /**
     * Format number for response.
     * @param Numbers|null $number Number doctrine entity.
     * @return array|null Array with all number information for response.
     */
    private function formatNumberForResponse(?Numbers $number): ?array
    {
        if (!$number)
        {
            return null;
        }

        return array(
            'dataTypeId' => $number->getDataType()->getId(),
            'min' => $number->getMin(),
            'max' => $number->getMax(),
            'minAlert' => $number->getMinAlert(),
            'maxAlert' => $number->getMaxAlert(),
            'evolutionMin' => $number->getEvolutionMin(),
            'evolutionMax' => $number->getEvolutionMax(),
            'isDecimal' => $number->getIsDecimal());
    }


}