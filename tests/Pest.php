<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Les tests « Feature » tournent sur PostgreSQL (base prophet_test, voir phpunit.xml) :
| plusieurs règles métier y sont aussi posées en contraintes et index partiels.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');
