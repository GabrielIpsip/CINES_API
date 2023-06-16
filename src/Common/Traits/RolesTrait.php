<?php


namespace App\Common\Traits;


use App\Entity\Roles;
use Exception;
use Symfony\Component\HttpFoundation\Response;

trait RolesTrait
{
    /**
     * Get all role in database.
     * @return array Array with all role doctrine entities.
     * @throws Exception 404 : No role found.
     */
    private function getAllRoles(): array
    {
        $roles = $this->managerRegistry->getRepository(Roles::class)->findAll();
        if (count($roles) === 0)
        {
            throw new Exception('No role found.', Response::HTTP_NOT_FOUND);
        }
        return $roles;
    }

    /**
     * Get role by id.
     * @param int $id Id of role.
     * @return Roles Role doctrine entity.
     * @throws Exception 404 : No role found with this id.
     */
    private function getRoleById(int $id): Roles
    {
        $role = $this->managerRegistry->getRepository(Roles::class)->find($id);
        if (!$role)
        {
            throw new Exception('No role with id : ' . $id, Response::HTTP_NOT_FOUND);
        }
        return $role;
    }

    /**
     * Get role by name.
     * @param string|null $name Name of role.
     * @return Roles Role doctrine entity.
     * @throws Exception 404 : No role found with this name.
     */
    private function getRoleByName(?string $name): ?Roles
    {
        $role = $this->managerRegistry->getRepository(Roles::class)->findOneBy(array('name' => $name));
        if (!$role)
        {
            throw new Exception('No role with name : ' . $name, Response::HTTP_NOT_FOUND);
        }
        return $role;
    }
}