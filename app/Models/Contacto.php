<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contacto extends Model
{
    protected $table = 'contactos';
    protected $primaryKey = 'id_contacto';
    public $timestamps = true;

    protected $fillable = [
        'id_empresa',
        'nombre','apellido','telefono','celular','email','direccion',
        'puesto','departamento','titulo','notas','activo'
    ];
}
