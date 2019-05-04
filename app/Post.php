<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $table="POST";

    protected $primaryKey="ID";
    
    protected $fillable = [
        
        'ID', 
        'Creador', 
        'titulo',
        'cuerpo',
    ];
}
