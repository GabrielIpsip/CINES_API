<?php


namespace App\Controller;


use App\Common\Traits\DepartmentsTrait;
use App\Controller\AbstractController\ESGBUController;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Departments;
use Nelmio\ApiDocBundle\Annotation\Model;

/**
 * Class DepartmentsController
 * @package App\Controller
 * @SWG\Tag(name="Departments")
 */
class DepartmentsController extends ESGBUController
{
    use DepartmentsTrait;

    /**
     * Show all departments.
     * @SWG\Response(
     *     response="200",
     *     description="List of all departments.",
     *     @SWG\Schema(type="array", @SWG\Items(ref=@Model(type=Departments::class)))
     * )
     * @SWG\Response(
     *     response="404",
     *     description="No department found.",
     * )
     * @Rest\Get(
     *      path = "/departments",
     *      name = "app_departments_list"
     * )
     * @Rest\View
     * @return View Array with all departments.
     */
    public function listAction() : View
    {
        try
        {
            $departments = $this->getAllDepartments();
            return $this->createView($departments, Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Show all departments.
     * @SWG\Response(
     *     response="200",
     *     description="List of all departments.",
     *     @SWG\Schema(type="array", @SWG\Items(ref=@Model(type=Departments::class)))
     * )
     * @SWG\Response(
     *     response="404",
     *     description="No department found.",
     * )
     * @Rest\Get(
     *      path = "/public/departments",
     *      name = "app_departments_public_list"
     * )
     * @Rest\View
     * @return View Array with all departments.
     */
    public function publicListAction(): View
    {
        return $this->listAction();
    }

    /** Show department by postal code.
     * @SWG\Response(
     *     response="200",
     *     description="Return department select by postal code.",
     *     @Model(type=Departments::class)
     * )
     * @SWG\Response(
     *     response="404",
     *     description="No department found.",
     * )
     * @SWG\Response(response="400", description="Error in postal code")
     * @SWG\Parameter(name="postalCode",type="string", in="path", description="Postal code.")
     * @Rest\Get(
     *      path = "/departments/{postalCode}",
     *      name = "app_departments_show",
     *      requirements = {"postalCode"="\d+"}
     * )
     * @Rest\View
     * @param string $postalCode Postal code.
     * @return View Department information.
     */
    public function showAction(string $postalCode): View
    {
        try
        {
            if (strlen($postalCode) > 5)
            {
                return $this->createView('Error in postal code', Response::HTTP_BAD_REQUEST);
            }

            $department = $this->getDepartmentByPostalCode($postalCode);
            return $this->createView($department, Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }
}
