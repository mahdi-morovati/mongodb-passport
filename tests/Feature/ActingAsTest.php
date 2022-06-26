<?php

namespace MahdiMorovati\MongoDbPassport\Tests\Feature;

use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Foundation\Auth\User;
use MahdiMorovati\MongoDbPassport\HasApiTokens;
use MahdiMorovati\MongoDbPassport\Http\Middleware\CheckForAnyScope;
use MahdiMorovati\MongoDbPassport\Http\Middleware\CheckScopes;
use MahdiMorovati\MongoDbPassport\Passport;

class ActingAsTest extends PassportTestCase
{
    public function testActingAsWhenTheRouteIsProtectedByAuthMiddleware()
    {
        $this->withoutExceptionHandling();

        /** @var Registrar $router */
        $router = $this->app->make(Registrar::class);

        $router->get('/foo', function () {
            return 'bar';
        })->middleware('auth:api');

        Passport::actingAs(new PassportUser());

        $response = $this->get('/foo');
        $response->assertSuccessful();
        $response->assertSee('bar');
    }

    public function testActingAsWhenTheRouteIsProtectedByCheckScopesMiddleware()
    {
        $this->withoutExceptionHandling();

        /** @var Registrar $router */
        $router = $this->app->make(Registrar::class);

        $router->get('/foo', function () {
            return 'bar';
        })->middleware(CheckScopes::class.':admin,footest');

        Passport::actingAs(new PassportUser(), ['admin', 'footest']);

        $response = $this->get('/foo');
        $response->assertSuccessful();
        $response->assertSee('bar');
    }

    public function testActingAsWhenTheRouteIsProtectedByCheckForAnyScopeMiddleware()
    {
        $this->withoutExceptionHandling();

        /** @var Registrar $router */
        $router = $this->app->make(Registrar::class);

        $router->get('/foo', function () {
            return 'bar';
        })->middleware(CheckForAnyScope::class.':admin,footest');

        Passport::actingAs(new PassportUser(), ['footest']);

        $response = $this->get('/foo');
        $response->assertSuccessful();
        $response->assertSee('bar');
    }
}

class PassportUser extends User
{
    use HasApiTokens;

    protected $table = 'users';
}
