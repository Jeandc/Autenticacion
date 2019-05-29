<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Auth;
class LoginController extends Controller
{

    // funcion para inciar sesion
    /**
     * @return \Illuminate\Http\RedirectResponse|string
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login()
    {
        $credentials = $this->validate(request(), [
            $this->username() => 'email|required|string',
            $this->getAuthPassword() => 'required|string'
        ]);

    //dd($credentials);
        if (Auth::attempt($credentials))
        {
            $user = auth::user();
            $user->save();
            return view ('welcome');

        }
        return back()
            ->withErrors(['email' =>  trans('auth.failed')])
            ->withinput(request(['email']));
    }
    // funcion para cerrar sesion
    public function logout()
    {

    // cierra sesion y devuelve al login 
        Auth::logout();

        return redirect('/');
    }
    public function getAuthPassword ()
    {

        return "password";

    }

    public function username()
    {

        return "email";
    }
}
