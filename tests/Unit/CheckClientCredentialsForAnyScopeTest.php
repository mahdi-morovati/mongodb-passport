<?php

namespace MahdiMorovati\MongoDbPassport\Tests\Unit;

use Illuminate\Http\Request;
use MahdiMorovati\MongoDbPassport\Client;
use MahdiMorovati\MongoDbPassport\Http\Middleware\CheckClientCredentialsForAnyScope;
use MahdiMorovati\MongoDbPassport\Token;
use MahdiMorovati\MongoDbPassport\TokenRepository;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class CheckClientCredentialsForAnyScopeTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function test_request_is_passed_along_if_token_is_valid()
    {
        $resourceServer = m::mock(ResourceServer::class);
        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = m::mock());
        $psr->shouldReceive('getAttribute')->with('oauth_user_id')->andReturn(1);
        $psr->shouldReceive('getAttribute')->with('oauth_client_id')->andReturn(1);
        $psr->shouldReceive('getAttribute')->with('oauth_access_token_id')->andReturn('token');
        $psr->shouldReceive('getAttribute')->with('oauth_scopes')->andReturn(['*']);

        $client = m::mock(Client::class);
        $client->shouldReceive('firstParty')->andReturnFalse();

        $token = m::mock(Token::class);
        $token->shouldReceive('getAttribute')->with('client')->andReturn($client);
        $token->shouldReceive('getAttribute')->with('scopes')->andReturn(['*']);

        $tokenRepository = m::mock(TokenRepository::class);
        $tokenRepository->shouldReceive('find')->with('token')->andReturn($token);

        $middleware = new CheckClientCredentialsForAnyScope($resourceServer, $tokenRepository);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $response = $middleware->handle($request, function () {
            return 'response';
        });

        $this->assertSame('response', $response);
    }

    public function test_request_is_passed_along_if_token_has_any_required_scope()
    {
        $resourceServer = m::mock(ResourceServer::class);
        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = m::mock());
        $psr->shouldReceive('getAttribute')->with('oauth_user_id')->andReturn(1);
        $psr->shouldReceive('getAttribute')->with('oauth_client_id')->andReturn(1);
        $psr->shouldReceive('getAttribute')->with('oauth_access_token_id')->andReturn('token');
        $psr->shouldReceive('getAttribute')->with('oauth_scopes')->andReturn(['foo', 'bar', 'baz']);

        $client = m::mock(Client::class);
        $client->shouldReceive('firstParty')->andReturnFalse();

        $token = m::mock(Token::class);
        $token->shouldReceive('getAttribute')->with('client')->andReturn($client);
        $token->shouldReceive('getAttribute')->with('scopes')->andReturn(['foo', 'bar', 'baz']);
        $token->shouldReceive('can')->with('notfoo')->andReturnFalse();
        $token->shouldReceive('can')->with('bar')->andReturnTrue();

        $tokenRepository = m::mock(TokenRepository::class);
        $tokenRepository->shouldReceive('find')->with('token')->andReturn($token);

        $middleware = new CheckClientCredentialsForAnyScope($resourceServer, $tokenRepository);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $response = $middleware->handle($request, function () {
            return 'response';
        }, 'notfoo', 'bar', 'notbaz');

        $this->assertSame('response', $response);
    }

    public function test_exception_is_thrown_when_oauth_throws_exception()
    {
        $this->expectException('Illuminate\Auth\AuthenticationException');

        $tokenRepository = m::mock(TokenRepository::class);
        $resourceServer = m::mock(ResourceServer::class);
        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andThrow(
            new OAuthServerException('message', 500, 'error type')
        );

        $middleware = new CheckClientCredentialsForAnyScope($resourceServer, $tokenRepository);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $middleware->handle($request, function () {
            return 'response';
        });
    }

    public function test_exception_is_thrown_if_token_does_not_have_required_scope()
    {
        $this->expectException('MahdiMorovati\MongoDbPassport\Exceptions\MissingScopeException');

        $resourceServer = m::mock(ResourceServer::class);
        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = m::mock());
        $psr->shouldReceive('getAttribute')->with('oauth_user_id')->andReturn(1);
        $psr->shouldReceive('getAttribute')->with('oauth_client_id')->andReturn(1);
        $psr->shouldReceive('getAttribute')->with('oauth_access_token_id')->andReturn('token');
        $psr->shouldReceive('getAttribute')->with('oauth_scopes')->andReturn(['foo', 'bar']);

        $client = m::mock(Client::class);
        $client->shouldReceive('firstParty')->andReturnFalse();

        $token = m::mock(Token::class);
        $token->shouldReceive('getAttribute')->with('client')->andReturn($client);
        $token->shouldReceive('getAttribute')->with('scopes')->andReturn(['foo', 'bar']);
        $token->shouldReceive('can')->with('baz')->andReturnFalse();
        $token->shouldReceive('can')->with('notbar')->andReturnFalse();

        $tokenRepository = m::mock(TokenRepository::class);
        $tokenRepository->shouldReceive('find')->with('token')->andReturn($token);

        $middleware = new CheckClientCredentialsForAnyScope($resourceServer, $tokenRepository);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $response = $middleware->handle($request, function () {
            return 'response';
        }, 'baz', 'notbar');
    }
}
