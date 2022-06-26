<?php

namespace MahdiMorovati\MongoDbPassport\Tests\Feature;

use Illuminate\Contracts\Routing\Registrar;
use MahdiMorovati\MongoDbPassport\Client;
use MahdiMorovati\MongoDbPassport\Http\Middleware\CheckClientCredentials;
use MahdiMorovati\MongoDbPassport\Http\Middleware\CheckClientCredentialsForAnyScope;
use MahdiMorovati\MongoDbPassport\Passport;
use Orchestra\Testbench\TestCase;

class ActingAsClientTest extends TestCase
{
    public function testActingAsClientWhenTheRouteIsProtectedByCheckClientCredentialsMiddleware()
    {
        $this->withoutExceptionHandling();

        /** @var Registrar $router */
        $router = $this->app->make(Registrar::class);

        $router->get('/foo', function () {
            return 'bar';
        })->middleware(CheckClientCredentials::class);

        Passport::actingAsClient(new Client());

        $response = $this->get('/foo');
        $response->assertSuccessful();
        $response->assertSee('bar');
    }

    public function testActingAsClientWhenTheRouteIsProtectedByCheckClientCredentialsForAnyScope()
    {
        $this->withoutExceptionHandling();

        /** @var Registrar $router */
        $router = $this->app->make(Registrar::class);

        $router->get('/foo', function () {
            return 'bar';
        })->middleware(CheckClientCredentialsForAnyScope::class.':testFoo');

        Passport::actingAsClient(new Client(), ['testFoo']);

        $response = $this->get('/foo');
        $response->assertSuccessful();
        $response->assertSee('bar');
    }
}
