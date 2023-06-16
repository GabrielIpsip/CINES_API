<?php


namespace App\Common\Traits;


use App\Entity\GroupInstructions;
use App\Entity\Groups;
use App\Entity\Surveys;
use Exception;
use Symfony\Component\HttpFoundation\Response;

trait GroupInstructionsTrait
{

    /**
     * Get Group instruction by this survey and this group.
     * @param Surveys $survey Survey doctrine entity.
     * @param Groups $group Group doctrine entity.
     * @return GroupInstructions Group instruction doctrine entity.
     * @throws Exception 404 : No group instruction found.
     */
    private function getGroupInstructionBySurveyAndGroup(Surveys $survey, Groups $group): GroupInstructions
    {
        $instruction = $this->managerRegistry->getRepository(GroupInstructions::class)
            ->findOneBy(array('survey' => $survey, 'group' => $group));
        if (!$instruction)
        {
            throw new Exception('No instruction found.', Response::HTTP_NOT_FOUND);
        }
        return $instruction;
    }
}