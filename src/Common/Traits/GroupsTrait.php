<?php

namespace App\Common\Traits;

use App\Entity\Groups;
use Exception;
use Symfony\Component\HttpFoundation\Response;

trait GroupsTrait
{
    /**
     * Get group entity by this id.
     * @param int $groupId Id of group in database.
     * @return Groups Group identified by this id.
     * @throws Exception 404 : No group with this id.
     */
    private function getGroupById(int $groupId): Groups
    {
        $group = $this->managerRegistry->getRepository(Groups::class)->find($groupId);
        if (!$group)
        {
            throw new Exception('No group with id : ' . $groupId, Response::HTTP_NOT_FOUND);
        }
        return $group;
    }

    /**
     * Get all groups which match with this criteria.
     * @param array $criteria Array that contains criteria for group research in database.
     * @return array Array of doctrine entity with all groups which match with this criteria.
     * @throws Exception 404 : No group found.
     */
    private function getGroupsByCriteria(array $criteria): array
    {
        $groups = $this->managerRegistry->getRepository(Groups::class)->findBy($criteria);
        if (count($groups) === 0)
        {
            throw new Exception('No group found.', Response::HTTP_NOT_FOUND);
        }
        return $groups;
    }

    /**
     * Recursive function to sort group list by parent.
     * @param Groups[] $groups Array with all group to sort.
     * @param Groups[] $sortedGroup Reference to sorted group result.
     * @param int|null $parentGroupId Root parent group id.
     */
    private function sortGroupByParent(array $groups, array &$sortedGroup, ?int $parentGroupId = null)
    {
        foreach ($groups as $group)
        {
            if (($parentGroupId === null && $group->getParentGroup() == null)
                || ($group->getParentGroup() != null && $group->getParentGroup()->getId() === $parentGroupId))
            {
                array_push($sortedGroup, $group);
                $this->sortGroupByParent($groups, $sortedGroup, $group->getId());
            }
        }
    }

    /**
     * Get all group sorted by parent group and id.
     * @return Groups[] Array of groups doctrine entity.
     */
    private function getAllGroupSortedByParent(): array
    {
        $groups = $this->managerRegistry->getRepository(Groups::class)->findAll();
        $sortedGroup = [];
        $this->sortGroupByParent($groups, $sortedGroup);
        return $sortedGroup;
    }
}
