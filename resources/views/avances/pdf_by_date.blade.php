<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Avances por fecha</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
    .header { margin-bottom: 12px; }
    .title { font-size: 18px; font-weight: 700; margin: 0 0 6px; }
    .filters { color:#555; font-size: 11px; margin-bottom: 10px; }

    .date-badge { display:inline-block; background:#111; color:#fff; padding:3px 8px; border-radius:10px; font-size:11px; font-weight:700; }
    .count { color:#666; margin-left:8px; font-size:11px; }

    .card { border:1px solid #e5e7eb; border-radius:10px; padding:10px 12px; margin:8px 0 14px; }
    .row { display:flex; justify-content:space-between; gap:10px; }
    .proj { font-weight:700; font-size:13px; margin:0 0 6px; }
    .desc { white-space: pre-wrap; margin:0 0 8px; }
    .meta { color:#555; font-size:11px; }
    .time { color:#555; font-size:11px; text-align:right; }
    hr { border:none; border-top:1px solid #eee; margin:10px 0; }
  </style>
</head>
<body>

  <div class="header">
    <p class="title">Avances por fecha</p>
    <div class="filters">
      Empresa: <strong>{{ $idEmpresa ? ($empresas->firstWhere('id_empresa', $idEmpresa)->nombre ?? '—') : 'Todos' }}</strong>
      | Desde: <strong>{{ $desde ?: '—' }}</strong>
      | Hasta: <strong>{{ $hasta ?: '—' }}</strong>
    </div>
  </div>

  @foreach($grouped as $fecha => $items)
    <div>
      <span class="date-badge">{{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</span>
      <span class="count">({{ $items->count() }} avances)</span>
    </div>

    @foreach($items as $a)
      @php
        // ✅ Limpieza fuerte: quita HTML + decodifica entidades + respeta saltos
        $desc = html_entity_decode($a->descripcion ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $desc = strip_tags($desc);
        $desc = preg_replace("/\r\n|\r|\n/", "\n", $desc);
        $desc = preg_replace('/[ \t]+/', ' ', $desc);
        $desc = trim($desc);
      @endphp

      <div class="card">
        <div class="row">
          <div style="flex:1">
            <p class="proj">{{ $a->empresa->nombre ?? '—' }}</p>
            <div class="desc">{{ $desc }}</div>
            <div class="meta">
              ✍ {{ $a->usuario->nombre ?? 'Usuario eliminado' }} {{ $a->usuario->apellido ?? '' }}
            </div>
          </div>

          <div class="time">
            🕒 {{ \Carbon\Carbon::parse($a->created_at)->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
          </div>
        </div>
      </div>
    @endforeach

    <hr>
  @endforeach

</body>
</html>
