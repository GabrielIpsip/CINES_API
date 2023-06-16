<?php


namespace App\Controller;

use App\Common\Traits\DocumentaryStructuresTrait;
use App\Common\Traits\RolesTrait;
use App\Common\Traits\UserRoleRequestsTrait;
use App\Common\Traits\UsersTrait;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\HttpFoundation\Response;
use Swagger\Annotations as SWG;
use App\Entity\UserRoleRequests;
use App\Controller\AbstractController\ESGBUController;

/**
 * Class UserRoleRequestsController
 * @package App\Controller
 * @SWG\Tag(name="User role request")
 */
class UserRoleRequestsController extends ESGBUController
{

    use RolesTrait,
        UsersTrait,
        DocumentaryStructuresTrait,
        UserRoleRequestsTrait;

    /**
     * Show all user role requests.
     * For DISTRD: show all requests.
     * For doc struct admin: show all requests for his doc struct.
     * For survey admin: show all user role requests for his doc struct.
     * @SWG\Response(
     *     response="200",
     *     description="User role request entity.",
     *     @SWG\Schema(type="array", @SWG\Items(ref=@Model(type=UserRoleRequests::class)))
     * )
     * @SWG\Response(
     *     response="404",
     *     description="No user role request or role 'user' found.",
     * )
     * @Rest\Get(
     *      path = "/user-role-requests",
     *      name = "app_user_role_request_list"
     * )
     * @Rest\View
     * @return View Array with all groups.
     */
    public function listAction() : View
    {
       try
       {
           $this->managerRegistry->getRepository(UserRoleRequests::class)->deleteOlder();

           $userRoleRequests = $this->getAllRequestForUser();
           $userRoleRequests = $this->removeNotValidUser($userRoleRequests);

           if (count($userRoleRequests) === 0)
           {
               return $this->createView('No user role request found.', Response::HTTP_NOT_FOUND);
           }

           return $this->createView($userRoleRequests, Response::HTTP_OK);
       }
       catch (Exception $e)
       {
            return $this->createView($e->getMessage(), $e->getCode());
       }
    }

    /**
     * Create new user role request.
     * @SWG\Response(
     *     response="201",
     *     description="Create a user role request.",
     *     @Model(type=UserRoleRequests::class)
     * )
     * @SWG\Response(response="404", description="No user, role or doc struct found.")
     * @SWG\Parameter(name="body", in="body", description="Survey informations.",
     *     @SWG\Schema(type="object",
     *     @SWG\Property(property="userId", type="integer"),
     *     @SWG\Property(property="roleId", type="integer"),
     *     @SWG\Property(property="docStructId", type="integer"),
     *     ))
     * @Rest\Post(path="/user-role-requests", name="app_user_role_requests_create")
     * @Rest\RequestParam(name="userId", requirements="[0-9]+", nullable=false)
     * @Rest\RequestParam(name="roleId", requirements="[0-9]+", nullable=false)
     * @Rest\RequestParam(name="docStructId", requirements="[0-9]*", nullable=true)
     * @Rest\View
     * @param int $userId User when will get the role.
     * @param int $roleId Role give to user.
     * @param int|null $docStructId Doc struct if role is associated role.
     * @return View Survey has just been created.
     */
    public function createAction(int $userId, int $roleId, ?int $docStructId) : View
    {
        try
        {
            $doctrine = $this->managerRegistry;
            $doctrine->getRepository(UserRoleRequests::class)->deleteOlder();

            $user = $this->getUserById($userId);
            $role = $this->getRoleById($roleId);

            if (!$docStructId && $role->getAssociated())
            {
                return $this->createView('Role must be associated with documentary structure.',
                    Response::HTTP_BAD_REQUEST, true);
            }

            if (!$role->getAssociated())
            {
                $docStructId = null;
            }

            $docStruct = null;
            if ($docStructId)
            {
                $docStruct = $this->getDocStructById($docStructId);
            }

            $userRoleRequests = new UserRoleRequests($docStruct, $role, $user);

            $em = $doctrine->getManager();
            $em->persist($userRoleRequests);
            $em->flush();

            $this->notifyRegistrationToManagerByMail($userId);

            return $this->createView($userRoleRequests, Response::HTTP_CREATED, true);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }


    /**
     * Delete user role request.
     * @SWG\Response(response="204", description="Request deleted.")
     * @SWG\Response(response="404", description="Request not found.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="id",type="integer", in="path", description="User role request id.")
     * @Rest\Delete(
     *      path="/user-role-requests/{id}",
     *      name="app_user_role_requests_delete",
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
            $doctrine = $this->managerRegistry;
            $doctrine->getRepository(UserRoleRequests::class)->deleteOlder();

            $userRoleRequest = $this->getUserRoleRequestById($id);
            $userRoleRequests = $this->getAllRequestForUser();

            if (in_array($userRoleRequest, $userRoleRequests))
            {
                $em = $doctrine->getManager();
                $em->remove($userRoleRequest);
                $em->flush();
            }
            else
            {
                return $this->createView(self::FORBIDDEN_ERROR, Response::HTTP_FORBIDDEN, true);
            }

            return $this->createView('User role request has been deleted.',
                Response::HTTP_NO_CONTENT, true);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }
}
