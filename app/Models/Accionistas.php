<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Accionistas extends Model
{
    protected $table="reg_accionistas";

    protected $primaryKey="id";

    protected $fillable = [
    	'id',
        'nombre_uno',
        'nombre_dos',
        'apellido_uno',
        'apellido_dos',
        'nacionalidad',
        'cedula_pasaporte',
        'tipo_rif',
        'rif',
        'id_cargo',
        'id_empresa',
        'created_at',
        'updated_at',
    ];
}
