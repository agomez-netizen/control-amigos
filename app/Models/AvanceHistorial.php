<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvanceHistorial extends Model
{
    protected $table = 'avance_historial';
    protected $primaryKey = 'id_historial';
    public $timestamps = false; // solo tenemos created_at

    protected $fillable = [
        'id_avance',
        'user_id',
        'campo',
        'valor_anterior',
        'valor_nuevo',
        'created_at',
    ];

      protected $casts = [
        'created_at' => 'datetime:d/m/Y H:i',
    ];
    
    public function avance()
    {
        return $this->belongsTo(Avance::class, 'id_avance', 'id_avance');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'user_id', 'id_usuario');
    }
}
