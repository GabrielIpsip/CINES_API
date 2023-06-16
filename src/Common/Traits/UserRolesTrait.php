<?php


namespace App\Common\Traits;


use App\Entity\DocumentaryStructures;
use App\Entity\Roles;
use App\Entity\UserRoles;
use App\Entity\Users;
use Exception;
use Symfony\Component\HttpFoundation\Response;

trait UserRolesTrait
{
    /**
     * Check if userRole already exists in database.
     * @param Roles $role Role of userRole.
     * @param Users $user User of userRole.
     * @param DocumentaryStructures|null $docStruct Documentary structure of userRole.
     * @throws Exception 409 : userRole already exists.
     */
    private function checkIfExistsUserRole(Roles $role, Users $user, ?DocumentaryStructures $docStruct)
    {
        $existUserRole = $this->managerRegistry->getRepository(UserRoles::class)
            ->findOneBy(array('role' => $role, 'user' => $user, 'documentaryStructure' => $docStruct));

        if ($existUserRole)
        {
            throw new Exception('Association already exists.', Response::HTTP_CONFLICT);
        }
    }

    /**
     * Get all user role which match with these criteria.
     * @param Roles|null $role Role doctrine entity.
     * @param DocumentaryStructures|null $docStruct Documentary structure doctrine entity.
     * @return UserRoles[] List of user role.
     */
    private function getUserRoleByCriteria(?Roles $role = null, ?DocumentaryStructures $docStruct = null): array
    {
        $criteria = [];
        if ($role)
        {
            $criteria['role'] = $role;
        }

        if ($docStruct)
        {
            $criteria['documentaryStructure'] = $docStruct;
        }

        $userRoles = $this->managerRegistry->getRepository(UserRoles::class)->findBy($criteria);
        if (count($userRoles) === 0)
        {
            return $this->createView('No user role found.', Response::HTTP_NOT_FOUND);
        }
        return $userRoles;
    }

    /**
     * Get userRole in database by id.
     * @param int $id Id of userRole.
     * @return UserRoles User role doctrine entity.
     */
    private function getUserRoleById(int $id): UserRoles
    {
        $userRole = $this->managerRegistry->getRepository(UserRoles::class)->find($id);
        if (!$userRole)
        {
            return $this->createView('Association not found.', Response::HTTP_NOT_FOUND);
        }
        return $userRole;
    }
}
