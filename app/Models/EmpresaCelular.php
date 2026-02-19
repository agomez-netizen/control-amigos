<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpresaCelular extends Model
{
    protected $table = 'empresa_celulares';
    protected $fillable = ['id_empresa','celular','nota','activo'];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_empresa', 'id_empresa');
    }
}
