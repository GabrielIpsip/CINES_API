<?php


namespace App\Common\Traits;


use App\Common\Enum\Role;
use App\Entity\Roles;
use App\Entity\UserRoleRequests;
use App\Entity\Users;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;

trait UserRoleRequestsTrait
{
    /**
     * Get userRoleRequest in database by id.
     * @param int $id Id of userRoleRequest.
     * @return UserRoleRequests UserRoleRequest doctrine entity.
     * @throws Exception 404 : No userRoleRequest with this id.
     */
    private function getUserRoleRequestById(int $id): UserRoleRequests
    {
        $userRoleRequest = $this->managerRegistry->getRepository(UserRoleRequests::class)->find($id);
        if (!$userRoleRequest)
        {
            throw new Exception('No user role request with this id: ' . $id,
                Response::HTTP_NOT_FOUND);
        }
        return $userRoleRequest;
    }

    /**
     * Get userRoleRequest in database by user.
     * @param Users $user User of role request.
     * @return UserRoleRequests[] UserRoleRequest doctrine entity.
     * @throws Exception 404 : No userRoleRequest with this user.
     */
    private function getUserRoleRequestByUser(Users $user): array
    {
        $userRoleRequest = $this->managerRegistry->getRepository(UserRoleRequests::class)->findBy(['user' => $user]);
        if (!$userRoleRequest)
        {
            throw new Exception('No user role request with this user.',
                Response::HTTP_NOT_FOUND);
        }
        return $userRoleRequest;
    }

    /**
     * Get all request for current user.
     * @return array Array with all userRoleRequest doctrine entity associated with current user.
     * @throws Exception 404 : No user role request found.
     */
    private function getAllRequestForUser(): array
    {
        $doctrine = $this->managerRegistry;
        $distrdOK = $this->checkRightsBool([Role::ADMIN, Role::ADMIN_RO]);

        if ($distrdOK)
        {
            $userRoleRequests = $doctrine->getRepository(UserRoleRequests::class)->findAll();
        }
        else
        {
            $SDAdminDocStructs = $this->getDocStructUser([Role::VALID_SURVEY_RESP]);
            $surveyAdminDocStructs = $this->getDocStructUser([Role::SURVEY_ADMIN]);

            $userRoleRequests = $doctrine->getRepository(UserRoleRequests::class)
                ->findBy(array('documentaryStructure' => $SDAdminDocStructs));

            $userRole = $doctrine->getRepository(Roles::class)->find(Role::USER);
            if (!$userRole)
            {
                throw new Exception('Role ' . Role::USER . ' not found.', Response::HTTP_NOT_FOUND);
            }

            $surveyAdminUserRole = $doctrine->getRepository(UserRoleRequests::class)
                ->findBy(array('documentaryStructure' => $surveyAdminDocStructs, 'role' => $userRole));

            foreach ($surveyAdminUserRole as $userRoleRequest)
            {
                if (!in_array($userRoleRequest, $userRoleRequests))
                {
                    array_push($userRoleRequests, $userRoleRequest);
                }
            }
        }

        return $userRoleRequests;
    }

    /**
     * Remove all request with not valid user in this array.
     * @param array $userRoleRequests Array that contains userRoleRequest doctrine entity.
     * @return array Return filtered with just valid user.
     */
    private function removeNotValidUser(array $userRoleRequests): array
    {
        $userRequestFiltered = [];
        $i = 0;
        foreach ($userRoleRequests as $userRoleRequest)
        {
            if ($userRoleRequest->getUser()->getValid())
            {
                array_push($userRequestFiltered, $userRoleRequest);
            }
            $i++;
        }

        return $userRequestFiltered;
    }

    /**
     * Send notification of role request creation for administrator or structure manager.
     * @param int $userId Id of user which send request.
     */
    private function notifyRegistrationToManagerByMail(int $userId)
    {
        $sendRegistrationMailCommand = 'php ../bin/console app:send-registration-manager-mail-notification ' . $userId
            . ' > /tmp/send-registration-manager-mail-notification-log.txt'
            . ' 2> /tmp/send-registration-manager-mail-notification-error-log.txt &';

        $process = Process::fromShellCommandline($sendRegistrationMailCommand);
        $process->run();
    }
}
