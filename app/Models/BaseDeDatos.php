<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaseDeDatos extends Model
{
    protected $table = 'base_de_datos';
    protected $primaryKey = 'id_base_datos';

    public $timestamps = true;

    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    public function empresas()
    {
        return $this->hasMany(Empresa::class, 'id_base_datos', 'id_base_datos');
    }

    public function usuarios()
    {
        return $this->belongsToMany(
            \App\Models\Usuario::class,
            'usuario_base_datos',
            'id_base_datos',
            'id_usuario'
        );
    }

}
