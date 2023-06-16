<?php


namespace App\Controller;

use App\Common\Exceptions\UserValidationsException;
use App\Common\Enum\Role;
use App\Common\Traits\UserRolesTrait;
use App\Common\Traits\UsersTrait;
use App\Common\Traits\UserValidationsTrait;
use App\Entity\UserRoleRequests;
use App\Entity\UserRoles;
use App\Entity\Users;
use App\Entity\UserValidations;
use App\Utils\StringTools;
use Exception;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use App\Controller\AbstractController\ESGBUController;

/**
 * Class UsersController
 * @package App\Controller
 * @SWG\Tag(name="Users")
 */
class UsersController extends ESGBUController
{
    use UserValidationsTrait,
        UsersTrait,
        UserValidationsTrait,
        UserRolesTrait;

    const FAKE_VALIDATION_TOKEN = 0000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000;

    /**
     * Show user information if authentication finished with success.
     * @SWG\Response(
     *     response="200",
     *     description="Array with all numbers information.",
     *     @Model(type=Users::class)
     * )
     * @Rest\Get(
     *      path = "/users/authent-success",
     *      name = "app_user_connect_success"
     * )
     * @Rest\View
     * @return View
     */
    public function connectSuccess() : View
    {
        $user = $this->getCurrentUser();
        $eppn = $this->getCurrentEppn();

        if ($user instanceof Users)
        {
            if ($this->isAnonymousUser() && $eppn)
            {
                $user->setEppn($eppn);
            }
            return $this->createView($this->addCsrfTokenInUser($user), Response::HTTP_OK);
        }
        return $this->createView('Connection success : No user information.', Response::HTTP_OK);
    }

    /**
     * Add csrf token to user object.
     * @param Users $user User doctrine entity.
     * @return Users User with csrf token.
     */
    private function addCsrfTokenInUser(Users $user): Users
    {
        $user->csrfToken = $this->csrfTokenManager->getToken($user->getEppn());
        return $user;
    }

    /**
     * Show if user authenticate failed.
     * @SWG\Response(
     *     response="200",
     *     description="Connection failure"
     * )
     * @Rest\Get(
     *      path = "/users/authent-failure",
     *      name = "app_user_connect_failure"
     * )
     * @Rest\View
     * @return View
     */
    public function connectFailure() : View
    {
        return $this->createView('Connection failure', Response::HTTP_NOT_FOUND);
    }

