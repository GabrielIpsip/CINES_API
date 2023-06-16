<?php

namespace App\Controller;

use App\Common\Traits\RolesTrait;
use App\Entity\Roles;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use App\Controller\AbstractController\ESGBUController;

/**
 * Class RolesController
 * @package App\Controller
 * @SWG\Tag(name="Roles")
 */
class RolesController extends ESGBUController
{
    use RolesTrait;

    /**
     * Show all roles user.
     * @SWG\Response(
     *     response="200",
     *     description="No role found.",
     *     @SWG\Schema(type="array", @SWG\Items(ref=@Model(type=Roles::class)))
     * )
     * @SWG\Response(
     *     response="404",
     *     description="No role found.",
     * )
     * @Rest\Get(
     *      path = "/roles",
     *      name = "app_roles_list"
     * )
     * @Rest\View
     * @return View Array with all roles user.
     */
    public function listAction() : View
    {
        try
        {
            $roles = $this->getAllRoles();
            return $this->createView($roles, Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

    /** Show role user by id.
     * @SWG\Response(
     *     response="200",
     *     description="Return role select by id.",
     *     @Model(type=Roles::class)
     * )
     * @SWG\Response(
     *     response="404",
     *     description="No role found.",
     * )
     * @SWG\Parameter(name="id",type="integer", in="path", description="Role type id.")
     * @Rest\Get(
     *      path = "/roles/{id}",
     *      name = "app_roles_show",
     *      requirements = {"id"="\d+"}
     * )
     * @Rest\View
     * @param int $id Role type id.
     * @return View Role information.
     */
    public function showAction(int $id) : View
    {
        try
        {
            $role = $this->getRoleById($id);
            return $this->createView($role, Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

}