<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpresaTelefono extends Model
{
    protected $table = 'empresa_telefonos';
    protected $fillable = ['id_empresa','telefono','nota','activo'];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_empresa', 'id_empresa');
    }
}