    /**
     * Send new confirmation mail.
     * @SWG\Response(
     *     response="200",
     *     description="Mail send."
     * )
     * @SWG\Response(response="400", description="Error to send mail.")
     * @Rest\Get(
     *      path = "/users/send-confirmation-mail",
     *      name = "app_user_send_confirmation_mail"
     * )
     * @Rest\View
     * @return View
     */
    public function sendConfirmationMailAction(): View
    {
        try
        {
            $user = $this->getCurrentUserDoctrineEntity();
            if ($user->getValid())
            {
                return $this->createView('User already valid.', Response::HTTP_OK, true);
            }

            $userValidation = $this->getUserValidationByUser($user);
            $em = $this->managerRegistry->getManager();
            $em->remove($userValidation);
            $em->flush();
            return $this->sendConfirmationMailView($user);
        }
        catch(UserValidationsException $e)
        {
            if (isset($user) && $e->getCode() === Response::HTTP_NOT_FOUND)
            {
                return $this->sendConfirmationMailView($user);
            }
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
        return $this->createView('Error to create and send confirmation mail.',
            Response::HTTP_BAD_REQUEST, true);
    }

    /**
     * URL validation for user.
     * @SWG\Response(
     *     response="302",
     *     description="User validated. Go to APP url"
     * )
     * @SWG\Response(response="400", description="Token not valid.")
     * @SWG\Response(response="404", description="No validation found for this user.")
     * @SWG\Parameter(name="mail",type="string", in="query", description="New mail to update for user.")
     * @SWG\Parameter(name="return",type="string", in="query",
     *     description="Return url after confirm user or update mail.")
     * @SWG\Parameter(name="return",type="string", in="query",
     *     description="CSRF token of DISTRD user who wants force user validation.")
     *
     * @Rest\Get(
     *      path = "public/users/confirm/{token}",
     *      name = "app_user_confirm",
     *      requirements={"token"="^[a-zA-Z0-9]{100}$"}
     * )
     * @Rest\QueryParam(name="mail", nullable=true)
     * @Rest\QueryParam(name="userId", nullable=true)
     * @Rest\QueryParam(name="return", requirements="^(true|false)$", default="true", nullable=true)
     * @Rest\QueryParam (name="csrfToken", nullable=true)
     *
     * @param string $token Token in url into mail.
     * @param string|null $mail New user mail to update after confirm.
     * @param string|null $userId User id of token.
     * @param string $return True to return to app url after confirm, else false.
     * @param string|null $csrfToken CSRF token of DISTRD user who wants force user validation.
     * @return View|RedirectResponse Return view if return parameter is false, else redirect to app url.
     */
    public function confirmAction(string $token, ?string $mail, ?string $userId, string $return, ?string $csrfToken)
    {
        try
        {
            $DISTRD = $this->checkRightsBool([Role::ADMIN]);
            $isFakeToken = $token == self::FAKE_VALIDATION_TOKEN;
            $force = $DISTRD && $isFakeToken;

            if ($force)
            {
                $this->checkCsrfTokenValidation($csrfToken);
            }

            $return = StringTools::stringToBool($return);

            $doctrine = $this->managerRegistry;
            $em = $doctrine->getManager();
            $validationRepo = $doctrine->getRepository(UserValidations::class);

            $validationRepo->deleteOlder();
            $user = $this->getUserById($userId);
            $userValidation = null;
            if (!$force)
            {
                $userValidation = $this->getUserValidationByUser($user);
            }

            $returnUrl = $this->appUrl . '/users/';

            if ($force || $userValidation->getToken() === $token)
            {
                $user->setValid(true);

                if ($userValidation != null)
                {
                    $em->remove($userValidation);
                }

                if ($mail)
                {
                    $user->setMail($mail);
                    $userRoles = $this->managerRegistry->getRepository(UserRoles::class)
                        ->findBy(['user' => $user]);
                    if (count($userRoles) > 0) {
                        $returnUrl .= 'confirm-mail';
                    }
                }
                else
                {
                    $returnUrl .= 'valid-user';
                }

                $em->flush();
                $this->updateCurrentUser($user);

                if ($return)
                {
                    return new RedirectResponse($returnUrl);
                }
                    return $this->createView($user, Response::HTTP_OK);
            }

            if ($return)
            {
                return new RedirectResponse($this->appUrl . 'error-confirm-mail');
            }
            return $this->createView('Token not valid.', Response::HTTP_BAD_REQUEST, true);

        }
        catch (Exception $e)
        {
            if ($return)
            {
                return new RedirectResponse($this->appUrl . 'error-confirm-mail');
            }
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }

    /**
     * Show all users information.
     * @SWG\Response(
     *     response="200",
     *     description="Array with all users.",
     *     @SWG\Schema(type="array", @SWG\Items(ref=@Model(type=Users::class)))
     * )
     * @SWG\Response(
     *     response="404",
     *     description="No user found.",
     * )
     * @Rest\Get(
     *      path = "/users",
     *      name = "app_users_list"
     * )
     * @Rest\View
     * @return View Array with all users information.
     */
    public function listAction() : View
    {
        $distrdOrAdminOk = $this->checkRightsBool(
            [Role::ADMIN, Role::ADMIN_RO, Role::VALID_SURVEY_RESP, Role::SURVEY_ADMIN],
        null, null, null, false);

        if ($distrdOrAdminOk)
        {
            $users = $this->managerRegistry->getRepository(Users::class)->findAll();
        }
        else
        {
            $users = $this->getAllLinkedUser([Role::USER]);
        }

        $i = 0;
        foreach ($users as $user)
        {
            if ($user->getId() === 1)
            {
                unset($users[$i]);
                $users = array_values($users);
                break;
            }
            $i++;
        }

        if (!$users)
        {
            return $this->createView('No user found.', Response::HTTP_NOT_FOUND);
        }
        return $this->createView($users, Response::HTTP_OK);
    }


    /** Show user by eppn.
     * @SWG\Response(
     *     response="200",
     *     description="Return user select by eppn.", @Model(type=Users::class)
     * )
     * @SWG\Response(
     *     response="404",
     *     description="No user found.",
     * )
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="id",type="string", in="path", description="User id.")
     * @Rest\Get(
     *      path = "/users/{id}",
     *      name = "app_users_show",
     *      requirements={"id"="\d+"}
     * )
     * @Rest\View
     * @param int $id Id user.
     * @return View User information.
     */
    public function showAction(int $id) : View
    {
        $currentUser = $this->getCurrentUser();
        $rightOk = false;
        if ($currentUser && $currentUser->getId() === $id)
        {
            $rightOk = true;
        }
        if (!$rightOk)
        {
            $distrdOrAdminOk = $this->checkRightsBool(
                [Role::ADMIN, Role::ADMIN_RO, Role::VALID_SURVEY_RESP, Role::SURVEY_ADMIN],
                null, null, null, false);
            if ($distrdOrAdminOk)
            {
                $rightOk = true;
            }
        }

        $user = $this->managerRegistry->getRepository(Users::class)->find($id);
        if (!$user || $user->getId() === 1)
        {
            return $this->createView('No user with id : ' . $id, Response::HTTP_NOT_FOUND);
        }

        if (!$rightOk)
        {
            $rightOk = $this->checkIfUserLinked([Role::USER], $user);
        }

        if (!$rightOk)
        {
            return $this->createView(self::FORBIDDEN_ERROR, Response::HTTP_FORBIDDEN);
        }

        return $this->createView($user, Response::HTTP_CREATED);
    }

    /**
     * Create new user.
     * @SWG\Response(
     *     response="201",
     *     description="Create a user.",
     *     @Model(type=Users::class)
     * )
     * @SWG\Response(response="404", description="No user found.")
     * @SWG\Response(response="400", description="Bad request. Body not valid.")
     * @SWG\Response(response="409", description="User already exists with this eppn.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="body", in="body", description="Group informations.", @Model(type=Users::class)))
     * @Rest\Post(path="/users", name="app_users_create")
     * @Rest\View
     * @ParamConverter("user", converter="fos_rest.request_body")
     * @param Users $user User set in body request.
     * @param ConstraintViolationListInterface $validationErrors Assert violation list.
     * @return View User has just been created.
     */
    public function createAction(Users $user, ConstraintViolationListInterface $validationErrors) : View
    {
        try
        {
            if (count($validationErrors) > 0)
            {
                return $this->createView($validationErrors, Response::HTTP_BAD_REQUEST, true);
            }

            $this->checkIfEppnAlreadyExists($user->getEppn());

            $rightsOK = $this->checkRightsBool([Role::ADMIN, Role::VALID_SURVEY_RESP, Role::SURVEY_ADMIN],
                null, null, null, false);
            if (!$rightsOK)
            {
                if ($user->getEppn() != $this->getCurrentEppn())
                {
                    return $this->createView(self::FORBIDDEN_ERROR, Response::HTTP_FORBIDDEN, true);
                }
            }

            $em = $this->managerRegistry->getManager();
            $em->persist($user);
            $em->flush();

            $this->sendConfirmationMail($user);

            // For first connection : change current user.
            if ($user->getEppn() === $this->getCurrentEppn())
            {
                $this->updateCurrentUser($user);
            }

            return $this->createView($user, Response::HTTP_CREATED, true);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode());
        }
    }


    /**
     * Update a user.
     * @SWG\Response(
     *     response="200",
     *     description="Update a user selected by eppn.",
     *     @Model(type=Users::class)
     * )
     * @SWG\Response(response="404", description="Data missing in database")
     * @SWG\Response(response="400", description="Error to update user. Body not valid. Anonymous can't be updated.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Response(response="409", description="Eppn already exists. Body not valid.")
     * @SWG\Parameter(name="id", type="integer", in="path", description="Id of user to update.")
     * @SWG\Parameter(name="body", in="body", description="User informations.", @Model(type=Users::class))
     * @Rest\Put(
     *     path="/users/{id}",
     *     name="app_users_update",
     *     requirements={"id"="\d+"}
     * )
     * @Rest\View
     * @ParamConverter("user", converter="fos_rest.request_body", options={"mapping":"none"})
     * @param int $id Id of user to update.
     * @param Users $user New user information in body.
     * @param ConstraintViolationListInterface $validationErrors Assert violations list.
     * @return View User has just been updated.
     */
    public function updateAction(int $id, Users $user, ConstraintViolationListInterface $validationErrors) : View
    {
        try
        {
            $distrdOk = $this->checkRightsBool([Role::ADMIN]);
            if (!$distrdOk)
            {
                $currentUser = $this->getCurrentUser();
                if ($currentUser && $currentUser->getId() != $id)
                {
                    return $this->createView(self::FORBIDDEN_ERROR, Response::HTTP_FORBIDDEN, true);
                }
            }

            if (count($validationErrors) > 0)
            {
                return $this->createView($validationErrors, Response::HTTP_BAD_REQUEST, true);
            }

            if ($id == 1)
            {
                return $this->createView('Anonymous can\'t be updated.',
                    Response::HTTP_BAD_REQUEST, true);
            }

            $doctrine = $this->managerRegistry;
            $existingUser = $this->getUserById($id);

            $existingUser->update($user);

            if ($existingUser->getMail() !== $user->getMail())
            {
                $userValidation = $doctrine->getRepository(UserValidations::class)
                    ->findOneBy(array('user' => $existingUser));
                if ($userValidation)
                {
                    $doctrine->getManager()->remove($userValidation);
                    $doctrine->getManager()->flush();
                }
                $this->sendConfirmationMail($existingUser, $user->getMail());
            }

            $doctrine->getManager()->flush();

            // For first connection : change current user.
            if ($user->getEppn() === $this->getCurrentEppn())
            {
                $this->updateCurrentUser($existingUser);
            }

            return $this->createView($existingUser, Response::HTTP_OK, true);
        }
        catch (Exception $e)
        {
            return $this->createView($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Delete user.
     * @SWG\Response(response="204", description="User deleted.")
     * @SWG\Response(response="404", description="User not found.")
     * @SWG\Response(response="403", description="Not authorized.")
     * @SWG\Parameter(name="userId",type="integer", in="path", description="User id.")
     * @Rest\Delete(
     *      path="/users/{userId}",
     *      name="app_users_delete",
     * )
     * @Rest\View
     * @param int $userId User id.
     * @return View Information about action.
     */
    public function deleteAction(int $userId) : View
    {
        try
        {
            $isDISTRD = $this->checkRightsBool([Role::ADMIN]);

            $user = $this->getUserById($userId);

            $doctrine = $this->managerRegistry;
            $em = $doctrine->getManager();
            $roles = $doctrine->getRepository(UserRoles::class)->findBy(array('user' => $user));

            if (!$isDISTRD)
            {
                if (count($roles) === 0)
                {
                    $this->checkRights([Role::VALID_SURVEY_RESP, Role::SURVEY_ADMIN],
                        null, null, null, false);
                }
                else
                {
                    foreach ($roles as $role)
                    {
                        $this->checkRights([Role::VALID_SURVEY_RESP, Role::SURVEY_ADMIN],
                            $role->getDocumentaryStructure());
                    }
                }
            }

            if ($userId == 1)
            {
                return $this->createView('Anonymous can\'t be removed.',
                    Response::HTTP_BAD_REQUEST, true);
            }

            foreach ($roles as $role)
            {
                $em->remove($role);
            }

            $roleRequests = $doctrine->getRepository(UserRoleRequests::class)
                ->findBy(array('user' => $user));
            foreach ($roleRequests as $roleRequest)
            {
                $em->remove($roleRequest);
            }

            $validations = $doctrine->getRepository(UserValidations::class)
                ->findBy(array('user' => $user));
            foreach ($validations as $validation)
            {
                $em->remove($validation);
            }

            $em->remove($user);
            $em->flush();

            return $this->createView('User and his role have been deleted.',
                Response::HTTP_NO_CONTENT, true);
        }
        catch(Exception $e)
        {
            return $this->createView($e->getMessage(), $e->getCode(), true);
        }
    }

    // Mail part ///////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * Send confirmation mail to user with link to valid user.
     * @param Users $user User for who the validation in created.
     * @param string|null $newMail Optional, to update user mail.
     * @param bool $returnApp Optional, add return parameter in address validation in mail.
     * @return int Number of success delivery. (O could be a failure)
     * @throws Exception Error in token creation.
     */
    private function sendConfirmationMail(Users $user, string $newMail = null, bool $returnApp = true): int
    {

        $userValidation = new UserValidations($user);
        $em = $this->managerRegistry->getManager();
        $em->persist($userValidation);
        $em->flush();

        $userName = $user->getUsername();
        $confirmationUrl = $this->apiUrl . 'public/users/confirm/' . $userValidation->getToken();
        $confirmationUrl .= '?userId=' . $user->getId();

        if ($newMail)
        {
            $mail = $newMail;
            $separator = StringTools::getUrlParameterSeparator($confirmationUrl);
            $confirmationUrl .= $separator . 'mail=' . $mail;
        }
        else
        {
            $mail = $user->getMail();
        }

        if (!$returnApp)
        {
            $separator = StringTools::getUrlParameterSeparator($confirmationUrl);
            $confirmationUrl .= $separator . 'return=false';
        }

        if ($newMail)
        {
            $message = $this->createUpdateMailMessage($mail, $userName, $confirmationUrl);
        }
        else
        {
            $message = $this->createNewAccountCreationMessage($mail, $userName, $confirmationUrl);
        }

        $i = 0;
        $nbrSuccess = 0;

        while ($nbrSuccess === 0 && $i < 8) {
            set_time_limit(30);
            try {
                $this->mailer->send($message);
                $nbrSuccess = 1;
                $this->logger->info("Mail envoyé à $mail");
            } catch (TransportExceptionInterface $e) {
                $this->logger->error($e->getMessage());
                $i++;
            }
        };
        return $nbrSuccess;
    }

    /**
     * Send confirmation mail, send view to show result of action.
     * @param Users $user User for who the validation in created.
     * @return View
     */
    private function sendConfirmationMailView(Users $user): View
    {
        try
        {
            if ($this->sendConfirmationMail($user) === 0) {
                throw new Exception();
            }
            return $this->createView('Mail send.', Response::HTTP_OK, true);
        }
        catch (Exception $e)
        {
            return $this->createView('Error to send Mail.', Response::HTTP_BAD_REQUEST, true);
        }
    }

    /**
     * Mail message when update mail address of user.
     * @param string $mailRecipient The mail is send to this mail.
     * @param string $userName User name show in mail.
     * @param string $confirmationUrl Url for confirm user.
     * @return Email Mail object to send with swiftMailer.
     */
    private function createUpdateMailMessage(string $mailRecipient, string $userName, string $confirmationUrl)
    : Email
    {
        return (new Email())
            ->subject('[ESGBU] Confirmation de modification d\'adresse mail')
            ->from($_ENV['MAIL_SENDER'])
            ->to($mailRecipient)
            ->html(
                $this->renderView('mailUpdate.html.twig',
                    ['userName' => $userName, 'confirmationUrl' => $confirmationUrl]
                )
            );
    }

    /**
     * Mail message when create user account user.
     * @param string $mailRecipient The mail is send to this mail.
     * @param string $userName User name show in mail.
     * @param string $confirmationUrl Url for confirm user.
     * @return Email Mail object to send with swiftMailer.
     */
    private function createNewAccountCreationMessage(string $mailRecipient, string $userName, string $confirmationUrl)
    : Email
    {
        return (new Email())
            ->subject('[ESGBU] Confirmation de création d\'utilisateurs')
            ->from($_ENV['MAIL_SENDER'])
            ->to($mailRecipient)
            ->html(
                $this->renderView('mailRegistration.html.twig',
                    ['userName' => $userName, 'confirmationUrl' => $confirmationUrl]
                )
            );
    }

}
