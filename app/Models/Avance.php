<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Avance extends Model
{
    protected $table = 'avances';
    protected $primaryKey = 'id_avance';

    protected $fillable = [
        'id_empresa',
        'id_contacto',   // agrega esto
        'id_usuario',
        'descripcion',
        'fecha',
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
        return $this->belongsTo(Usuario::class, 'id_usuario', 'id_usuario');
    }

    public function historial()
    {
        return $this->hasMany(AvanceHistorial::class, 'id_avance', 'id_avance')
            ->orderByDesc('created_at')
            ->orderByDesc('id_historial');
    }

    public function contacto()
    {
        return $this->belongsTo(\App\Models\Contacto::class, 'id_contacto', 'id_contacto');
    }

}
