<?php


namespace App\Common\Traits;


use App\Entity\States;
use Exception;
use Symfony\Component\HttpFoundation\Response;

trait StatesTrait
{
    /**
     * Get all states in database.
     * @return array Array with all state doctrine entities.
     * @throws Exception 404 : No state found.
     */
    private function getAllStates(): array
    {
        $states = $this->managerRegistry->getRepository(States::class)->findAll();
        if (count($states) === 0)
        {
            throw new Exception('No state found.', Response::HTTP_NOT_FOUND);
        }
        return $states;
    }

    /**
     * Get state by id.
     * @param int $id Id of state.
     * @return States State doctrine entity.
     * @throws Exception 404 : No state found.
     */
    private function getStateById(int $id): States
    {
        $state = $this->managerRegistry->getRepository(States::class)->find($id);
        if (!$state)
        {
            throw new Exception('No state with id : ' . $id, Response::HTTP_NOT_FOUND);
        }
        return $state;
    }
}