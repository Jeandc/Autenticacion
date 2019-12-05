<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPassword;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = "usuario";

    protected $primaryKey = "id";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'usuario',
        'nacionalidad',
        'cedula',
        'clave',
        'correo',
        'numero_intentos',
        'id_rol',
        'id_estatus_usuario',
        'remember_token',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'clave', 'remember_token'
    ];

    public function getAuthPassword()
    {
        return $this->clave;
    }

    public function getEmailForPasswordReset()
    {
        return $this->correo;
    }

}
