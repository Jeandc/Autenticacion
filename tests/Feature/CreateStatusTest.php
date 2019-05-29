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

    public function testguests_users_can_not_create_statuses()
    {

        $response = $this->post(route('statuses.store'), ['body' => 'Mi primer status']);


        $response->assertRedirect('login');

    }

    public function testCreate_usuario_autenticado()
    {

        //Teniendo un usuario autenticado
        $user = factory(User::class)->create();
        $this->actingAs($user);

        // When => Cuando hace un post request a status
        $response = $this->postJson(route('statuses.store'), ['body' => 'Mi primer status']);

        $response->assertJson([
            'body' =>'Mi primer status'
        ]);

        // Then => entonces veo un nuevo estado en la base de datos
        $this->assertDatabaseHas('statuses', [
            'user_id' => $user-> ID,
            'body' => 'Mi primer status'
        ]);
    }

    public function testEstado_requiere_unCuerpo()
    {

        $user = factory(User::class)->create();
        $this->actingAs($user);

        $response = $this->postJson(route('statuses.store'), ['body' => '']);

        $response->assertStatus(422);

        $response->assertJsonStructure([
            'message','errors' => ['body']
        ]);


    }

    public function testCuerpoEstado_requiere_unMinCaracteres()
    {

        $user = factory(User::class)->create();
        $this->actingAs($user);

        $response = $this->postJson(route('statuses.store'), ['body' => 'asdf']);

        $response->assertStatus(422);

        $response->assertJsonStructure([
            'message','errors' => ['body']
        ]);


    }
}
