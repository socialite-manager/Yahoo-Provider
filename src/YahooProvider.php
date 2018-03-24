<?php

namespace Socialite\Provider;

use Socialite\Two\AbstractProvider;
use Socialite\Two\User;
use Socialite\Util\A;

class YahooProvider extends AbstractProvider
{
    /**
     * @var string
     */
    protected $xoauth_yahoo_guid;

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl(string $state)
    {
        return $this->buildAuthUrlFromBase('https://api.login.yahoo.com/oauth2/request_auth', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://api.login.yahoo.com/oauth2/get_token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken(string $token)
    {
        $response = $this->getHttpClient()->get('https://social.yahooapis.com/v1/user/' . $this->xoauth_yahoo_guid . '/profile?format=json', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);
        return json_decode($response->getBody(), true)['profile'];
    }

    /**
     * Note: To have access to e-mail, you need to request "Profiles (Social Directory) - Read/Write Public and Private"
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => $user['guid'],
            'nickname' => $user['nickname'],
            'name' => trim(sprintf('%s %s', A::get($user, 'givenName'), A::get($user, 'familyName'))),
            'email' => A::get($user, 'emails.0.handle'),
            'avatar' => A::get($user, 'image.imageUrl'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields(string $code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse(string $code)
    {
        $response = parent::getAccessTokenResponse($code);
        $this->xoauth_yahoo_guid = A::get($response, 'xoauth_yahoo_guid');

        return $response;
    }
}
