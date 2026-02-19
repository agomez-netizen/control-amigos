<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $table = 'empresa';
    protected $primaryKey = 'id_empresa';

    public $timestamps = true;

    protected $fillable = [
        'nombre',
        'activo',
        'apellido_contacto',
        'asistente',
        'correlativo_carta',
        'departamento',
        'descripcion',
        'detalles',
        'direccion',
        'email',
        'gestor_aapos',
        'id_base_datos',
        'municipio',
        'nombre_contacto',
        'notas',
        'pais',
        'puesto',
        'sitio_web',
        'tipo_empresa',
        'titulo',
    ];


    public function baseDeDatos() { return $this->belongsTo(BaseDeDatos::class, 'id_base_datos', 'id_base_datos'); }
    public function tipoEmpresa() { return $this->belongsTo(TipoEmpresa::class, 'id_tipo_empresa', 'id_tipo_empresa'); }
    public function contactos() { return $this->hasMany(Contacto::class, 'id_empresa', 'id_empresa'); }

    public function usuarios()
    {
        return $this->belongsToMany(
            \App\Models\Usuario::class,
            'empresa_usuario',
            'id_empresa',
            'id_usuario'
        )->withTimestamps();
    }
}
