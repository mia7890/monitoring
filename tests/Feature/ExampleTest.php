<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Test that the root landing page renders successfully with the calendar UI.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('Hytec Power Inc.')
            ->assertSee('Schedule & Monitoring Portal')
            ->assertSee('Sun')
            ->assertSee('Sat');
    }
}
