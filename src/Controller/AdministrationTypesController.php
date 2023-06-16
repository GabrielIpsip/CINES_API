<?php

namespace App\Controller;

use App\Common\Traits\AdministrationTypesTrait;
use App\Entity\AdministrationTypes;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use App\Controller\AbstractController\ESGBUController;

/**
 * Class AdministrationTypesController
 * @package App\Controller
 * @SWG\Tag(name="Administration types")
 */
class AdministrationTypesController extends ESGBUController
{

    use AdministrationTypesTrait;

    /**
     * Show all administration types.
     * @SWG\Response(
     *     response="200",
     *     description="All administration types.",
     *     @SWG\Schema(type="array", @SWG\Items(ref=@Model(type=AdministrationTypes::class)))
     * )
     * @SWG\Response(
     *     response="404",
     *     description="No administration type found.",
     * )
     * @Rest\Get(
     *      path = "/administration-types",
     *      name = "app_administration_types_list"
     * )
     * @Rest\View
     * @return View Array with all administration types.
     */
    public function listAction() : View
    {
        try
        {
            $administrationTypes = $this->getAllAdministrationTypes();
            return $this->createView($administrationTypes, Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

    /** Show administration type by id.
     * @SWG\Response(
     *     response="200",
     *     description="Return administration type select by id.",
     *     @Model(type=AdministrationTypes::class)
     * )
     * @SWG\Response(
     *     response="404",
     *     description="No administration type found.",
     * )
     * @SWG\Parameter(name="id",type="integer", in="path", description="Administration type id.")
     * @Rest\Get(
     *      path = "/administration-types/{id}",
     *      name = "app_administration_types_show",
     *      requirements = {"id"="\d+"}
     * )
     * @Rest\View
     * @param int $id Administration type id.
     * @return View Administration type information.
     */
    public function showAction(int $id) : View
    {
        try
        {
            $administrationType = $this->getAdministrationTypeById($id);
            return $this->createView($administrationType, Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

}