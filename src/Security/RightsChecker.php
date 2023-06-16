<?php


namespace App\Security;


use App\Entity\DocumentaryStructures;
use App\Entity\Establishments;
use App\Entity\PhysicalLibraries;
use App\Entity\Roles;
use App\Entity\UserRoles;
use App\Entity\Users;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Common\Enum\Role;

class RightsChecker
{

    /**
     * Doctrine registry manager.
     * @var ManagerRegistry
     */
    public $doctrine;

    /**
     * @var Roles[]
     * All roles authorized to execute action.
     */
    private $authorizedRoles;

    /**
     * @var Users
     * User info.
     */
    private $user;

    /**
     * @var UserRoles[]
     * All user role in database.
     */
    private $userRoles;

    /**
     * RightsComponent constructor.
     * @param ManagerRegistry $doctrine
     * @param SessionInterface $session
     * @param array $authorizedRoles
     */
    public function __construct(ManagerRegistry $doctrine, SessionInterface $session, array $authorizedRoles)
    {
        $this->doctrine = $doctrine;
        $this->user = $session->get(ShibbolethAuthenticator::SHIB_USER_SESSION_INDEX);

        if ($_ENV['DEV_USER_ID'] > 0)
        {
            $this->user = $doctrine->getRepository(Users::class)->find($_ENV['DEV_USER_ID']);
            $session->set(ShibbolethAuthenticator::SHIB_USER_SESSION_INDEX, $this->user);
            $session->set(ShibbolethAuthenticator::SHIB_EPPN_SESSION_INDEX, $this->user->getEppn());
        }
        $this->userRoles = $doctrine->getRepository(UserRoles::class)
            ->findBy(array('user' => $this->user));
        $this->updateAuthorizedRole($authorizedRoles);
    }

    /**
     * Update authorized role for right checker.
     * @param array $authorizedRoles array of role enum.
     */
    public function updateAuthorizedRole(array $authorizedRoles)
    {
        $this->authorizedRoles = $this->doctrine->getRepository(Roles::class)
            ->findBy(array('id' => $authorizedRoles));
    }

    /**
     * Check right for current user by administration id.
     * @param int|null $docStructId Documentary structure id.
     * @param int|null $establishmentId Establishment id.
     * @param int|null $physicLibId Physical library id.
     * @param bool $associated False to check role without taking into account administration association.
     * @return bool True if authorized to do action for this establishment, else false.
     */
    public function checkRightsById(?int $docStructId = null,
                                    ?int $establishmentId = null,
                                    ?int $physicLibId = null,
                                    bool $associated = true): bool
    {
        $docStruct = null;
        $establishment = null;
        $physicLib = null;
        if ($docStructId)
        {
            $docStruct = $this->doctrine->getRepository(DocumentaryStructures::class)->find($docStructId);
        }
        if ($establishmentId)
        {
            $establishment = $this->doctrine->getRepository(Establishments::class)->find($establishmentId);
        }
        if ($physicLibId)
        {
            $physicLib = $this->doctrine->getRepository(PhysicalLibraries::class)->find($physicLibId);
        }
        return $this->checkRights($docStruct, $establishment, $physicLib, $associated);
    }

