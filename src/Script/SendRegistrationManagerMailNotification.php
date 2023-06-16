<?php


namespace App\Script;

use App\Common\Enum\Role;
use App\Common\Traits\RolesTrait;
use App\Common\Traits\UserRoleRequestsTrait;
use App\Common\Traits\UserRolesTrait;
use App\Common\Traits\UsersTrait;
use App\Entity\Roles;
use App\Entity\UserRoles;
use App\Entity\Users;
use Exception;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class SendRegistrationManagerMailNotification extends Command
{
    use UsersTrait,
        UserRoleRequestsTrait,
        UserRolesTrait,
        RolesTrait;

    /**
     * @var string
     */
    protected static $defaultName = "app:send-registration-manager-mail-notification";

    const USER_ID_PARAM = 'userId';

    /**
     * @var \Doctrine\Persistence\ManagerRegistry Doctrine entity manager
     */
    private $managerRegistry;

    /**
     * @var MailerInterface To Send mail.
     */
    private $mailer;

    /**
     * @var Environment Twig to render html mail body.
     */
    private $twig;

    protected function configure()
    {
        $this->setDescription('Send registration mail notification to administrator or documentary structure manager')
            ->addArgument(
                self::USER_ID_PARAM,
                InputArgument::REQUIRED,
                'Id of user has just been created'
            );
    }

     public function __construct(ManagerRegistry $managerRegistry, MailerInterface $mailer, Environment $twig,
                                string $name = null)
    {
        parent::__construct($name);

        $this->mailer = $mailer;
        $this->managerRegistry = $managerRegistry;
        $this->twig = $twig;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try
        {
            $userId = $input->getArgument(self::USER_ID_PARAM);
            $user = $this->getUserById($userId);

            $userRoleRequest = $this->getUserRoleRequestByUser($user)[0];
            $userRoleId = $userRoleRequest->getRole()->getId();

            $mailList = [];

            $distrdRole = $this->getRoleById(Role::ADMIN);
            $this->addMailToList($mailList, $this->getUserRoleByCriteria($distrdRole));

            if ($userRoleId === Role::SURVEY_ADMIN || $userRoleId === Role::USER)
            {
                $docStruct = $userRoleRequest->getDocumentaryStructure();

                $validSurveyRespRole = $this->getRoleById(Role::VALID_SURVEY_RESP);
                $this->addMailToList($mailList, $this->getUserRoleByCriteria($validSurveyRespRole, $docStruct));

                if ($userRoleId === Role::USER)
                {
                    $surveyAdminRole = $this->getRoleById(Role::SURVEY_ADMIN);
                    $this->addMailToList($mailList, $this->getUserRoleByCriteria($surveyAdminRole, $docStruct));
                }
            }

            $this->sendMails($mailList, $user, $userRoleRequest->getRole());
        }
        catch (Exception $e)
        {
            print('ERROR 3: ' . $e->getMessage() . "\n");
            return 1;
        }
        return 0;
    }

    /**
     * @param UserRoles[] $userRoles
     */
    private function addMailToList(array& $mailList, array $userRoles)
    {
        foreach ($userRoles as $userRole)
        {
            $mail = $userRole->getUser()->getMail();
            if (!in_array($mail, $mailList))
            {
                print($userRole->getUser()->getEppn() . "\n");
                array_push($mailList, $mail);
            }
        }
    }

    /**
     * @param string[] $mailList
     * @param Users $user
     * @param Roles $role
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function sendMails(array $mailList, Users $user, Roles $role)
    {
        $body = $this->twig->render('mailRegistrationManagerNotification.html.twig',
            [
                'userName' => $user->getFirstname() . ' ' . $user->getLastname(),
                'eppn' => $user->getEppn(),
                'mail' => $user->getMail(),
                'roleName' => $this->getFrenchRoleName($role),
                'appUrl' => $_ENV['APP_URL']
            ]);

        foreach ($mailList as $mailRecipient)
        {
            $message = (new Email())
                ->subject('[ESGBU] Notification de demande de rôle')
                ->from($_ENV['MAIL_SENDER'])
                ->to($mailRecipient)
                ->html($body, 'text/html');
            $i = 0;
            $nbrSuccess = 0;

            while ($nbrSuccess === 0 && $i < 8)
            {
                set_time_limit(30);
                $nbrSuccess = $this->mailer->send($message);
                $i++;
            }
        }
    }

    private function getFrenchRoleName(Roles $role): string
    {
        switch ($role->getId())
        {
            case Role::ADMIN_RO:
                return 'DISTRD invité';
            case Role::VALID_SURVEY_RESP:
                return 'Responsable de structure';
            case Role::SURVEY_ADMIN:
                return 'Responsable d\'enquête';
            case Role::USER:
                return 'Responsable de saisie';
            default:
                return $role->getName();
        }
    }
}
