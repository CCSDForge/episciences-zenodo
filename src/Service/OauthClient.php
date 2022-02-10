<?php


namespace App\Service;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Component\HttpFoundation\RequestStack;

class OauthClient
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var ClientRegistry
     */
    private $clientRegistry;

    public function __construct(RequestStack $requestStack, ClientRegistry $clientRegistry)
    {
        $this->requestStack = $requestStack;
        $this->clientRegistry = $clientRegistry;
    }


    public function checkTokenValidity()
    {
        $client = $this->clientRegistry->getClient('zenodo_main');

        try {

            $session =  $this->requestStack->getSession();

            // Load the access token from the session, and refresh if required
            $accessToken = $session->get('access_token');

            if ($accessToken->hasExpired()) {
                $accessToken = $client->refreshAccessToken($accessToken->getRefreshToken());

                // Update the stored access token for next time
                $session->set('access_token', $accessToken);
            }

        } catch (IdentityProviderException $e) {
            return $e->getMessage();
        }
    }
}