    /**
     * Check right for current user by administration doctrine entity.
     * @param DocumentaryStructures|null $docStruct Documentary structure doctrine entity.
     * @param Establishments|null $establishment Establishment doctrine entity.
     * @param PhysicalLibraries|null $physicLib Physical library doctrine entity.
     * @param bool $associated False to check role without taking into account administration association.
     * @return bool True if authorized to do action for this establishment, else false.
     */
    public function checkRights(?DocumentaryStructures $docStruct = null,
                                ?Establishments $establishment = null,
                                ?PhysicalLibraries $physicLib = null,
                                bool $associated = true): bool
    {
        if (!$this->user)
        {
            return false;
        }

        if (!$this->user->getActive())
        {
            return false;
        }

        if ($this->checkIsDISTRD())
        {
            return true;
        }

        if ($this->matchUnassociatedRole())
        {
            return true;
        }

        if ($associated)
        {
            if (!$docStruct && !$establishment && !$physicLib)
            {
                return false;
            }

            $docStructList = $this->getDocStructList($docStruct, $establishment, $physicLib);

            if (count($docStructList) > 0)
            {
                $criteria = array(
                    'role' => $this->authorizedRoles,
                    'user' => $this->user,
                    'documentaryStructure' => $docStructList);

                $userRoles = $this->doctrine->getRepository(UserRoles::class)
                    ->findBy($criteria);

                return count($userRoles) > 0;
            }
        } else
        {
            foreach ($this->userRoles as $userRole)
            {
                if (in_array($userRole->getRole(), $this->authorizedRoles))
                {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get establishment of current user.
     * @param array|null $roles Role of user for establishment.
     * @return array Array of establishment doctrine entities.
     */
    public function getEstablishmentUser(array $roles = null): array
    {
        if (!$this->user || !$this->user->getActive())
        {
            return array();
        }

        $establishmentList = array();
        foreach ($this->userRoles as $userRoles)
        {
            if (!$roles || in_array($userRoles->getRole()->getId(), $roles))
            {
                array_push($establishmentList, $userRoles->getDocumentaryStructure()->getEstablishment());
            }
        }
        return $establishmentList;
    }

    /**
     * Get documentary structure of current user.
     * @param array|null $roles Role of user for documentary structure.
     * @return array Array of documentary structure doctrine entities.
     */
    public function getDocStructUser(?array $roles = null): array
    {
        if (!$this->user || !$this->user->getActive())
        {
            return array();
        }

        $docStructList = array();
        foreach ($this->userRoles as $userRoles)
        {
            if (!$roles || in_array($userRoles->getRole()->getId(), $roles))
            {
                $docStruct = $userRoles->getDocumentaryStructure();
                if ($docStruct)
                {
                    array_push($docStructList, $docStruct);
                }
            }
        }
        return $docStructList;
    }

    /**
     * Get physical libraries of current user.
     * @param array|null $roles Role of user for physical libraries.
     * @return array Array of physical libraries doctrine entities.
     */
    public function getPhysicLib(?array $roles = null): array
    {
        if (!$this->user || !$this->user->getActive())
        {
            return array();
        }

        $docStructList = $this->getDocStructUser($roles);
        return $this->doctrine->getRepository(PhysicalLibraries::class)
            ->findBy(array('documentaryStructure' => $docStructList));
    }

    /**
     * Return all user with same documentary structure of current user.
     * @return Users[]|array Array of user doctrine entities.
     */
    public function getAllLinkedUser(): array
    {
        if (!$this->user)
        {
            return array();
        }
        if  (!$this->user->getActive())
        {
            return array($this->user);
        }

        $docStruct = $this->getDocStructUser();
        $userArray = array();
        if ($docStruct)
        {
            $userRoles = $this->doctrine->getRepository(UserRoles::class)
                ->findBy(array('documentaryStructure' => $docStruct));
            foreach ($userRoles as $userRole)
            {
                $user = $userRole->getUser();
                if (!in_array($user, $userArray))
                {
                    array_push($userArray, $user);
                }
            }
        }
        return $userArray;
    }

    /**
     * Check if user are same documentary structure than current user.
     * @param Users $linkedUser User to compare.
     * @return bool True if user are linked, else false.
     */
    public function checkIfUserLinked(Users $linkedUser): bool
    {
        if (!$this->user || !$this->user->getActive())
        {
            return false;
        }

        $docStruct = $this->getDocStructUser();
        if ($docStruct)
        {
            $userRoles = $this->doctrine->getRepository(UserRoles::class)
                ->findBy(array('documentaryStructure' => $docStruct, 'user' => $linkedUser));
            return count($userRoles) > 0;
        }
        return false;
    }

    /**
     * Get current user information.
     * @return Users|null User doctrine entity.
     */
    public function getCurrentUser(): ?Users
    {
        return $this->user;
    }

    /**
     * Check if current user is DISTRD.
     * @return bool True if DISTRD, else false.
     */
    private function checkIsDISTRD(): bool
    {
        $authorized = false;
        $DISTRDAuthorized = false;
        foreach ($this->authorizedRoles as $authorizedRole)
        {
            if ($authorizedRole->getId() === Role::ADMIN)
            {
                $DISTRDAuthorized = true;
                break;
            }
        }

        if ($DISTRDAuthorized)
        {
            foreach ($this->userRoles as $userRole)
            {
                if ($userRole->getRole()->getId() === Role::ADMIN)
                {
                    $authorized = true;
                    break;
                }
            }
        }

        return $authorized;
    }

    /**
     * Check if user has unassociated role, like DISTRD.
     * @return bool True if user has unassociated role, else false.
     */
    private function matchUnassociatedRole(): bool
    {
        foreach ($this->userRoles as $userRole)
        {
            foreach ($this->authorizedRoles as $authorizedRole)
            {
                if ($userRole->getRole()->getId() === $authorizedRole->getId()
                    && !$userRole->getRole()->getAssociated())
                {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get documentary structure list from establishment, physical library and documentary structure parameter
     * @param DocumentaryStructures|null $docStruct Documentary structure doctrine entity.
     * @param Establishments|null $establishment Establishment doctrine entity.
     * @param PhysicalLibraries|null $physicLib Physical library doctrine entity.
     * @return array Array that contains all documentary structure associated with all administrations in parameter.
     */
    private function getDocStructList(?DocumentaryStructures $docStruct,
                                          ?Establishments $establishment,
                                          ?PhysicalLibraries $physicLib): array
    {
        $docStructList = array();

        if ($docStruct)
        {
            array_push($docStructList, $docStruct);
        }

        if ($establishment)
        {
            $estabList = $this->doctrine->getRepository(DocumentaryStructures::class)
                ->findBy(array('establishment' => $establishment));
            $docStructList = array_merge($estabList);
        }

        if ($physicLib)
        {
            array_push($docStructList, $physicLib->getDocumentaryStructure());
        }
        return $docStructList;
    }

}
