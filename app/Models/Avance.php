<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Avance extends Model
{
    protected $table = 'avances';
    protected $primaryKey = 'id_avance';

    protected $fillable = [
        'id_empresa',
        'descripcion',
        'fecha',
        'user_id',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_empresa', 'id_empresa');
    }

    // Alias por si en algún lado viejo todavía llaman proyecto()
    public function proyecto()
    {
        return $this->empresa();
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'user_id', 'id_usuario');
    }

    public function historial()
    {
        return $this->hasMany(AvanceHistorial::class, 'id_avance', 'id_avance')
            ->orderByDesc('created_at')
            ->orderByDesc('id_historial');
    }
}
