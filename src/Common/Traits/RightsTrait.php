<?php


namespace App\Common\Traits;


use App\Entity\DocumentaryStructures;
use App\Entity\Establishments;
use App\Entity\PhysicalLibraries;
use App\Entity\Users;
use App\Security\RightsChecker;
use Exception;
use Symfony\Component\HttpFoundation\Response;

trait RightsTrait
{
    /**
     * @var RightsChecker
     */
    private $rights;

    /**
     * @param array $roles
     * @param DocumentaryStructures|null $docStruct
     * @param Establishments|null $establishment
     * @param PhysicalLibraries|null $physicLib
     * @param bool $associated
     * @throws Exception
     */
    public function checkRights(array $roles,
                                ?DocumentaryStructures $docStruct = null,
                                ?Establishments $establishment = null,
                                ?PhysicalLibraries $physicLib = null,
                                bool $associated = true)
    {
        if (!self::SECURITY)
        {
            return;
        }

        $this->initRights($roles);
        $authorized = $this->rights->checkRights($docStruct, $establishment, $physicLib, $associated);

        if (!$authorized)
        {
            throw new Exception(self::FORBIDDEN_ERROR, Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * @param array $roles
     * @param int|null $docStructId
     * @param int|null $establishmentId
     * @param int|null $physicLibId
     * @param bool $associated
     * @throws Exception
     */
    public function checkRightsById(array $roles,
                                    ?int $docStructId = null,
                                    ?int $establishmentId = null,
                                    ?int $physicLibId = null,
                                    bool $associated = true)
    {
        if (!self::SECURITY)
        {
            return;
        }

        $this->initRights($roles);
        $authorized = $this->rights->checkRightsById($docStructId, $establishmentId, $physicLibId, $associated);

        if (!$authorized)
        {
            throw new Exception(self::FORBIDDEN_ERROR, Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * @param array $roles
     * @param DocumentaryStructures|null $docStruct
     * @param Establishments|null $establishment
     * @param PhysicalLibraries|null $physicLib
     * @param bool $associated
     * @return bool
     */
    public function checkRightsBool(array $roles,
                                    ?DocumentaryStructures $docStruct = null,
                                    ?Establishments $establishment = null,
                                    ?PhysicalLibraries $physicLib = null,
                                    bool $associated = true): bool
    {
        if (!self::SECURITY)
        {
            return true;
        }

        $this->initRights($roles);
        return $this->rights->checkRights($docStruct, $establishment, $physicLib, $associated);
    }

    /**
     * @param array $roles
     * @param int|null $docStructId
     * @param int|null $establishmentId
     * @param int|null $physicLibId
     * @param bool $associated
     * @return bool
     */
    public function checkRightsBoolById(array $roles,
                                        ?int $docStructId = null,
                                        ?int $establishmentId = null,
                                        ?int $physicLibId = null,
                                        bool $associated = true): bool
    {
        if (!self::SECURITY)
        {
            return true;
        }

        $this->initRights($roles);
        return $this->rights->checkRightsById($docStructId, $establishmentId, $physicLibId, $associated);
    }

    public function getDocStructUser(array $roles): array
    {
        if (self::SECURITY)
        {
            $this->initRights($roles);
            return $this->rights->getDocStructUser($roles);
        }
        return $this->managerRegistry->getRepository(DocumentaryStructures::class)->findAll();
    }

    public function getAllLinkedUser(array $roles): array
    {
        if (self::SECURITY)
        {
            $this->initRights($roles);
            return $this->rights->getAllLinkedUser();
        }
        return $this->managerRegistry->getRepository(Users::class)->findAll();
    }

    public function checkIfUserLinked(array $roles, Users $linkedUser): bool
    {
        if (!self::SECURITY)
        {
            return true;
        }
        $this->initRights($roles);
        return $this->rights->checkIfUserLinked($linkedUser);
    }

    private function initRights(array $roles)
    {
        if (!$this->rights)
        {
            $this->rights = new RightsChecker($this->managerRegistry, $this->session, $roles);
        }
        else
        {
            $this->rights->updateAuthorizedRole($roles);
        }
    }
}