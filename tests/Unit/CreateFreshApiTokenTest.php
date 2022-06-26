<?php

namespace MahdiMorovati\MongoDbPassport\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use MahdiMorovati\MongoDbPassport\ApiTokenCookieFactory;
use MahdiMorovati\MongoDbPassport\Http\Middleware\CreateFreshApiToken;
use MahdiMorovati\MongoDbPassport\Passport;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;

class CreateFreshApiTokenTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testShouldReceiveAFreshToken()
    {
        $cookieFactory = m::mock(ApiTokenCookieFactory::class);

        $middleware = new CreateFreshApiToken($cookieFactory);
        $request = m::mock(Request::class)->makePartial();

        $response = new Response;

        $guard = 'guard';
        $user = m::mock()
            ->shouldReceive('getAuthIdentifier')
            ->andReturn($userKey = 1)
            ->getMock();

        $request->shouldReceive('session')->andReturn($session = m::mock());
        $request->shouldReceive('isMethod')->with('GET')->once()->andReturn(true);
        $request->shouldReceive('user')->with($guard)->twice()->andReturn($user);
        $session->shouldReceive('token')->withNoArgs()->once()->andReturn($token = 't0k3n');

        $cookieFactory->shouldReceive('make')
            ->with($userKey, $token)
            ->once()
            ->andReturn(new Cookie(Passport::cookie()));

        $result = $middleware->handle($request, function () use ($response) {
            return $response;
        }, $guard);

        $this->assertSame($response, $result);
        $this->assertTrue($this->hasPassportCookie($response));
    }

    public function testShouldNotReceiveAFreshTokenForOtherHttpVerbs()
    {
        $cookieFactory = m::mock(ApiTokenCookieFactory::class);

        $middleware = new CreateFreshApiToken($cookieFactory);
        $request = Request::create('/', 'POST');
        $response = new Response;

        $result = $middleware->handle($request, function () use ($response) {
            return $response;
        });

        $this->assertSame($response, $result);
        $this->assertFalse($this->hasPassportCookie($response));
    }

    public function testShouldNotReceiveAFreshTokenForAnInvalidUser()
    {
        $cookieFactory = m::mock(ApiTokenCookieFactory::class);

        $middleware = new CreateFreshApiToken($cookieFactory);
        $request = Request::create('/', 'GET');
        $response = new Response;

        $request->setUserResolver(function () {
        });

        $result = $middleware->handle($request, function () use ($response) {
            return $response;
        });

        $this->assertSame($response, $result);
        $this->assertFalse($this->hasPassportCookie($response));
    }

    public function testShouldNotReceiveAFreshTokenForResponseThatAlreadyHasToken()
    {
        $cookieFactory = m::mock(ApiTokenCookieFactory::class);

        $middleware = new CreateFreshApiToken($cookieFactory);
        $request = Request::create('/', 'GET');

        $response = (new Response)->withCookie(
            new Cookie(Passport::cookie())
        );

        $request->setUserResolver(function () {
            return m::mock()
                ->shouldReceive('getAuthIdentifier')
                ->andReturn(1)
                ->getMock();
        });

        $result = $middleware->handle($request, function () use ($response) {
            return $response;
        });

        $this->assertSame($response, $result);
        $this->assertTrue($this->hasPassportCookie($response));
    }

    protected function hasPassportCookie($response)
    {
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === Passport::cookie()) {
                return true;
            }
        }

        return false;
    }
}
