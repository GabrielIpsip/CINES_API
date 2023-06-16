<?php

namespace App\Controller;

use App\Common\Traits\StatesTrait;
use App\Entity\States;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Response;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use App\Controller\AbstractController\ESGBUController;

/**
 * Class StatesController
 * @package App\Controller
 * @SWG\Tag(name="States")
 */
class StatesController extends ESGBUController
{
    use StatesTrait;

    /**
     * Show all states.
     * @SWG\Response(
     *     response="200",
     *     description="No state found.",
     *     @SWG\Schema(type="array", @SWG\Items(ref=@Model(type=States::class)))
     * )
     * @SWG\Response(
     *     response="404",
     *     description="No state found.",
     * )
     * @Rest\Get(
     *      path = "/states",
     *      name = "app_state_list"
     * )
     * @Rest\View
     * @return View Array with all states.
     */
    public function listAction() : View
    {
        try
        {
            $states = $this->getAllStates();
            return $this->createView($states, Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

    /** Show state by id.
     * @SWG\Response(
     *     response="200",
     *     description="Return state select by id.",
     *     @Model(type=States::class)
     * )
     * @SWG\Response(
     *     response="404",
     *     description="No state found.",
     * )
     * @SWG\Parameter(name="id",type="integer", in="path", description="State id.")
     * @Rest\Get(
     *      path = "/states/{id}",
     *      name = "app_states_show",
     *      requirements = {"id"="\d+"}
     * )
     * @Rest\View
     * @param int $id State id.
     * @return View State information.
     */
    public function showAction(int $id) : View
    {
        try
        {
            $state = $this->getStateById($id);
            return $this->createView($state, Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

}