<?php

namespace App\Controller;

use App\Common\Traits\EstablishmentTypesTrait;
use App\Entity\EstablishmentTypes;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use App\Controller\AbstractController\ESGBUController;

/**
 * Class EstablishmentTypesController
 * @package App\Controller
 * @SWG\Tag(name="Establishment types")
 */
class EstablishmentTypesController extends ESGBUController
{

    use EstablishmentTypesTrait;

    /**
     * Show all establishment types.
     * @SWG\Response(
     *     response="200",
     *     description="Return all establishment types.",
     *     @SWG\Schema(type="array", @SWG\Items(ref=@Model(type=EstablishmentTypes::class)))
     * )
     * @SWG\Response(
     *     response="404",
     *     description="No establishement type found.",
     * )
     * @Rest\Get(
     *      path = "/establishment-types",
     *      name = "app_establishment_types_list"
     * )
     * @Rest\View
     * @return View Array with all establishment types.
     */
    public function listAction() : View
    {
        try
        {
            $establishmentTypes = $this->getAllEstablishmentTypes();
            return $this->createView($establishmentTypes, Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

    /** Show establishment type by id.
     * @SWG\Response(
     *     response="200",
     *     description="Return establishment type select by id.",
     *     @Model(type=EstablishmentTypes::class)
     * )
     * @SWG\Response(
     *     response="404",
     *     description="No establishement type found.",
     * )
     * @SWG\Parameter(name="id",type="integer", in="path", description="Establishment type id.")
     * @Rest\Get(
     *      path = "/establishment-types/{id}",
     *      name = "app_establishment_types_show",
     *      requirements = {"id"="\d+"}
     * )
     * @Rest\View
     * @param int $id Establishment type id.
     * @return View Establishment information.
     */
    public function showAction(int $id) : View
    {
        try
        {
            $establishmentType = $this->getEstablishmentTypeById($id);
            return $this->createView($establishmentType, Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

}