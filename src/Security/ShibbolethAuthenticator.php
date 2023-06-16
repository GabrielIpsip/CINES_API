<?php

namespace App\Security;


use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

class ShibbolethAuthenticator extends AbstractGuardAuthenticator implements LogoutSuccessHandlerInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var String | null
     */
    private $apiUrl;

    /**
     * @var String | null
     */
    private $appUrl;

    /**
     * @var string | null
     */
    private $eppn;

    /**
     * @var string | null
     */
    private $displayName;

    /**
     * @var string | null
     */
    private $givenName;

    /**
     * @var string | null
     */
    private $mail;

    /**
     * @var
     */
    private $route_form;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var string | null
     */
    private $equipeCssiCivilite;

    /**
     * @var string | null
     */
    private $equipeCssiMail;

    /**
     * @var string | null
     */
    private $equipeCssiNom;

    /**
     * @var string | null
     */
    private $equipeCssiPrenom;

    /**
     * @var string | null
     */
    private $equipeCssiTel;

    /**
     * @var string | null
     */
    private $equipeRespCivilite;

    /**
     * @var string | null
     */
    private $equipeRespMail;

    /**
     * @var string | null
     */
    private $equipeRespNom;

    /**
     * @var string | null
     */
    private $equipeRespPrenom;

    /**
     * @var string | null
     */
    private $equipeRespTel;

    /**
     * @var string | null
     */
    private $equipeRnsr;

    /**
     * @var string | null
     */
    private $shibIdentityProvider;

    /**
     * @var CsrfTokenManagerInterface
     */
    private $csrfTokenManager;

    private const SHIB_SESSION_INDEX = 'shib';
    public const SHIB_USER_SESSION_INDEX = 'shib_user';
    public const SHIB_EPPN_SESSION_INDEX = 'eppn';

    /**
     * @var string | null
     */
    private $cruIdentityProvider;

    public function __construct(
        RouterInterface $router,
        ManagerRegistry $managerRegistry,
        SessionInterface $session,
        CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->router = $router;
        $this->managerRegistry = $managerRegistry;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->eppn = 'REDIRECT_eppn';
        $this->displayName = 'REDIRECT_displayName';
        $this->givenName = 'REDIRECT_givenName';
        $this->mail = 'REDIRECT_mail';
        $this->session = $session;
        $this->equipeCssiCivilite = 'REDIRECT_equipeCssiCivilite';
        $this->equipeCssiMail = 'REDIRECT_equipeCssiMail';
        $this->equipeCssiNom = 'REDIRECT_equipeCssiNom';
        $this->equipeCssiPrenom = 'REDIRECT_equipeCssiPrenom';
        $this->equipeCssiTel = 'REDIRECT_equipeCssiTel';
        $this->equipeRespCivilite = 'REDIRECT_equipeRespCivilite';
        $this->equipeRespMail = 'REDIRECT_equipeRespMail';
        $this->equipeRespNom = 'REDIRECT_equipeRespNom';
        $this->equipeRespPrenom = 'REDIRECT_equipeRespPrenom';
        $this->equipeRespTel = 'REDIRECT_equipeRespTel';
        $this->equipeRnsr = 'REDIRECT_equipeRnsr';
        $this->shibIdentityProvider = 'REDIRECT_Shib-Identity-Provider';
        $this->cruIdentityProvider = 'urn:mace:cru.fr:federation:sac';

        $dotEnv = new Dotenv();
        $dotEnv->load(__DIR__.'/../../.env.local');
        $this->apiUrl = str_ends_with($_ENV['API_URL'], '/') ? $_ENV['API_URL'] : $_ENV['API_URL'] . '/';
        $this->appUrl = str_ends_with($_ENV['APP_URL'], '/') ? $_ENV['APP_URL'] : $_ENV['APP_URL'] . '/';
    }


    protected function getRedirectUrl()
    {
        return $this->router->generate('shib_login');
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        $redirectTo = $this->getRedirectUrl();
        if (in_array('application/json', $request->getAcceptableContentTypes())) {
            return new JsonResponse(array(
                'status' => 'error',
                'message' => 'You are not authenticated.',
                'redirect' => $redirectTo,
            ), Response::HTTP_FORBIDDEN);
        } else {
            return new RedirectResponse($redirectTo);
        }
    }

    public function supports(Request $request)
    {
        $hasEppn = $request->server->has($this->eppn);
        $hasShibIdProvider = $request->server->has($this->shibIdentityProvider);
        if (!$hasEppn && !$hasShibIdProvider)
        {
            return false;
        }
        else
        {
            $eppn = $request->server->get($this->eppn);
            $entityProvider = $request->server->get($this->shibIdentityProvider);
            if ($eppn) {
                return true;
            }
            else if ($entityProvider === $this->cruIdentityProvider)
            {
                return true;
            }
            else
            {
                return false;
            }
        }
    }

    public function getCredentials(Request $request)
    {
        $this->route_form = $request->attributes->get('_route');
        $eppn = $request->server->get($this->eppn);
        $displayName = $request->server->get($this->displayName);
        $givenName = $request->server->get($this->givenName);
        $mail = $request->server->get($this->mail);

        $equipeCssiCivilite = $request->server->get($this->equipeCssiCivilite);
        $equipeCssiNom = $request->server->get($this->equipeCssiNom);
        $equipeCssiPrenom = $request->server->get($this->equipeCssiPrenom);
        $equipeCssiMail = $request->server->get($this->equipeCssiMail);
        $equipeCssiTel = $request->server->get($this->equipeCssiTel);
        $equipeRespCivilite = $request->server->get($this->equipeRespCivilite);
        $equipeRespNom = $request->server->get($this->equipeRespNom);
        $equipeRespPrenom = $request->server->get($this->equipeRespPrenom);
        $equipeRespMail = $request->server->get($this->equipeRespMail);
        $equipeRespTel = $request->server->get($this->equipeRespTel);
        $equipeRnsr = $request->server->get($this->equipeRnsr);

        $shibIdentityProvider = $request->server->get($this->shibIdentityProvider);

        $credential = array();
        $credential['eppn'] = $eppn;
        $credential['displayName'] = $displayName;
        $credential['givenName'] = $givenName;
        $credential['server_shib'] = $request->server;
        $credential['mail'] = $mail;

        $credential['equipeCssiCivilite'] = $equipeCssiCivilite;
        $credential['equipeCssiNom'] = $equipeCssiNom;
        $credential['equipeCssiPrenom'] = $equipeCssiPrenom;
        $credential['equipeCssiMail'] = $equipeCssiMail;
        $credential['equipeCssiTel'] = $equipeCssiTel;
        $credential['equipeRespCivilite'] = $equipeRespCivilite;
        $credential['equipeRespNom'] = $equipeRespNom;
        $credential['equipeRespPrenom'] = $equipeRespPrenom;
        $credential['equipeRespMail'] = $equipeRespMail;
        $credential['equipeRespTel'] = $equipeRespTel;
        $credential['equipeRnsr'] = $equipeRnsr;

        $credential['shibIdentityProvider'] = $shibIdentityProvider;

        return $credential;
    }

    /**
     * @param $credentials
     * @param UserProviderInterface $userProvider
     * @return Users|null
     * @throws Exception
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $eppn = $credentials['eppn'];

        $usersRepo = $this->managerRegistry->getRepository(Users::class);
        $user = $usersRepo->findOneBy(array('eppn' => $eppn));

        if ($user == null) {
            $user = $usersRepo->find(1);
        }

        if ($user != null) {
            $this->session->set(self::SHIB_SESSION_INDEX, true);
            $this->session->set(self::SHIB_USER_SESSION_INDEX, $user);
            $this->session->set(self::SHIB_EPPN_SESSION_INDEX, $eppn);
        } else {
            $this->session->remove(self::SHIB_SESSION_INDEX);
            $this->session->remove(self::SHIB_USER_SESSION_INDEX);
            $this->session->remove(self::SHIB_EPPN_SESSION_INDEX);
            $this->csrfTokenManager->removeToken($eppn);
        }

        return $user;
    }


    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return true;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): RedirectResponse
    {
        return new RedirectResponse($this->router->generate('app_user_connect_failure'));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): RedirectResponse
    {
        $redirectUrl = $request->get('return');

        if ($redirectUrl === '/') {
            $redirectUrl = $this->appUrl;
        }

        $redirectUrlExplode = explode('/', $redirectUrl);
        $redirectUrlExplode = $redirectUrlExplode[0] . '//' . $redirectUrlExplode[2] . '/';

        if (!str_starts_with($redirectUrlExplode, $this->apiUrl)
            && !str_starts_with($redirectUrlExplode, $this->appUrl)) {
            return new RedirectResponse($this->router->generate('app_user_connect_success'));
        }

        if (empty($redirectUrl))
        {
            return new RedirectResponse($this->router->generate('app_user_connect_success'));
        }
        return new RedirectResponse($redirectUrl);
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }

    public function onLogoutSuccess(Request $request): RedirectResponse
    {
        $redirectUrl = $request->get('return');

        if ($redirectUrl === '/') {
            $redirectUrl = $this->appUrl;
        }

        $redirectUrlExplode = explode('/', $redirectUrl);
        $redirectUrlExplode = $redirectUrlExplode[0] . '//' . $redirectUrlExplode[2] . '/';

        if (!str_starts_with($redirectUrlExplode, $this->apiUrl)
            && !str_starts_with($redirectUrlExplode, $this->appUrl)) {
            $redirectUrl = $this->apiUrl;
        }

        if (empty($redirectUrl)) {
            $redirectUrl = $this->apiUrl;
        }

        if($this->session->get(self::SHIB_SESSION_INDEX) == true){
            $redirectTo = $redirectTo = $this->router->generate('shib_logout', array(
                'return'  => $redirectUrl
            ));
        } else {
            $redirectTo = $this->router->generate('home');
        }
        $this->session->remove(self::SHIB_SESSION_INDEX);
        $this->session->remove(self::SHIB_USER_SESSION_INDEX);
        $this->csrfTokenManager->removeToken($this->eppn);

        return new RedirectResponse($redirectTo);
    }

}
