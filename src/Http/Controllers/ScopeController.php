<?php

namespace MahdiMorovati\MongoDbPassport\Http\Controllers;

use MahdiMorovati\MongoDbPassport\Passport;

class ScopeController
{
    /**
     * Get all of the available scopes for the application.
     *
     * @return \Illuminate\Support\Collection
     */
    public function all()
    {
        return Passport::scopes();
    }
}
