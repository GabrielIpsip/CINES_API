<?php

namespace App\Controller;

use App\Common\Traits\TypesTrait;
use App\Entity\Types;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use App\Controller\AbstractController\ESGBUController;

/**
 * Class TypesController
 * @package App\Controller
 * @SWG\Tag(name="Types")
 */
class TypesController extends ESGBUController
{

    use TypesTrait;

    /**
     * Show all type of data type.
     * @SWG\Response(
     *     response="200",
     *     description="List of all type.",
     *     @SWG\Schema(type="array", @SWG\Items(ref=@Model(type=Types::class)))
     * )
     * @SWG\Response(
     *     response="404",
     *     description="No type found.",
     * )
     * @Rest\Get(
     *      path = "/types",
     *      name = "app_types_list"
     * )
     * @Rest\View
     * @return View Array with all types of data type.
     */
    public function listAction() : View
    {
        try
        {
            $types = $this->getAllTypes();
            return $this->createView($types, Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

    /** Show type by id.
     * @SWG\Response(
     *     response="200",
     *     description="Return type select by id.",
     *     @Model(type=Types::class)
     * )
     * @SWG\Response(
     *     response="404",
     *     description="No type found.",
     * )
     * @SWG\Parameter(name="id",type="integer", in="path", description="Type id.")
     * @Rest\Get(
     *      path = "/types/{id}",
     *      name = "app_types_show",
     *      requirements = {"id"="\d+"}
     * )
     * @Rest\View
     * @param int $id Type id.
     * @return View Type information.
     */
    public function showAction(int $id) : View
    {
        try
        {
            $type = $this->getTypeById($id);
            return $this->createView($type, Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

}
