<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {

        $response = $this->get('/');

        $response->assertStatus(405);
    }

    public function testFoo ()
    {
        $this->withoutExceptionHandling();
        $response = $this->post('/');
        $response->assertStatus(405);
    }
}
