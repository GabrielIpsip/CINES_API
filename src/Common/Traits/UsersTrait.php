<?php

namespace App\Common\Traits;

use App\Entity\Users;
use App\Security\ShibbolethAuthenticator;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

trait UsersTrait
{

    /**
     * @param int $id User id.
     * @return Users User associated with this id.
     * @throws Exception No user found with this id.
     */
    private function getUserById(int $id): Users
    {
        $user = $this->managerRegistry->getRepository(Users::class)->find($id);
        if (!$user)
        {
            throw new Exception('No user with this id : ' . $id, Response::HTTP_NOT_FOUND);
        }
        return $user;
    }

    /**
     * Check if user with this eppn already exists in database.
     * @param string $eppn Eppn to check.
     * @param int|null $userId Except this user with this id in check verification.
     * @throws Exception 409 : User with this eppn already exists.
     */
    private function checkIfEppnAlreadyExists(string $eppn, ?int $userId = null)
    {
        $userSameEppn = $this->managerRegistry->getRepository(Users::class)->findOneBy(array('eppn' => $eppn));
        if ($userSameEppn && $userSameEppn->getId() != $userId)
        {
            throw new Exception('User with this eppn already exists.', Response::HTTP_CONFLICT);
        }
    }

    // Current user part ///////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @var SessionInterface Contain current user information.
     */
    protected $session;

    protected function getCurrentUser(): ?Users
    {
        return $this->session->get(ShibbolethAuthenticator::SHIB_USER_SESSION_INDEX);
    }

    protected function getCurrentEppn(): ?string
    {
        return $this->session->get(ShibbolethAuthenticator::SHIB_EPPN_SESSION_INDEX);
    }

    protected function updateCurrentUser(Users $user)
    {
        $this->session->set(ShibbolethAuthenticator::SHIB_USER_SESSION_INDEX, $user);
    }

    /**
     * @return Users Current user doctrine entity.
     * @throws Exception 404 : No user found.
     */
    protected function getCurrentUserDoctrineEntity(): Users
    {
        $user = $this->getCurrentUser();
        return $this->getUserById($user->getId());
    }

    public function isAnonymousUser(): bool
    {
        $currentUser = $this->getCurrentUser();
        return $currentUser && $currentUser->getId() === 1;
    }
}