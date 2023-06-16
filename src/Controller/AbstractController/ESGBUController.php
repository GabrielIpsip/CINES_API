<?php

namespace App\Controller\AbstractController;

use App\Common\Traits\RightsTrait;
use App\Common\Traits\TranslationsTrait;
use App\Common\Traits\UsersTrait;
use App\Security\ShibbolethAuthenticator;
use App\Utils\StringTools;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Psr\Log\LoggerInterface;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Class ESGBUController
 * @package App\Controller
 */
abstract class ESGBUController extends AbstractFOSRestController
{
    use UsersTrait,
        RightsTrait,
        TranslationsTrait;

    /**
     * @var string Default lang of ESGBU API.
     */
    public const DEFAULT_LANG = 'fr';

    /**
     * @var string Message to show when there is authorization error.
     */
    protected const FORBIDDEN_ERROR = 'Not authorized.';

    /**
     * @var bool Enable or disable right checker.
     */
    private const SECURITY = true;

    /**
     * @var bool Enable or disable logs.
     */
    private const LOGGING = true;

    /**
     * @var LoggerInterface For write log.
     */
    protected $logger;

    /**
     * @var MailerInterface To Send mail.
     */
    protected $mailer;

    /**
     * @var mixed|string Url API end with '/'.
     */
    protected $apiUrl;

    /**
     * @var string App url end with '/'.
     */
    protected $appUrl;

    /**
     * @var HubInterface To send message with Mercure.
     */
    protected $publisher;

    /**
     * @var CsrfTokenManagerInterface
     */
    protected $csrfTokenManager;

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    public function __construct(LoggerInterface $logger,
                                SessionInterface $session,
                                MailerInterface $mailer,
                                HubInterface $publisher,
                                CsrfTokenManagerInterface $csrfTokenManager,
                                RequestContext $request,
                                ManagerRegistry $managerRegistry
    )
    {
        $this->logger = $logger;
        $this->session = $session;
        $this->mailer = $mailer;
        $this->publisher = $publisher;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->managerRegistry = $managerRegistry;

        $this->apiUrl = str_ends_with($_ENV['API_URL'], '/') ? $_ENV['API_URL'] : $_ENV['API_URL'] . '/';
        $this->appUrl = str_ends_with($_ENV['APP_URL'], '/') ? $_ENV['APP_URL'] : $_ENV['APP_URL'] . '/';

        $this->checkCsrfSecurity($request);
    }

    /**
     * Create API view response.
     * @param null $data Content of response.
     * @param int|null $statusCode HTTP status code of response.
     * @param bool $log True to write log in action.log, else false.
     * @param array $headers Headers to add to the response.
     * @return View
     */
    protected function createView($data = null, int $statusCode = null, bool $log = false, array $headers = []): View
    {
        if ($statusCode === 0) {
            $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        if ($log)
        {
            $this->writeLog($data, $statusCode);
        }
        return View::create($data, $statusCode, $headers);
    }

    /**
     * Write log in action.log
     * @param mixed $response Body of response of API.
     * @param mixed $codeResponse HTTP code response of API.
     */
    private function writeLog($response, $codeResponse)
    {
        if (!self::LOGGING)
        {
            return;
        }

        $logDetails['args'] = 'ERROR TO GET ARGUMENTS';
        $logDetails['code'] = $codeResponse;
        $logDetails['response'] = $response;

        $trace = debug_backtrace();
        if (count($trace) > 0)
        {
            $trace = $trace[2];
            $logDetails['args']  = $trace['args'];
            $message = $trace['class'] . ' : ' . $trace['function'];
        }
        else
        {
            $message = 'ERROR TO GET DEBUG BACKTRACE';
        }

        if ($this->session)
        {
            $user = $this->getCurrentUser();
            if (!$user)
            {
                $logDetails['$user'] = 'ERROR TO GET USER';
            }
            else
            {
                $userArray = $user->toArray();
                $logDetails['$user']['id'] = $userArray['id'];
                $logDetails['$user']['eppn'] = $userArray['eppn'];
            }
        }

        $this->logger->notice($message,$logDetails);
    }

    /**
     * Test mail configuration.
     * @SWG\Response(
     *     response="200",
     *     description="Mail send."
     * )
     * @SWG\Response(response="400", description="Error to send mail.")
     * @Rest\Get(
     *      path = "/test-mail/{mailSender}/{mailRecipient}",
     *      name = "app_test_mail",
     *     requirements={"mailSender"="[a-zA-Z.]+@[a-zA-Z.]+", "mailRecipient"="[a-zA-Z.]+@[a-zA-Z.]+"}
     * )
     * @Rest\View
     * @param string $mailSender
     * @param string $mailRecipient
     * @return View
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function sendTestMail(string $mailSender, string $mailRecipient): View
    {
        $mail = (new Email())
            ->subject('[ESGBU] Confirmation de modification d\'adresse mail')
            ->from($mailSender)
            ->to($mailRecipient)
            ->html(
                $this->renderView('mailUpdate.html.twig',
                    ['userName' => 'test', 'confirmationUrl' => 'test']
                )
        );

        $this->mailer->send($mail);
        return $this->createView('Mail send', Response::HTTP_OK);
    }

    /**
     * Send mercure message.
     * @param string $key Mercure message key (Action url, for example).
     * @param string $message Message to send to client subscriber.
     */
    protected function sendMercureMessage(string $key, string $message)
    {
        $update = new Update($key, $message);
        $this->publisher->publish($update);
    }

    /**
     * Check csrf security depend on request type. Kill controller if CSRF token is not valid.
     * @param RequestContext $request Request information.
     */
    private function checkCsrfSecurity(RequestContext $request)
    {
        if (str_starts_with($request->getPathInfo(), '/public/')) {
            return;
        }

        $csrfMethod = ['PUT', 'POST', 'PATCH', 'DELETE'];
        $tokenKey = 'token';
        $errorMsg = 'Token error';

        if (in_array($request->getMethod(), $csrfMethod)) {
            try
            {
                $params = StringTools::getRequestFromQueryStringRequest($request->getQueryString());
                if (array_key_exists($tokenKey, $params)) {
                    $this->checkCsrfTokenValidation($params[$tokenKey]);
                } else {
                    throw new Exception($errorMsg, Response::HTTP_UNAUTHORIZED);
                }
            }
            catch (Exception $e)
            {
                $this->createView($errorMsg, Response::HTTP_UNAUTHORIZED, true)
                    ->getResponse()
                    ->send();
                exit();
            }
        }
    }

    /**
     * Check if CSRF token is valid.
     * @param string|null $tokenStr CSRF token.
     * @param bool $throwError True to throw error if bad token, else return false.
     * @return bool True if valid, else false.
     * @throws Exception 401 : Not authorized to execute this action. Csrf token not valid.
     */
    function checkCsrfTokenValidation(?string $tokenStr, bool $throwError = true): bool
    {
        if ($_ENV['DEV_USER_ID'] > 0)
        {
            return true;
        }

        $eppn = $this->session->get(ShibbolethAuthenticator::SHIB_EPPN_SESSION_INDEX);
        $token = new CsrfToken($eppn, $tokenStr);

        if ($this->csrfTokenManager->isTokenValid($token)) {
            return true;
        } else {
            if ($throwError) {
                throw new Exception('Csrf token not valid. Logout and re-log', Response::HTTP_UNAUTHORIZED);
            } else {
                return false;
            }
        }
    }

}
