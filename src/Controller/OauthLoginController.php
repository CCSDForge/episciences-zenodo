<?php

namespace App\Controller;

use App\Service\ZenodoClient;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class OauthLoginController extends AbstractController
{
    public function oauthindex(Security $security)
    {
        $userInfo = $security->getToken()->getAttributes();
        return $this->render('login/indexoauth.html.twig',[
            'userInfo' => [
                'lastname' => $userInfo['LASTNAME'],
                'firstname' => $userInfo['FIRSTNAME'],
            ]
        ]);
    }

    /**
     * @Route("/oauthzenodo", name="oauth_zenodo_authorization")
     */
    public function oauthZenodo(Request $request , ClientRegistry $clientRegistry)
    {
        return $clientRegistry
            ->getClient('zenodo_main') // key used in config/packages/knpu_oauth2_client.yaml
            ->redirect([
                'deposit:write', 'deposit:actions','user:email' // the scopes you want to access
            ]);
    }
    /**
     * After going to zenodo, you're redirected back here
     * because this is the "redirect_route" you configured
     * in config/packages/knpu_oauth2_client.yaml
     *
     * @Route("/connect/zenodo/check", name="connect_zenodo_check")
     */
    public function connectCheckAction(Request $request, ClientRegistry $clientRegistry, RequestStack $requestStack)
    {
        // ** if you want to *authenticate* the user, then
        // leave this method blank and create a Guard authenticator
        // (read below)
        $client = $clientRegistry->getClient('zenodo_main');

        try {
            // the exact class depends on which provider you're using

            // Try to get an access token using the authorization code grant.
            // Fetch and store the AccessToken
            $session = $requestStack->getSession();

            if (isset($_GET['error'])) {

                if ($_GET['error'] === 'access_denied') {
                    $this->addFlash('error','You must authorize, in order to be authorized to make action on Zenodo via application');
                } else {
                    $this->addFlash('error','Something wrong happened');
                }
                return $this->redirectToRoute('oauth_login');
            }

            $accessToken = $client->getAccessToken();

            $session->set('access_token', $accessToken);

            // Load the access token from the session, and refresh if required
            $accessToken = $session->get('access_token');

            if ($accessToken->hasExpired()) {
                $accessToken = $client->refreshAccessToken($accessToken->getRefreshToken());

                // Update the stored access token for next time
                $session->set('access_token', $accessToken);
            }
            return $this->redirectToRoute('create_deposit');

        } catch (IdentityProviderException $e) {

            var_dump($e->getMessage()); die;
        }
    }
}