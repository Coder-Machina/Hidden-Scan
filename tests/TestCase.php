<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Empêche strictement tout envoi réel vers Discord pendant les tests
        \Illuminate\Support\Facades\Http::fake([
            'https://discord.com/*' => \Illuminate\Support\Facades\Http::response(['id' => 'fake_msg'], 200),
        ]);
    }
}
