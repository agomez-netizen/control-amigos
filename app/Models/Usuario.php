<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Usuario extends Authenticatable
{
    protected $table = 'usuarios';
    protected $primaryKey = 'id_usuario';
    public $timestamps = false;

    protected $fillable = [
        'nombre', 'apellido', 'usuario', 'pass', 'id_rol', 'estado'
    ];

    protected $hidden = ['pass'];

  
    public function getAuthPassword()
    {
        return $this->pass;
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id_rol');
    }

    public function empresas()
    {
        return $this->belongsToMany(
            \App\Models\Empresa::class,
            'empresa_usuario',
            'id_usuario',
            'id_empresa'
        )->withTimestamps();
    }

}
