<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CreateStatusTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testCreate_usuario_autenticado()
    {
        $this->withExceptionHandling();

        //Teniendo un usuario autenticado
        $user = factory(User::class)->create();
        $this->actingAs($user);
        
        // When => Cuando hace un post request a status
        $this->post(route('statuses.store'), ['body' => 'Mi primer status']);

        // Then => entonces veo un nuevo estado en la base de datos
        $this->assertDatabaseHas('statuses', [
            'body' => 'Mi primer status'
        ]);
    }
}
