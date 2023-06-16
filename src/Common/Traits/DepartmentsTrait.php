<?php


namespace App\Common\Traits;


use App\Entity\Departments;
use Exception;
use Symfony\Component\HttpFoundation\Response;

trait DepartmentsTrait
{
    /**
     * @param string $postalCode
     * @return Departments
     * @throws Exception
     */
    private function getDepartmentByPostalCode(string $postalCode): Departments
    {
        $postalCode3 = mb_substr($postalCode, 0, 3);
        $postalCode2 = mb_substr($postalCode, 0, 2);

        $repository = $this->managerRegistry->getRepository(Departments::class);

        if ($postalCode2 === '00')
        {
            $department = $repository->findOneBy(['code' => '999']);
        }
        else
        {
            $department = $repository->findOneBy(
                ['code' => [$postalCode3, $postalCode2]]
            );
        }

        if ($department == null) {
            print($postalCode2 . "\n");
            print($postalCode3 . "\n");
            throw new Exception('No department found for this postal code : ' . $postalCode);
        }

        return $department;
    }

    /**
     * @param int $id
     * @return Departments|object
     * @throws Exception
     */
    private function getDepartmentById(int $id): Departments
    {
        $department = $this->managerRegistry->getRepository(Departments::class)->find($id);
        if (!$department)
        {
            throw new Exception('No department found with this id : ' . $id, Response::HTTP_NOT_FOUND);
        }
        return $department;
    }

    private function getAllDepartments(): array
    {
        $departments = $this->managerRegistry->getRepository(Departments::class)->findAll();
        if (count($departments) === 0)
        {
            throw new Exception('No department found.', Response::HTTP_NOT_FOUND);
        }
        return $departments;
    }
}
