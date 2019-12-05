<?php

namespace App\Http\Controllers;

use App\EstatusEmpresa;
use App\Http\Controllers\Controller;
use App\Http\Controllers\CorreoController;
use App\Http\Controllers\TelefonoController;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Validator;

class AccionistaController extends Controller
{
    protected $controllerTelefonos;

    protected $controllerCorreos;

    public function __constructTelefono(TelefonoController $contTelefonos)
    {
        $this->controllerTelefonos = $contTelefonos;
    }

    public function __constructCorreo(CorreoController $contCorreos)
    {
        $this->controllerCorreos = $contCorreos;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (!esTokenValido($request)) {
            return response()->json(['message' => 'Petición sin token válido!', 'status' => '401', 'color' => 'danger']);
        }

        header('Access-Control-Allow-Origin: *');
        try {
            $accionistasyRl = DB::select("SELECT acc.id, acc.nombre_uno, acc.nombre_dos, acc.apellido_uno, acc.apellido_dos, acc.cedula_pasaporte, acc.rif, tip.nombre
            FROM accionistas acc, tipo_persona tip
            WHERE tip.id = acc.id_tipo_persona");

            return response()->json(['entity' => $accionistasyRl, 'message' => 'Se ha encontrado todos los Accionistas y Representantes legales', 'status' => '200', 'color' => 'info'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => '503', 'color' => 'danger'], 503);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!esTokenValido($request)) {
            return response()->json(['message' => 'Petición sin token válido!', 'status' => '401', 'color' => 'danger'], 401);
        }

        header('Access-Control-Allow-Origin: *');
        $validator = Validator::make($request->all(), [
            'nombre_uno' => 'required',
            'apellido_uno' => 'required',
            'id_tipo_persona' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Error al validar los campos', 'status' => '422', 'color' => 'danger', 'error' => $validator->errors()], 422);
        }
        try {
            DB::beginTransaction();

            $nombre_uno = $request->nombre_uno;
            $nombre_dos = $request->nombre_dos;
            $apellido_uno = $request->apellido_uno;
            $apellido_dos = $request->apellido_dos;
            $nacionalidad = $request->nacionalidad;
            $cedula_pasaporte = $request->cedula_pasaporte;
            $tipo_rif = $request->tipo_rif;
            $rif = $request->rif;
            $id_tipo_persona = $request->id_tipo_persona;
            $idEmpresa = $request->id_empresa;
            $idrol = $request->id_rol;
            $idTipoEmpresa = $request->idTipoEmpresa;
            $horaActual = Carbon::now('America/Caracas')->toDateTimeString();

            $AR = "";
            if ($id_tipo_persona == 1) {
                $AR = "Representante Legal";
            } elseif($id_tipo_persona == 2) {
                $AR = "Accionista";
            }else{
                ($id_tipo_persona == 3); 
                $AR = "Conductor";
            }

            $repAccUnico = DB::select("SELECT cedula_pasaporte, id_tipo_persona FROM accionistas
                WHERE cedula_pasaporte='$cedula_pasaporte'
                AND id_tipo_persona=$id_tipo_persona
                AND id_empresa=$idEmpresa ");

            if (count($repAccUnico) == 0) {

                $accionista = [
                    'nombre_uno' => $nombre_uno,
                    'nombre_dos' => $nombre_dos,
                    'apellido_uno' => $apellido_uno,
                    'apellido_dos' => $apellido_dos,
                    'nacionalidad' => $nacionalidad,
                    'cedula_pasaporte' => $cedula_pasaporte,
                    'tipo_rif' => $tipo_rif,
                    'rif' => $rif,
                    'id_tipo_persona' => $id_tipo_persona,
                    'id_empresa' => $idEmpresa,
                ];

                $idAccionista = DB::table('accionistas')->insertGetId($accionista);

                $controllerTelefonos = new TelefonoController();
                $controllerTelefonos->storeAccionista($request, $idAccionista);

                $controllerCorreos = new CorreoController();
                $controllerCorreos->storeAccionista($request, $idAccionista);

                $accionistasyRl = DB::select("SELECT acc.id, acc.nombre_uno, acc.nombre_dos, acc.apellido_uno, acc.apellido_dos, acc.nacionalidad, acc.cedula_pasaporte, acc.tipo_rif, acc.rif, acc.id_empresa, acc.id_tipo_persona, tip.nombre, us.id_rol
                FROM accionistas acc, tipo_persona tip, usuario us
                WHERE tip.id = acc.id_tipo_persona
                AND us.id_rol= $idrol
                AND acc.id=" . $idAccionista);

                $telefonosLocales = DB::select("SELECT cod_num, numero, id_accionistas, id_empresa FROM telefonos WHERE id_accionistas=$idAccionista AND SUBSTRING(cod_num,1,2)='02'");
                $telefonosCelulares = DB::select("SELECT cod_num, numero, id_accionistas, id_empresa FROM telefonos WHERE id_accionistas=$idAccionista AND SUBSTRING(cod_num,1,2)='04'");
                $correos = DB::select("SELECT correo, id_accionistas, id_empresa FROM correos WHERE id_accionistas=$idAccionista");

                $accionistasyRl[0]->telefonosLocales = $telefonosLocales;
                $accionistasyRl[0]->telefonosCelulares = $telefonosCelulares;
                $accionistasyRl[0]->correos = $correos;

                $EstatusEmp = DB::select("SELECT id FROM estatus_empresa WHERE id_empresa=?", [$idEmpresa]);

                if (count($EstatusEmp) > 0) {
                    if ($nombre_uno != null && $apellido_uno != null && $cedula_pasaporte != null && $idTipoEmpresa != null && $telefonosLocales != null && (($idTipoEmpresa != 4 && $telefonosCelulares != null && $tipo_rif != null && $rif != null && $nacionalidad != null) || ($idTipoEmpresa == 4)) && $correos != null && $id_tipo_persona == '1') {
                        DB::UPDATE("UPDATE estatus_empresa SET estatus = JSON_REPLACE(estatus, '$.3', $idrol) WHERE id_empresa= ?", [$idEmpresa]);
                    } else {
                        if ($nombre_uno != null && $apellido_uno != null && $cedula_pasaporte != null  && $idTipoEmpresa != null && $telefonosLocales != null && (($idTipoEmpresa != 4 && $telefonosCelulares != null && $tipo_rif != null && $rif != null && $nacionalidad != null) || ($idTipoEmpresa == 4)) && $correos != null && $id_tipo_persona == '2') {
                            DB::UPDATE("UPDATE estatus_empresa SET estatus = JSON_REPLACE(estatus, '$.4', $idrol) WHERE id_empresa= ?", [$idEmpresa]);
                        } else {
                            if ($id_tipo_persona == '1') {
                                DB::UPDATE("UPDATE estatus_empresa SET estatus = JSON_REPLACE(estatus, '$.3', 0) WHERE id_empresa= ?", [$idEmpresa]);
                            } else {
                                if ($id_tipo_persona == '2') {
                                    DB::UPDATE("UPDATE estatus_empresa SET estatus = JSON_REPLACE(estatus, '$.4', 0) WHERE id_empresa= ?", [$idEmpresa]);
                                }
                            }
                        }

                    }
                } else {
                    EstatusEmpresa::Create(
                        [
                            'id_empresa' => $idEmpresa,
                        ],
                        [
                            'estatus' => '{"1": 0, "2": 0, "3": 0, "4": 0, "5": 0}',
                            'descripcion' => 'ESTATUS DE Accionista o Rp' . strtoupper($cedula_pasaporte),
                            'id_empresa' => $idEmpresa,
                            'estatus_linea' => '1:0',
                            'updated_at' => $horaActual,
                        ]);
                }

                DB::commit();
                return response()->json(['entity' => $accionistasyRl[0], 'message' => 'Datos ' . $AR . ' guardados exitosamente.', 'status' => '200', 'color' => 'success'], 200);
            } else {
                return response()->json(['entity' => $repAccUnico, 'message' => 'Usuario ya registrado como ' . $AR, 'status' => '202', 'color' => 'warning'], 200);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage(), 'status' => '503', 'color' => 'danger'], 503);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        if (!esTokenValido($request)) {
            response()->json(['message' => 'Petición sin token válido!', 'status' => '401', 'color' => 'danger'], 401);
        }

        header('Access-Control-Allow-Origin: *');
        try {
            $accionistasyRl = DB::select("SELECT acc.id, acc.nombre_uno, acc.apellido_uno, acc.cedula_pasaporte, acc.rif, acc.id_tipo_persona, tip.nombre
            FROM accionistas acc, tipo_persona tip
            WHERE tip.id = acc.id_tipo_persona
            AND acc.id=?", [$id]);

            $telefonos = DB::select("SELECT id, cod_num, numero, id_accionistas FROM telefonos WHERE id_accionistas=?", [$id]);
            $correos = DB::select("SELECT id, correo, id_accionistas FROM correos WHERE id_accionistas=?", [$id]);

            array_push($accionistasyRl, ['telefonos' => $telefonos]);
            array_push($accionistasyRl, ['correos' => $correos]);

            if (count($accionistasyRl) > 0) {
                return response()->json(['entity' => $accionistasyRl, 'message' => 'Se ha encontrado un Accionista.', 'status' => '200', 'color' => 'success'], 200);
            } else {
                return response()->json(['entity' => $accionistasyRl, 'message' => 'No se encontro.', 'status' => '200', 'color' => 'warning'], 200);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => '503', 'color' => 'danger'], 503);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showByIdEmpresa(Request $request, $tipoPersona, $id)
    {
        if (!esTokenValido($request)) {
            response()->json(['message' => 'Petición sin token válido!', 'status' => '401', 'color' => 'danger'], 401);
        }

        header('Access-Control-Allow-Origin: *');
        try {

            $AR = "";
            if ($tipoPersona == 1) {
                $AR = "Representante Legal";
            } elseif($tipoPersona == 2) {
                $AR = "Accionista";
            }else{
                ($tipoPersona == 3); 
                $AR = "Conductor";
            }

            $accionistasyRl = DB::select("SELECT acc.id, acc.nombre_uno, acc.nombre_dos, acc.apellido_uno, acc.apellido_dos, nacionalidad, acc.cedula_pasaporte, acc.tipo_rif, acc.rif, acc.id_empresa, acc.id_tipo_persona, tip.nombre
            FROM accionistas acc, tipo_persona tip
            WHERE tip.id = acc.id_tipo_persona
            AND acc.id_tipo_persona=?
            AND acc.id_empresa=?
            ORDER BY acc.id DESC", [$tipoPersona, $id]);

            $naccionistas = count($accionistasyRl);
            if ($naccionistas > 0) {
                for ($i = 0; $i < $naccionistas; $i++) {
                    $idAccionista = $accionistasyRl[$i]->id;
                    $telefonosLocales = DB::select("SELECT tel.id, tel.cod_num, tel.numero, tel.id_accionistas FROM telefonos tel, accionistas acc WHERE acc.id=id_accionistas AND id_accionistas=? AND SUBSTRING(cod_num,1,2)='02'", [$idAccionista]);
                    $accionistasyRl[$i]->telefonosLocales = $telefonosLocales;

                    $telefonosCelulares = DB::select("SELECT tel.id, tel.cod_num, tel.numero, tel.id_accionistas FROM telefonos tel, accionistas acc WHERE acc.id=id_accionistas AND id_accionistas=? AND SUBSTRING(cod_num,1,2)='04'", [$idAccionista]);
                    $accionistasyRl[$i]->telefonosCelulares = $telefonosCelulares;

                    $correos = DB::select("SELECT cor.id ,cor.correo, cor.id_accionistas FROM correos cor, accionistas acc WHERE acc.id=id_accionistas AND id_accionistas=$idAccionista");
                    $accionistasyRl[$i]->correos = $correos;

                }
                return response()->json(['entity' => $accionistasyRl, 'message' => 'Se encontro '. count($accionistasyRl). ' ' .$AR, 'status' => '200', 'color' => 'info'], 200);

            } else {
                return response()->json(['entity' => $accionistasyRl, 'message' => 'No se encontró ningun '.$AR, 'status' => '202', 'color' => 'warning'], 202);
            }

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => '503', 'color' => 'danger'], 503);
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showByIdEmpresaCond(Request $request, $tipoPersona, $id)
    {
        if (!esTokenValido($request)) {
            response()->json(['message' => 'Petición sin token válido!', 'status' => '401', 'color' => 'danger'], 401);
        }

        header('Access-Control-Allow-Origin: *');
        try {

            $AR = "";
            if ($tipoPersona == 1) {
                $AR = "Representante Legal";
            } elseif($tipoPersona == 2) {
                $AR = "Accionista";
            }else{
                ($tipoPersona == 3); 
                $AR = "Conductor";
            }

            $accionistasyRl = DB::select("SELECT * FROM accionistas ac 
            WHERE (NOT EXISTS (SELECT id_accionista FROM guia WHERE id_accionista = ac.id)) 
            AND ac.id_tipo_persona =? 
            AND ac.id_empresa =?
            ORDER BY ac.id DESC", [$tipoPersona, $id]);

            $naccionistas = count($accionistasyRl);
            if ($naccionistas > 0) {
                for ($i = 0; $i < $naccionistas; $i++) {
                    $idAccionista = $accionistasyRl[$i]->id;
                    $telefonosLocales = DB::select("SELECT tel.id, tel.cod_num, tel.numero, tel.id_accionistas FROM telefonos tel, accionistas acc WHERE acc.id=id_accionistas AND id_accionistas=? AND SUBSTRING(cod_num,1,2)='02'", [$idAccionista]);
                    $accionistasyRl[$i]->telefonosLocales = $telefonosLocales;

                    $telefonosCelulares = DB::select("SELECT tel.id, tel.cod_num, tel.numero, tel.id_accionistas FROM telefonos tel, accionistas acc WHERE acc.id=id_accionistas AND id_accionistas=? AND SUBSTRING(cod_num,1,2)='04'", [$idAccionista]);
                    $accionistasyRl[$i]->telefonosCelulares = $telefonosCelulares;

                    $correos = DB::select("SELECT cor.id ,cor.correo, cor.id_accionistas FROM correos cor, accionistas acc WHERE acc.id=id_accionistas AND id_accionistas=$idAccionista");
                    $accionistasyRl[$i]->correos = $correos;

                }
                return response()->json(['entity' => $accionistasyRl, 'message' => 'Se encontro '. count($accionistasyRl). ' ' .$AR, 'status' => '200', 'color' => 'info'], 200);

            } else {
                return response()->json(['entity' => $accionistasyRl, 'message' => 'No se encontró ningun '.$AR, 'status' => '202', 'color' => 'warning'], 202);
            }

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => '503', 'color' => 'danger'], 503);
        }

    }    

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        if (!esTokenValido($request)) {
            response()->json(['message' => 'Petición sin token válido!', 'status' => '401', 'color' => 'danger'], 401);
        }

        header('Access-Control-Allow-Origin: *');
        try {
            $accionistasyRl = DB::select("SELECT acc.id, acc.nombre_uno, acc.nombre_dos, acc.apellido_uno, acc.apellido_dos, acc.nacionalidad, acc.cedula_pasaporte, acc.tipo_rif, acc.rif, acc.id_tipo_persona, tip.nombre
            FROM accionistas acc, tipo_persona tip
            WHERE tip.id = acc.id_tipo_persona
            AND acc.id=?", [$id]);

            $telefonos = DB::select("SELECT cod_num, numero, id_accionistas FROM telefonos WHERE id_accionistas=?", [$id]);
            $correos = DB::select("SELECT correo, id_accionistas FROM correos WHERE id_accionistas=?", [$id]);

            array_push($accionistasyRl, ['telefonos' => $telefonos]);
            array_push($accionistasyRl, ['correos' => $correos]);

            if (count($accionistasyRl) > 0) {
                return response()->json(['entity' => $accionistasyRl, 'message' => 'Se ha encontrado un Accionista o Representante Legal', 'status' => '200', 'color' => 'info'], 200);
            } else {
                return response()->json(['entity' => $accionistasyRl, 'message' => 'No se encontró ninguna Accionista.', 'status' => '200', 'color' => 'warning'], 200);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => '503', 'color' => 'danger'], 503);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!esTokenValido($request)) {
            return response()->json(['message' => 'Petición sin token válido!', 'status' => '401', 'color' => 'danger'], 401);
        }

        header("Access-Control-Allow-Origin: *");
        $validator = Validator::make($request->all(), [
            'nombre_uno' => 'required',
            'apellido_uno' => 'required',
            'id_tipo_persona' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Error al validar los campos', 'status' => '422', 'color' => 'danger', 'error' => $validator->errors()], 422);
        }
        try {

            $nombre_uno = $request->nombre_uno;
            $nombre_dos = $request->nombre_dos;
            $apellido_uno = $request->apellido_uno;
            $apellido_dos = $request->apellido_dos;
            $nacionalidad = $request->nacionalidad;
            $cedula_pasaporte = $request->cedula_pasaporte;
            $tipo_rif = $request->tipo_rif;
            $rif = $request->rif;
            $id_tipo_persona = $request->id_tipo_persona;
            $idEmpresa = $request->id_empresa;
            $idrol = $request->id_rol;
            $idTipoEmpresa = $request->idTipoEmpresa;
            $horaActual = Carbon::now('America/Caracas')->toDateTimeString();
            $updated_at = Carbon::now()->toDateTimeString();

            if ($nombre_uno == null) {
                $nombre_uno = "";
            }

            if ($nombre_dos == null) {
                $nombre_dos = "";
            }

            if ($apellido_uno == null) {
                $apellido_uno = "";
            }

            if ($apellido_dos == null) {
                $apellido_dos = "";
            }

            if ($cedula_pasaporte == null) {
                $cedula_pasaporte = "";
            }

            if ($rif == null) {
                $rif = "";
            }

            $AR = "";
            if ($id_tipo_persona == 1) {
                $AR = "Representante Legal";
            } elseif($id_tipo_persona == 2) {
                $AR = "Accionista";
            }else{
                ($id_tipo_persona == 3); 
                $AR = "Conductor";
            }

            DB::table('accionistas')->where('id', $id)->update([
                'nombre_uno' => $nombre_uno,
                'nombre_dos' => $nombre_dos,
                'apellido_uno' => $apellido_uno,
                'apellido_dos' => $apellido_dos,
                'nacionalidad' => $nacionalidad,
                'cedula_pasaporte' => $cedula_pasaporte,
                'tipo_rif' => $tipo_rif,
                'rif' => $rif,
                'id_tipo_persona' => $id_tipo_persona,
                'updated_at' => $updated_at]);

            $idAccionista = $id;

            DB::delete('DELETE FROM telefonos WHERE id_accionistas=?', [$idAccionista]);
            DB::delete('DELETE FROM correos WHERE id_accionistas=?', [$idAccionista]);

            $controllerTelefonos = new TelefonoController();
            $controllerTelefonos->storeAccionista($request, $idAccionista);

            $controllerCorreos = new CorreoController();
            $controllerCorreos->storeAccionista($request, $idAccionista);

            $accionistasyRl = DB::select("SELECT acc.id, acc.nombre_uno, acc.nombre_dos, acc.apellido_uno, acc.apellido_dos, acc.nacionalidad, acc.cedula_pasaporte, acc.tipo_rif, acc.rif, acc.id_empresa, acc.id_tipo_persona, tip.nombre, us.id_rol
            FROM accionistas acc, tipo_persona tip, usuario us
            WHERE tip.id = acc.id_tipo_persona
            AND us.id_rol= $idrol
            AND acc.id=" . $idAccionista);

            $telefonosLocales = DB::select("SELECT cod_num, numero, id_accionistas FROM telefonos WHERE id_accionistas=$idAccionista AND SUBSTRING(cod_num,1,2)='02'");
            $telefonosCelulares = DB::select("SELECT cod_num, numero, id_accionistas FROM telefonos WHERE id_accionistas=$idAccionista AND SUBSTRING(cod_num,1,2)='04'");
            $correos = DB::select("SELECT correo, id_accionistas FROM correos WHERE id_accionistas=$idAccionista");

            $accionistasyRl[0]->telefonosLocales = $telefonosLocales;
            $accionistasyRl[0]->telefonosCelulares = $telefonosCelulares;
            $accionistasyRl[0]->correos = $correos;

            $EstatusEmp = DB::select("SELECT id FROM estatus_empresa WHERE id_empresa=?", [$idEmpresa]);

            if (count($EstatusEmp) > 0) {
                if ($nombre_uno != null && $apellido_uno != null && $cedula_pasaporte != null && $idTipoEmpresa != null && $telefonosLocales != null && (($idTipoEmpresa != 4 && $telefonosCelulares != null && $tipo_rif != null && $rif != null && $nacionalidad != null) || ($idTipoEmpresa == 4)) && $correos != null && $id_tipo_persona == '1') {
                    DB::UPDATE("UPDATE estatus_empresa SET estatus = JSON_REPLACE(estatus, '$.3', $idrol) WHERE id_empresa= ?", [$idEmpresa]);
                } else {
                    if ($nombre_uno != null && $apellido_uno != null && $cedula_pasaporte != null  && $idTipoEmpresa != null && $telefonosLocales != null && (($idTipoEmpresa != 4 && $telefonosCelulares != null && $tipo_rif != null && $rif != null && $nacionalidad != null) || ($idTipoEmpresa == 4)) && $correos != null && $id_tipo_persona == '2') {
                        DB::UPDATE("UPDATE estatus_empresa SET estatus = JSON_REPLACE(estatus, '$.4', $idrol) WHERE id_empresa= ?", [$idEmpresa]);
                    } else {
                        if ($id_tipo_persona == '1') {
                            DB::UPDATE("UPDATE estatus_empresa SET estatus = JSON_REPLACE(estatus, '$.3', 0) WHERE id_empresa= ?", [$idEmpresa]);
                        } else {
                            if ($id_tipo_persona == '2') {
                                DB::UPDATE("UPDATE estatus_empresa SET estatus = JSON_REPLACE(estatus, '$.4', 0) WHERE id_empresa= ?", [$idEmpresa]);
                            }
                        }
                    }

                }
            } else {
                EstatusEmpresa::Create(
                    [
                        'id_empresa' => $idEmpresa,
                    ],
                    [
                        'estatus' => '{"1": 0, "2": 0, "3": 0, "4": 0, "5": 0}',
                        'descripcion' => 'ESTATUS DE Accionista o Rp' . strtoupper($cedula_pasaporte),
                        'id_empresa' => $idEmpresa,
                        'estatus_linea' => '1:0',
                        'updated_at' => $horaActual,
                    ]);
            }

            return response()->json(['entity' => $accionistasyRl[0], 'message' => $AR . ' se ha actualizado exitosamente!', 'status' => '200', 'color' => 'success'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'error' => 'Accionista o RP no se Actualizo', 'status' => '401', 'color' => 'danger'], 401);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        if (!esTokenValido($request)) {
            response()->json(['message' => 'Petición sin token válido!', 'status' => '401', 'color' => 'danger'], 401);
        }

        header('Access-Control-Allow-Origin: *');
        try {

            $idEmpresa = $request->id_empresa;
            $id_tipo_persona = $request->id_tipo_persona;
            $AR = "";
            if ($id_tipo_persona == 1) {
                $AR = "Representante Legal";
            } elseif($id_tipo_persona == 2) {
                $AR = "Accionista";
            }else{
                ($id_tipo_persona == 3); 
                $AR = "Conductor";
            }
            DB::table('accionistas')->where('id', $id)->delete();
            $EstatusEmp = DB::select("SELECT id FROM estatus_empresa WHERE id_empresa=?", [$idEmpresa]);
            $tipdelete = DB::select("SELECT id_tipo_persona FROM accionistas WHERE id_tipo_persona=$id_tipo_persona AND id_empresa=?", [$idEmpresa]);

            if (count($EstatusEmp) > 0) {
                if ($id_tipo_persona == 1 && count($tipdelete) == 0) {
                    DB::UPDATE("UPDATE estatus_empresa SET estatus = JSON_REPLACE(estatus, '$.3', 0) WHERE id_empresa= ?", [$idEmpresa]);
                } else {
                    if ($id_tipo_persona == 2 && count($tipdelete) == 0) {
                        DB::UPDATE("UPDATE estatus_empresa SET estatus = JSON_REPLACE(estatus, '$.4', 0) WHERE id_empresa= ?", [$idEmpresa]);
                    }
                }
            }
            return response()->json(['message' => $AR . ' se elimino exitosamente!', 'status' => '200', 'color' => 'warning'], 200);
        } catch (\Exception $e) {
            \Log::info('Error al eliminar registro: ' . $e->getCode());
            $codigoError = $e->getCode();
            if ($codigoError == 23000) {
                return response()->json(['message' => 'No se puede eliminar el '.$AR.' porque tiene un documento(s) cargado(s)', 'status' => '503', 'color' => 'danger'], 503);
            } else {
            return response()->json(['message' => $e->getMessage(), 'status' => '503', 'color' => 'danger'], 503);
            }
        }
    }
}
