<?php
namespace App\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use Psr\Http\Message\ResponseInterface;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;

class Zenodo extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * Get authorization url to begin OAuth flow
     *
     * @return string
     */

    public function getBaseAuthorizationUrl()
    {
        return $_ENV['APP_API_ZEN_URL']."/oauth/authorize";
    }

    /**
     * Get access token url to retrieve token
     *
     * @param array $params
     *
     * @return string
     */

    public function getBaseAccessTokenUrl(array $params)
    {
        return $_ENV['APP_API_ZEN_URL']."/oauth/token";
    }

    /**
     * Get provider url to fetch user details
     *
     * @param AccessToken $token
     *
     * @return string
     */

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return '';
    }

    /**
     * Get the default scopes used by this provider.
     *
     * @return array
     */
    protected function getDefaultScopes()
    {
        return ["deposit:write","deposit:actions","user:email"];
    }

    /**
     * Returns the string that should be used to separate scopes when building
     * the URL for requesting an access token.
     *
     * @return string Scope separator, defaults to ','
     */
    protected function getScopeSeparator()
    {
        return ' ';
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (isset($data['error'])) {
            $statusCode = $response->getStatusCode();
            $error = $data['error'];
            throw new IdentityProviderException(
                $statusCode . ' - ' . $error,
                $response->getStatusCode(),
                $response
            );
        }
    }

    /**
     * Generate a user object from a successful user details request.
     *
     * @param array $response
     * @param AccessToken $token
     *
     * @return ZenodoResourceOwner
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new ZenodoResourceOwner($response,$token);
    }

}
