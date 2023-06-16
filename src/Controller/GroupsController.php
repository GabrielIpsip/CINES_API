<?php

namespace App\Controller;

use App\Common\Enum\Role;
use App\Common\Traits\AdministrationTypesTrait;
use App\Common\Traits\GroupsTrait;
use App\Entity\AdministrationTypes;
use App\Entity\Groups;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\HttpFoundation\Response;
use Swagger\Annotations as SWG;
use App\Controller\AbstractController\ESGBUController;

/**
 * Class GroupsController
 * @package App\Controller
 * @SWG\Tag(name="Groups")
 */
class GroupsController extends ESGBUController
{

    use AdministrationTypesTrait,
        GroupsTrait;

    private const TABLE_NAME = 'groups';

    /**
     * Show all groups.
     * @SWG\Response(
     *     response="200",
     *     description="Group entity.",
     * @SWG\Schema(type="array",
     *         @SWG\Items(allOf={
     *          @SWG\Schema(ref=@Model(type=Groups::class))},
     *          @SWG\Property(property="id", type="integer"),
     *          @SWG\Property(property="parentGroupId", type="integer"),
     *          @SWG\Property(property="title", type="string"))))
     * )
     * @SWG\Parameter(name="lang",type="string", in="query", description="Language code. (ex: fr)")
     * @SWG\Parameter(name="parentId",type="string", in="query",
     *     description="Set this ID to get child groups, 0 to get all groups or null to get root groups.")
     * @SWG\Response(
     *     response="404",
     *     description="No group found.",
     * )
     * @Rest\Get(
     *      path = "/groups",
     *      name = "app_groups_list"
     * )
     * @Rest\QueryParam(name="parentId", requirements="([0-9]*|null)", nullable=true, default="0")
     * @Rest\QueryParam(name="administrationType", requirements="[a-z]*", nullable=true)
     * @Rest\QueryParam(name="lang", requirements="[a-z]{2}", default=ESGBUController::DEFAULT_LANG)
     * @Rest\View
     * @param string|null $parentId Set ID to get child groups.
     * @param string|null $administrationType Filter by administration.
     * @param string $lang Code to choose title lang.
     * @return View Array with all groups.
     */
    public function listAction(?string $parentId, ?string $administrationType, string $lang) : View
    {
        try
        {
            $criteria = array();
            if ($administrationType)
            {
                $criteria['administrationType'] = $this->getAdministrationTypeByName($administrationType);
            }

            if ($parentId === 'null')
            {
                $criteria['parentGroup'] = null;
            }
            else if ($parentId > 0)
            {
                $criteria['parentGroup'] = $this->getGroupById($parentId);
            }

            $groups = $this->getGroupsByCriteria($criteria);
            $formattedGroup = array();
            foreach ($groups as $group) {
                array_push($formattedGroup, $this->getFormattedGroupForResponse($group, $lang));
            }
            return $this->createView($formattedGroup, Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Show all groups.
     * @SWG\Response(
     *     response="200",
     *     description="Group entity.",
     * @SWG\Schema(type="array",
     *         @SWG\Items(allOf={
     *          @SWG\Schema(ref=@Model(type=Groups::class))},
     *          @SWG\Property(property="id", type="integer"),
     *          @SWG\Property(property="parentGroupId", type="integer"),
     *          @SWG\Property(property="title", type="string"))))
     * )
     * @SWG\Parameter(name="lang",type="string", in="query", description="Language code. (ex: fr)")
     * @SWG\Parameter(name="parentId",type="string", in="query",
     *     description="Set this ID to get child groups, 0 to get all groups or null to get root groups.")
     * @SWG\Response(
     *     response="404",
     *     description="No group found.",
     * )
     * @Rest\Get(
     *      path = "/public/groups",
     *      name = "app_public_groups_list"
     * )
     * @Rest\QueryParam(name="parentId", requirements="([0-9]*|null)", nullable=true, default="0")
     * @Rest\QueryParam(name="administrationType", requirements="[a-z]*", nullable=true)
     * @Rest\QueryParam(name="lang", requirements="[a-z]{2}", default=ESGBUController::DEFAULT_LANG)
     * @Rest\View
     * @param string|null $parentId Set ID to get child groups.
     * @param string|null $administrationType Filter by administration.
     * @param string $lang Code to choose title lang.
     * @return View Array with all groups.
     */
    public function publicListAction(?string $parentId, ?string $administrationType, string $lang) : View
    {
        return $this->listAction($parentId, $administrationType, $lang);
    }

    /** Show group by id.
     * @SWG\Response(
     *     response="200",
     *     description="Return group select by id.",
     *     @SWG\Items(allOf={
     *        @SWG\Schema(ref=@Model(type=Groups::class))},
     *        @SWG\Property(property="id", type="integer"),
     *        @SWG\Property(property="parentGroupId", type="integer"),
     *        @SWG\Property(property="title", type="string")))
     * )
     * @SWG\Parameter(name="lang",type="string", in="query", description="Language code.")
     * @SWG\Response(
     *     response="404",
     *     description="No group found.",
     * )
     * @SWG\Parameter(name="id",type="integer", in="path", description="Group id.")
     * @Rest\Get(
     *      path = "/groups/{id}",
     *      name = "app_groups_show",
     *      requirements = {"id"="\d+"}
     * )
     * @Rest\QueryParam(name="lang", requirements="[a-z]{2}", default=ESGBUController::DEFAULT_LANG)
     * @Rest\View
     * @param int $id Group id.
     * @param string $lang Code to choose title lang.
     * @return View Group information.
     */
    public function showAction(int $id, string $lang) : View
    {
        try
        {
            $group = $this->getGroupById($id);
            return $this->createView($this->getFormattedGroupForResponse($group, $lang), Response::HTTP_OK);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Create new group.
     * @SWG\Response(
     *     response="201",
     *     description="Create a group.",
     *     @SWG\Items(allOf={
     *        @SWG\Schema(ref=@Model(type=Groups::class))},
     *        @SWG\Property(property="id", type="integer"),
     *        @SWG\Property(property="parentGroupId", type="integer"),
     *        @SWG\Property(property="title", type="string")))
     * )
     * @SWG\Response(response="404", description="No group or lang found.")
     * @SWG\Response(response="400", description="Bad request. Body not valid.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="body", in="body", description="Group informations.",
     *     @SWG\Schema(type="object",
     *     @SWG\Property(property="parentGroupId", type="integer"),
     *     @SWG\Property(property="administrationTypeId", type="integer"),
     *     @SWG\Property(property="titles", type="array",
     *     @SWG\Items(type="object",
     *      @SWG\Property(property="lang", type="string"),
     *      @SWG\Property(property="value", type="string"))))
     * )
     * @Rest\Post(path="/groups", name="app_groups_create")
     * @Rest\RequestParam(name="parentGroupId", requirements="[0-9]*", nullable=true)
     * @Rest\RequestParam(name="administrationTypeId", requirements="[0-9]*", nullable=false)
     * @Rest\RequestParam(name="titles", nullable=false)
     * @Rest\View
     * @param array $titles All title value for each lang.
     * @param int|null $parentGroupId Id of parentGroup.
     * @param int $administrationTypeId Id of administration type.
     * @return View Groups has just been created.
     */
    public function createAction(array $titles, ?int $parentGroupId, int $administrationTypeId) : View
    {
        try
        {
            $this->checkRights([Role::ADMIN]);

            $em = $this->managerRegistry->getManager();

            $parentGroup = null;
            if ($parentGroupId)
            {
                $parentGroup = $this->getGroupById($parentGroupId);
                if ($parentGroup->getAdministrationType()->getId() != $administrationTypeId)
                {
                    return $this->createView('Can\'t set parent group with another administration type.',
                        Response::HTTP_BAD_REQUEST, true);
                }
            }

            $administrationType = $this->getAdministrationTypeById($administrationTypeId);

            $content = $this->addTranslation($titles, self::TABLE_NAME);

            $newGroup = new Groups($parentGroup, $content, $administrationType);
            $em->persist($newGroup);
            $em->flush();

            return $this->createView($this->getFormattedGroupForResponse($newGroup, self::DEFAULT_LANG),
                Response::HTTP_CREATED, true);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }

    /**
     * Update a group.
     * @SWG\Response(
     *     response="200",
     *     description="Update a group selected by id.",
     *     @SWG\Items(allOf={
     *        @SWG\Schema(ref=@Model(type=Groups::class))},
     *        @SWG\Property(property="id", type="integer"),
     *        @SWG\Property(property="parentGroupId", type="integer"),
     *        @SWG\Property(property="title", type="string")))
     * )
     * @SWG\Response(response="404", description="Data missing in database")
     * @SWG\Response(response="400", description="Error to update group. Body not valid.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="id", type="integer", in="path", description="Group id to update.")
     * @SWG\Parameter(name="body", in="body", description="Group informations.",
     *     @SWG\Schema(type="object",
     *     @SWG\Property(property="parentGroupId", type="integer"),
     *     @SWG\Property(property="administrationTypeId", type="integer"),
     *     @SWG\Property(property="titles", type="array",
     *     @SWG\Items(type="object",
     *      @SWG\Property(property="lang", type="string"),
     *      @SWG\Property(property="value", type="string"))))
     * )
     * @Rest\Put(
     *     path="/groups/{id}",
     *     name="app_groups_update",
     *     requirements={"id"="\d+"}
     * )
     * @Rest\RequestParam(name="parentGroupId", requirements="[0-9]*", nullable=true)
     * @Rest\RequestParam(name="administrationTypeId", requirements="[0-9]*", nullable=false)
     * @Rest\RequestParam(name="titles", nullable=false)
     * @Rest\View
     * @param int $id Group id to update.
     * @param int|null $parentGroupId New parent group id.
     * @param int $administrationTypeId Id of administration type.
     * @param array $titles New title for this group.
     * @return View Groups has just been updated.
     */
    public function updateAction(int $id, ?int $parentGroupId, int $administrationTypeId, array $titles) : View
    {
        try
        {
            $this->checkRights([Role::ADMIN]);

            $existingGroup = $this->getGroupById($id);

            if ($parentGroupId)
            {
                $parentGroup = $this->getGroupById($parentGroupId);
                if ($parentGroupId == $existingGroup->getId()
                    || $this->isParent($existingGroup->getId(), $parentGroup))
                {
                    return $this->createView('Can\'t create cyclic relation.',
                        Response::HTTP_BAD_REQUEST, true);
                }
                if ($parentGroup->getAdministrationType()->getId() != $administrationTypeId)
                {
                    return $this->createView('Can\'t set parent group with another administration type.',
                        Response::HTTP_BAD_REQUEST, true);
                }
                $existingGroup->setParentGroup($parentGroup);
            }
            else
            {
                $existingGroup->setParentGroup(null);
            }

            $this->updateAdministrationType($existingGroup, $administrationTypeId);
            $this->updateTranslation($titles, $existingGroup->getTitle(), self::TABLE_NAME);

            $this->managerRegistry->getManager()->flush();

            return $this->createView($this->getFormattedGroupForResponse($existingGroup, self::DEFAULT_LANG),
                Response::HTTP_OK, true);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }

    /**
     * Check if group is a parent of other group.
     * @param int $groupId Id of group to search in parent group of $groupToCheck.
     * @param Groups|null $groupToCheck Group doctrine entity which we search $groupId in these parents.
     * @return bool True if $groupId is a parent of $groupToCheck, else false;
     */
    private function isParent(int $groupId, ?Groups $groupToCheck): bool
    {
        if ($groupToCheck == null)
        {
            return false;
        }
        else
        {
            return ($groupToCheck->getId() === $groupId)
                || $this->isParent($groupId, $groupToCheck->getParentGroup());
        }
    }

    /**
     * Update administration type for a group.
     * @param Groups $existingGroup Group doctrine entity to update.
     * @param int $administrationTypeId New administration type for group.
     * @throws Exception 404 : No administration type found with this id.
     */
    private function updateAdministrationType(Groups $existingGroup, int $administrationTypeId)
    {
        $administrationType = $this->getAdministrationTypeById($administrationTypeId);
        $existingGroup->setAdministrationType($administrationType);

        $allGroup = $this->getDoctrine()->getRepository(Groups::class)
            ->findAll();
        $this->updateSubGroupAdministrationType($existingGroup, $administrationType, $allGroup);
    }

    /**
     * Update administration type for all child of a group.
     * @param Groups $group Parent group. Doctrine group entity.
     * @param AdministrationTypes $administrationType New administration type to update for all child of $group.
     * @param array $allGroup All group which exists in database.
     */
    private function updateSubGroupAdministrationType(Groups $group, AdministrationTypes $administrationType,
                                                      array $allGroup)
    {
        foreach ($allGroup as $g)
        {
            if ($g->getParentGroup() && $g->getParentGroup()->getId() === $group->getId())
            {
                $g->setAdministrationType($administrationType);
                $this->updateSubGroupAdministrationType($g, $administrationType, $allGroup);
            }
        }
    }

    /**
     * Format group for response.
     * @param Groups|null $group Group doctrine entity to format.
     * @param string $lang Lang for the response.
     * @return array|null Array representation of group for response.
     */
    private function getFormattedGroupForResponse(?Groups $group, string $lang) : ?array
    {
        if (!$group)
        {
            return null;
        }
        
        $title = $this->getTranslation($lang, $group->getTitle());

        $parentGroup = $group->getParentGroup();
        $parentGroupId = ($parentGroup) ? $parentGroup->getId() : null;

        return array('id' => $group->getId(),
                     'parentGroupId' => $parentGroupId,
                     'title' => $title,
                     'administrationType' => $group->getAdministrationType());
    }

}