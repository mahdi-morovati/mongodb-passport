<?php

namespace MahdiMorovati\MongoDbPassport\Http\Middleware;

use Illuminate\Auth\AuthenticationException;
use MahdiMorovati\MongoDbPassport\Exceptions\MissingScopeException;

class CheckClientCredentials extends CheckCredentials
{
    /**
     * Validate token credentials.
     *
     * @param  \MahdiMorovati\MongoDbPassport\Token  $token
     * @return void
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function validateCredentials($token)
    {
        if (! $token) {
            throw new AuthenticationException;
        }
    }

    /**
     * Validate token credentials.
     *
     * @param  \MahdiMorovati\MongoDbPassport\Token  $token
     * @param  array  $scopes
     * @return void
     *
     * @throws \MahdiMorovati\MongoDbPassport\Exceptions\MissingScopeException
     */
    protected function validateScopes($token, $scopes)
    {
        if (in_array('*', $token->scopes)) {
            return;
        }

        foreach ($scopes as $scope) {
            if ($token->cant($scope)) {
                throw new MissingScopeException($scope);
            }
        }
    }
}
