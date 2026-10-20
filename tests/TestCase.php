<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Aucun test ne doit appeler un service extérieur (Wayback Machine…) : simuler avec Http::fake().
        Http::preventStrayRequests();
    }
}
