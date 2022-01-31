<?php

namespace App\Controller;

use App\Service\ZenodoClient;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
class OauthLoginController extends AbstractController
{
    /**
     * @Route("/testconnection", name="testconnec")
     */
    public function testconnection(Request $request , ClientRegistry $clientRegistry)
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
            $accessToken = $client->getAccessToken();

            $session->set('access_token', $accessToken);

            // Load the access token from the session, and refresh if required
            $accessToken = $session->get('access_token');

            if ($accessToken->hasExpired()) {
                $accessToken = $client->refreshAccessToken($accessToken->getRefreshToken());

                // Update the stored access token for next time
                $session->set('access_token', $accessToken);
            }

            return $this->redirectToRoute('home');

        } catch (IdentityProviderException $e) {

            var_dump($e->getMessage()); die;
        }
    }
}