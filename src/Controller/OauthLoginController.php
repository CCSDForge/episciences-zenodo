<?php

namespace App\Controller;

use App\Service\ZenodoClient;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class OauthLoginController extends AbstractController
{
    public function oauthindex(Security $security,RequestStack $requestStack)
    {
        $userInfo = $security->getToken()->getAttributes();
        $rvcodeTxt = '';
        if($requestStack->getSession()->has('epi-rvcode')){
            $rvcodeTxt = $requestStack->getSession()->get('epi-rvcode');
        }
        return $this->render('login/indexoauth.html.twig',[
            'userInfo' => [
                'lastname' => $userInfo['LASTNAME'],
                'firstname' => $userInfo['FIRSTNAME'],
            ],
            'rvcodeTxt' => $rvcodeTxt
        ]);
    }

    /**
     * @Route("/oauthzenodo", name="oauth_zenodo_authorization")
     */
    public function oauthZenodo(Request $request , ClientRegistry $clientRegistry,LoggerInterface $logger)
    {
        $logger->debug('page before connect oauth');
        $logger->debug("Client",[$clientRegistry->getClient('zenodo_main')]);
        return $clientRegistry
            ->getClient('zenodo_main') // key used in config/packages/knpu_oauth2_client.yaml
            ->redirect([
                'deposit:write', 'deposit:actions','user:email' // the scopes you want to access
            ],['redirect_uri'=>$this->getParameter('oauth_redirect_secure')]);
    }

    public function connectCheckAction(Request $request, ClientRegistry $clientRegistry, RequestStack $requestStack,LoggerInterface $logger, TranslatorInterface $translator)
    {
        // ** if you want to *authenticate* the user, then
        // leave this method blank and create a Guard authenticator
        // (read below)
        $logger->debug('page check');
        $logger->debug("Client Oauth",[$clientRegistry->getClient('zenodo_main')]);
        $client = $clientRegistry->getClient('zenodo_main');
        $logger->debug('try get session',[$requestStack->getSession()]);
        try {
            $logger->info("Enter in try to push session oauth in app");
            // the exact class depends on which provider you're using

            // Try to get an access token using the authorization code grant.
            // Fetch and store the AccessToken
            $session = $requestStack->getSession();
            $logger->debug('try get session',[$session->all()]);
            if (isset($_GET['error'])) {
                $logger->debug('GET error',[$_GET['error']]);
                if (!is_null($session->get('access_token'))){
                    $session->remove('access_token');

                }
                $logger->debug('GET error',[$session->get("knpu.oauth2_client_state")]);
                $session->remove('knpu.oauth2_client_state');
                if ($_GET['error'] === 'access_denied') {
                    $this->addFlash('error',$translator->trans('accessDeniedClientDenied'));
                } else {
                    $this->addFlash('error',$translator->trans('somethingWrong'));
                }
                return $this->redirectToRoute('oauth_login');
            }
            $logger->debug('state knpu before insert session',[$session->get("knpu.oauth2_client_state")]);
            $accessToken = $client->getAccessToken();
            $logger->debug('try get accessToken',[$accessToken]);
            $session->set('access_token', $accessToken);

            // Load the access token from the session, and refresh if required
            $accessToken = $session->get('access_token');
            $logger->debug('session',[$session]);
            $logger->debug('try get accessToken in session',[$accessToken]);
            $logger->debug('Is expired : ',[$accessToken->hasExpired()]);
            if ($accessToken->hasExpired()) {
                $accessToken = $client->refreshAccessToken($accessToken->getRefreshToken());

                // Update the stored access token for next time
                $session->set('access_token', $accessToken);
            }
            $logger->debug('after all process',[$session->get("knpu.oauth2_client_state")]);
            $logger->debug('session oauth : ',[$session->get('access_token')]);
            return $this->redirectToRoute('create_deposit');

        } catch (IdentityProviderException $e) {
            $logger->debug('enter in catch',[$e->getTrace()]);
            $logger->error($e->getMessage(),['context'=>'oauth_connection_zenodo_app']);

            $this->addFlash('error','Something wrong happened');

            return $this->redirectToRoute('oauth_login');

        }
    }
}