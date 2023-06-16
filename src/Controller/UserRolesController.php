<?php

namespace App\Controller;

use App\Common\Enum\Role;
use App\Common\Traits\DocumentaryStructuresTrait;
use App\Common\Traits\RolesTrait;
use App\Common\Traits\UserRolesTrait;
use App\Common\Traits\UsersTrait;
use App\Entity\UserRoles;
use Doctrine\Common\Util\Debug;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use App\Controller\AbstractController\ESGBUController;

/**
 * Class UserRolesController
 * @package App\Controller
 * @SWG\Tag(name="User roles")
 */
class UserRolesController extends ESGBUController
{
    use RolesTrait,
        UsersTrait,
        DocumentaryStructuresTrait,
        UserRolesTrait;

    /**
     * Show all users and their roles.
     * @SWG\Response(
     *     response="200",
     *     description="No user have role.",
     *     @SWG\Schema(type="array", @SWG\Items(ref=@Model(type=UserRoles::class)))
     * )
     * @SWG\Response(
     *     response="404",
     *     description="No user have role. Role filter, user or documentary structure not found.",
     * )
     * @SWG\Parameter(name="role",type="string", in="query",
     *     description="DISTRD, SURVEY_ADMIN, VALID_SURVEY_RESP, USER, DISTRD_RO")
     * @SWG\Parameter(name="userId",type="integer", in="query", description="User id.")
     * @SWG\Parameter(name="docStructId",type="integer", in="query",
     *     description="Documentary structure id. Set 0 to unassociated user roles.")
     * @Rest\Get(
     *      path = "/user-roles",
     *      name = "app_user_roles_list"
     * )
     * @Rest\QueryParam(name="role",
     *     requirements="DISTRD|SURVEY_ADMIN|VALID_SURVEY_RESP|USER|DISTRD_RO",
     *     nullable=true
     * )
     * @Rest\QueryParam(name="userId", requirements="\d+", nullable=true)
     * @Rest\QueryParam(name="docStructId", requirements="\d+", nullable=true)
     * @param string|null $role
     * @param int|null $userId
     * @param int|null $docStructId
     * @return View
     */
    public function listAction(?string $role, ?int $userId, ?int $docStructId) : View
    {
        try
        {
            $distrdOk = $this->checkRightsBool([Role::ADMIN, Role::ADMIN_RO]);
            $rightOk = $distrdOk;

            $criteria = array();

            if ($role)
            {
                $roleEnt = $this->getRoleByName($role);
                $criteria['role'] = $roleEnt;
            }

            if ($userId)
            {
                $user = $this->getUserById($userId);
                $criteria['user'] = $user;
            }

            if ($docStructId)
            {
                $docStruct = $this->getDocStructById($docStructId);
                $criteria['documentaryStructure'] = $docStruct;

                if ($docStruct)
                {
                    $this->checkRights(
                        [Role::ADMIN, Role::ADMIN_RO, Role::VALID_SURVEY_RESP, Role::SURVEY_ADMIN, Role::USER],
                        $docStruct);
                    $rightOk = true;
                }
            }

            if ($docStructId === 0)
            {
                $criteria['documentaryStructure'] = null;
            }

            if (!$distrdOk)
            {
                if (!array_key_exists('documentaryStructure', $criteria))
                {
                    $docStructFilter = $this->getDocStructUser(
                        [Role::USER, Role::SURVEY_ADMIN, Role::VALID_SURVEY_RESP]);
                    if (count($docStructFilter) > 0)
                    {
                        $criteria['documentaryStructure'] = $docStructFilter;
                        $rightOk = true;
                    }
                }
            }

            $userRole = array();
            if ($rightOk)
            {
                $userRole = $this->managerRegistry->getRepository(UserRoles::class)->findBy($criteria);
            }

            if (count($userRole) === 0)
            {
                return $this->createView('No user have role.', Response::HTTP_NOT_FOUND);
            }
            return $this->createView($userRole, Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Associate user, role and documentary structure.
     * @SWG\Response(
     *     response="201",
     *     description="Create a new association.",
     *     @Model(type=UserRoles::class)
     * )
     * @SWG\Response(response="404", description="No role, user or documentary structure found.")
     * @SWG\Response(response="409", description="Association already exists.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="body", in="body", description="Association informations.",
     *     @SWG\Schema(type="object",
     *      @SWG\Property(property="roleId", type="integer"),
     *      @SWG\Property(property="docStructId", type="integer"),
     *      @SWG\Property(property="userId", type="integer"))
     * )
     * @Rest\Post(path="/user-roles", name="app_user_roles_create")
     * @Rest\RequestParam(name="roleId", requirements="\d+", nullable=false)
     * @Rest\RequestParam(name="userId", requirements="\d+", nullable=false)
     * @Rest\RequestParam(name="docStructId", requirements="\d+", nullable=true)
     * @Rest\RequestParam(name="active", nullable=true)
     * @Rest\View
     * @param int $roleId Role id.
     * @param int $userId User id.
     * @param int|null $docStructId Documentary structure id.
     * @param bool $active Role is active.
     * @return View Association has just been created.
     */
    public function createAction(int $roleId, int $userId, ?int $docStructId, ?bool $active) : View
    {
        try
        {
            $role = $this->getRoleById($roleId);
            $user = $this->getUserById($userId);
            $docStruct = ($docStructId) ? $this->getDocStructById($docStructId) : null;
            if ($role->getId() === Role::ADMIN)
            {
                $this->checkRights([Role::ADMIN]);
            }
            if ($docStruct)
            {
                $this->checkRights([Role::ADMIN, Role::SURVEY_ADMIN, Role::VALID_SURVEY_RESP], $docStruct);
            }

            if (!$user->getValid())
            {
                return $this->createView('User must be valid to add role.',
                    Response::HTTP_BAD_REQUEST, true);
            }

            if ($role->getAssociated() && !$docStruct)
            {
                return $this->createView('Documentary structure can\'t be null with associated role.',
                    Response::HTTP_BAD_REQUEST, true);
            }

            if (!$role->getAssociated())
            {
                $docStruct = null;
            }

            $this->checkIfExistsUserRole($role, $user, $docStruct);

            $userRole = new UserRoles($role, $user, $docStruct, $active);
            $em = $this->managerRegistry->getManager();
            $em->persist($userRole);
            $em->flush();

            return $this->createView($userRole, Response::HTTP_CREATED, true);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }


    /**
     * Delete user role association.
     * @SWG\Response(response="204", description="Association deleted.")
     * @SWG\Response(response="404", description="Association not found.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="id",type="integer", in="path", description="User role id.")
     * @Rest\Delete(
     *      path="/user-roles/{id}",
     *      name="app_user_roles_delete",
     *      requirements={"id"="\d+"}
     * )
     * @Rest\View
     * @param int $id
     * @return View Information about action.
     */
    public function deleteAction(int $id) : View
    {
        try
        {
            $userRole = $this->getUserRoleById($id);

            $this->checkRights([Role::ADMIN, Role::SURVEY_ADMIN, Role::VALID_SURVEY_RESP],
                $userRole->getDocumentaryStructure());

            $em = $this->managerRegistry->getManager();
            $em->remove($userRole);
            $em->flush();

            return $this->createView('User and his role have been deleted.',
                Response::HTTP_NO_CONTENT, true);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }

    /**
     * Update status of user role.
     * @SWG\Response(
     *     response="200",
     *     description="Return user role updated.",
     *     @Model(type=UserRoles::class)
     * )
     * @SWG\Response(response="404", description="No user role found.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="userRoleId", type="integer", in="path", description="Survey id of relation.")
     * @Rest\Patch(
     *     path="/user-roles/{userRoleId}",
     *     name="app_user_roles_update",
     *     requirements={"userRoleId"="\d+", "dataTypeId"="\d+"}
     * )
     * @Rest\RequestParam(name="active", strict=true)
     * @Rest\View
     * @param int $userRoleId Id of user role association.
     * @param bool $active True to active the relation, else false.
     * @return View Relation has just been updated.
     */
    public function updateAction(int $userRoleId, bool $active) : View
    {
        try
        {
            $userRole = $this->getUserRoleById($userRoleId);

            $this->checkRights([Role::ADMIN, Role::VALID_SURVEY_RESP, Role::SURVEY_ADMIN],
                $userRole->getDocumentaryStructure());

            $userRole->setActive($active);
            $this->managerRegistry->getManager()->flush();

            return $this->createView($userRole, Response::HTTP_OK, true);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }


}
