<?php


namespace App\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Tool\ArrayAccessorTrait;

class  ZenodoResourceOwner implements ResourceOwnerInterface
{
    use ArrayAccessorTrait;

    /**
     * Name of the resource owner identifier field that is
     * present in the access token response (if applicable)
     */
    const ACCESS_TOKEN_RESOURCE_OWNER_ID = null;

    /**
     * Raw response
     *
     * @var array
     */
    protected $response;

    /**
     * Creates new resource owner.
     *
     * @param array $response
     */
    public function __construct(array $response = array())
    {
        $this->response = $response;
    }

    /**
     * Get resource owner id
     *
     * @return string|null
     */
    public function getId()
    {
        return $this->getValueByKey($this->response, 'id');
    }

    /**
     * Get resource owner Refresh Token
     *
     * @return string|null
     */
    public function getRefreshToken()
    {
        return $this->getValueByKey($this->response, 'refresh_token');
    }

    /**
     * Get resource owner Refresh Token
     *
     * @return string|null
     */
    public function getUser()
    {
        return $this->getValueByKey($this->response, 'user');
    }

    /**
     * Return all of the owner details available as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }
}
