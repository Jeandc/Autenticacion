<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


//Routes Usuarios
Route::resource('usuario', 'UsuarioController');

//Ruta Autenticacion
Route::post('login/{idrol}', 'UsuarioController@login');

//Ruta cerrar sesion
Route::post('logout', 'UsuarioController@logout');

//Rutas de Reestablecimiento de contraseña
Route::post('password/email', 'UsuarioController@email');
Route::get('password/reset/{token}', 'UsuarioController@passwordReset');
Route::post('password/reset', 'UsuarioController@reset');
