<?php

namespace App\Http\Controllers;

use App\PasswordReset;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UsuarioController extends Controller
{

    private $urlFrontend = "";

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        if (!esTokenValido($request)) {
            return response()->json(['message' => 'Petición sin token válido!', 'status' => '401', 'color' => 'danger']);
        }

        header('Access-Control-Allow-Origin: *');
        $data = User::all();
        $response = Response::json($data);
        return $response;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!esTokenValido($request)) {
            return response()->json(['message' => 'Petición sin token válido!', 'status' => '401', 'color' => 'danger'], 401);
        }

        header('Access-Control-Allow-Origin: *');
        $validator = Validator::make($request->all(), [
            'correo' => 'email|max:250|required|unique:usuario',
            'cedula' => 'required|string|unique:usuario',
            'nacionalidad' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Error al validar los campos', 'status' => '422', 'color' => 'danger', 'error' => $validator->errors()], 422);
        }
        try {
            DB::beginTransaction();

            DB::table('usuario')->insert([
                'correo' => $request->input('correo'),
                'cedula' => $request->input('cedula'),
                'nacionalidad' => $request->input('nacionalidad'),
                'id_estatus_usuario' => '1',
                'id_rol' => '1',
                'numero_intentos' => '0',
                'usuario_registrado' => '0',
                'remember_token' => str_random(60),
            ]);
            $horaActual = Carbon::now('America/Caracas');
            $user = User::where('correo', $request->correo)->first();
            $passwordReset = PasswordReset::Create(
                [
                    'correo' => $user->correo,
                    'token' => str_random(60),
                    'created_at' => $horaActual,
                    //'updated_at' => $horaActual
                ]
            );
            $ruta = $this->urlFrontend . '/#/crearclave?t=' . $passwordReset->token;
            $mensaje = " " .
                $ruta .
                "\n\nIMPORTANTE: Esta es una cuenta de correo no monitoreada. Por favor, no responda ni reenvíe mensajes a esta cuenta.";
            if ($user && $passwordReset) {
                Mail::raw($mensaje, function ($message) use ($user) {
                    $message->subject('Registro en SIPA');
                    $message->to($user->correo);
                });

                $mens1 = "Envio de correo exitoso";
                DB::commit();
                return response()->json(['entity' => $user, 'correo' => $mens1, 'message' => 'Hemos enviado un enlace para crear contraseña para que continue su registro', 'status' => '200', 'color' => 'success'], 200);
            }
        } catch (\Exception $e) {
            $mens = "Envio de correo no exitoso";
            DB::rollBack();
            return response()->json(['correo' => $mens, 'message' => $e->getMessage(), 'codError' => '300', 'color' => 'danger'], 300);

        }
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param  int $id
     * @return JsonResponse
     */
    public function show(Request $request, $id)
    {
        if (!esTokenValido($request)) {
            return response()->json(['message' => 'Petición sin token válido!', 'status' => '401', 'color' => 'danger'], 401);
        }

        header('Access-Control-Allow-Origin: *');
        $usuario = DB::select("SELECT * FROM usuario WHERE id=?", [$id]);
        $response = Response::json($usuario);
        return $response;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Request $request
     * @param $usuario
     * @return JsonResponse
     */
    public function edit(Request $request, $usuario)
    {
        if (!esTokenValido($request)) {
            return response()->json(['message' => 'Petición sin token válido!', 'status' => '401', 'color' => 'danger'], 401);
        }

        header('Access-Control-Allow-Origin: *');
        $usuario = DB::table('usuario')->find($usuario);
        $response = Response::json($usuario);
        return $response;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!esTokenValido($request)) {
            return response()->json(['message' => 'Petición sin token válido!', 'status' => '401', 'color' => 'danger'], 401);
        }

        header("Access-Control-Allow-Origin: *");
        try {
            $updated_at = Carbon::now()->toDateTimeString();
            DB::table('usuario')->where('id', $id)->update([
                'correo' => $request->input('correo'),
                'cedula' => $request->input('cedula'),
                //'clave'=> Hash::make($request->clave),
                'nacionalidad' => $request->input('nacionalidad'),
                'id_rol' => $request->input('id_rol'),
                'updated_at' => $updated_at]);
            return response()->json(['message' => 'Usuario actualizado exitosamente!', 'status' => '200', 'color' => 'success'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'error' => 'Usuario no se Actualizo', 'status' => '401', 'color' => 'danger'], 401);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @param $usuario
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $usuario)
    {
        if (!esTokenValido($request)) {
            return response()->json(['message' => 'Petición sin token válido!', 'status' => '401', 'color' => 'danger'], 401);
        }

        header('Access-Control-Allow-Origin: *');
        DB::table('usuario')->where('id', $usuario)->delete();
        return response()->json(['message' => 'Usuario eliminado exitosamente!', 'status' => '200', 'color' => 'success'], 200);
    }
    // funcion para inciar sesion

    /**
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        header('Access-Control-Allow-Origin: *');

        $credentials = $this->validate(request(), [
            'correo' => 'email|required|string',
            'clave' => 'required|string',
        ]);
        $usuario = DB::table('usuario')
            ->Select('id', 'correo', 'cedula', 'clave', 'id_rol')
            ->where('correo', '=', "$_POST[correo]")
            ->get();

        $cuenta = 0;
        $idEmpresa = 0;

        if (count($usuario) > 0) {
            $userId = $usuario[0]->id;
            $citasPendientes = DB::select("SELECT count(*)  as cuenta, est.id_empresa     
            FROM empresa e, estatus_instrumento est 
            WHERE e.id = est.id_empresa 
            AND id_instrumento = 1 
            AND  id_estatus = 4 
            AND id_usuario = '$userId' 
            AND est.id  
            IN (select max(est.id) FROM empresa e, estatus_instrumento est WHERE e.id = est.id_empresa AND id_instrumento = 1 AND id_usuario= '$userId' )")[0];
            $cuenta = $citasPendientes->cuenta;
            $idEmpresa = $citasPendientes->id_empresa;
        }

        if (count($usuario) > 0) {

            if (Auth::attempt(['correo' => $credentials['correo'], 'password' => $credentials['clave']])) {
                $user = auth::user();
                $user->save();
                /*Creación de token*/
                /*$tokenPeticiones = Str::random(50);
                DB::table('token_peticiones')->insert([
                'correo' => $credentials['correo'],
                'token' => $tokenPeticiones
                ]);
                /*Fin Creación de token*/
                return response()->json(['entity' => $usuario[0], 'message' => 'Inicio de sesion correcto', 'status' => '200', 'color' => 'success', 'citasPendientes' => $cuenta, 'idEmpresa' => $idEmpresa], 200);
            } else {
                return response()->json(['error' => 'No Autorizado', 'status' => '401', 'color' => 'danger'], 401);
            }
        } else {
            return response()->json(['error' => 'Usuario no existe', 'status' => '401', 'color' => 'danger'], 401);

        }
    }

    // funcion para cerrar sesion
    public function logout(Request $request)
    {
        if (!esTokenValido($request)) {
            return response()->json(['message' => 'Petición sin token válido!', 'status' => '401', 'color' => 'danger'], 401);
        }

        header('Access-Control-Allow-Origin: *');
        DB::select("DELETE FROM token_peticiones WHERE correo=?", [$request->header('correo')]);
        Auth::logout();
        return response()->json(['Sesion finalizada', 'status' => '200', 'color' => 'success'], 200);
    }

    /**
     * Create token password reset
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function email(Request $request)
    {
        if (!esTokenValido($request)) {
            return response()->json(['message' => 'Petición sin token válido!', 'status' => '401', 'color' => 'danger'], 401);
        }

        header('Access-Control-Allow-Origin: *');
        $request->validate([
            'correo' => 'required|string|email',
        ]);
        $horaActual = Carbon::now('America/Caracas');
        $user = User::where('correo', $request->correo)->first();
        if (!$user) {
            return response()->json(['message' => 'No podemos encontrar un usuario con esa dirección de correo electrónico.', 'status' => '404', 'color' => 'danger'], 404);
        }

        $passwordReset = PasswordReset::updateOrCreate(
            ['correo' => $user->correo],
            [
                'correo' => $user->correo,
                'token' => Str::random(60),
                'created_at' => $horaActual,
                //'updated_at' => $horaActual
            ]
        );
        $ruta = $this->urlFrontend . '/#/cambiarclave?t=' . $passwordReset->token;
        if ($user && $passwordReset) {
            Mail::raw(" " .
                $ruta .
                "\n\nIMPORTANTE: Esta es una cuenta de correo no monitoreada. Por favor, no responda ni reenvíe mensajes a esta cuenta.", function ($message) use ($user, $passwordReset) {
                    $message->subject('Reestablecimiento de contraseña (SIPA)');
                    $message->to($user->correo);
                });
            return response()->json(['message' => 'Hemos enviado su enlace de restablecimiento de contraseña por correo electrónico', 'status' => '200', 'color' => 'success'], 200);
        }

    }

    /**
     * token password reset
     *
     * @param  [string] $token
     * @return JsonResponse
     */
    public function passwordReset($token)
    {
        header('Access-Control-Allow-Origin: *');
        $passwordReset = PasswordReset::where('token', $token)
            ->first();
        if (!$passwordReset) {
            return response()->json(['message' => 'Este token de restablecimiento de contraseña no es válido.', 'status' => '404', 'color' => 'danger'], 404);
        }

        //if (Carbon::parse($passwordReset->updated_at)->addMinutes(15)->isPast()) {
        $horaCreado = Carbon::parse($passwordReset->updated_at, 'America/Caracas');
        $horaActual = Carbon::now('America/Caracas');
        if ($horaCreado->diffInMinutes($horaActual) > 15) {
            $passwordReset->delete();
            return response()->json(['message' => 'Este token de restablecimiento de contraseña no es válido.', 'status' => '404', 'color' => 'danger'], 404);
        }
        return response()->json($passwordReset);
    }

    /**
     * Reset password
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse [string] message
     */
    public function reset(Request $request)
    {
        header('Access-Control-Allow-Origin: *');
        $request->validate([
            'clave' => 'required',
            'confirm_password' => 'required|same:clave',
        ]);
        $passwordReset = PasswordReset::where(['token' => $request->token])->first();
        if (!$passwordReset) {
            return response()->json(['message' => 'Este token de restablecimiento de contraseña no es válido.', 'status' => '404', 'color' => 'danger'], 404);
        }
        $horaCreado = Carbon::parse($passwordReset->updated_at, 'America/Caracas');
        $horaActual = Carbon::now('America/Caracas');
        if ($horaCreado->diffInMinutes($horaActual) > 15) {
            $passwordReset->delete();
            return response()->json(['message' => 'Este token de restablecimiento de contraseña no es válido.', 'status' => '404', 'color' => 'danger'], 404);
        }
        $user = User::where('correo', $passwordReset->correo)->first();
        if (!$user) {
            return response()->json(['message' => 'No podemos encontrar un usuario con esa dirección de correo electrónico.', 'status' => '404', 'color' => 'danger'], 404);
        }

        $user->clave = Hash::make($request->clave);
        $user->usuario_registrado = 1;
        $user->save();
        $passwordReset->delete();
        return response()->json([
            'message' => 'Contraseña cambiada exitosamente', 'status' => '200', 'color' => 'success',
            'user' => $user,
        ], 200);

    }

}
