<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoEmpresa extends Model
{
    protected $table = 'tipo_empresa';
    protected $primaryKey = 'id_tipo_empresa';
    public $timestamps = true;

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];
}
