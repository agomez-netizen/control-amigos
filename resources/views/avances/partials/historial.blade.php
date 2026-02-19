@php
  use Carbon\Carbon;

  // Convierte HTML a texto plano (para no ver <p> etc.)
  $plain = function ($value) {
      $value = $value ?? '';
      $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
      $value = strip_tags($value);
      $value = preg_replace('/[ \t]+/', ' ', $value);
      $value = trim($value);
      return $value;
  };

  // Helpers de fecha
  $toDateInput = function ($value) {
      if (!$value) return '';
      try {
          // asegura Y-m-d para <input type="date">
          return Carbon::parse($value)->format('Y-m-d');
      } catch (\Throwable $e) {
          return (string)$value;
      }
  };

  $toDateHuman = function ($value) {
      if (!$value) return '';
      try {
          return Carbon::parse($value)->format('d/m/Y');
      } catch (\Throwable $e) {
          return (string)$value;
      }
  };

  $descPlano = $plain($avance->descripcion);

  $fechaHuman = $toDateHuman($avance->fecha);
  $fechaInput = $toDateInput($avance->fecha);
@endphp

<div class="mb-3">
  <div class="d-flex justify-content-between align-items-start">
    <div>
      <div class="fw-bold">{{ $avance->empresa->nombre ?? 'Empresa' }}</div>
      <div class="text-muted small">
        Avance #{{ $avance->id_avance }} • {{ $fechaHuman }} •
        {{ $avance->usuario->nombre ?? 'Usuario' }} {{ $avance->usuario->apellido ?? '' }}
      </div>
    </div>
  </div>
</div>

<hr>

<form method="POST" action="{{ route('avances.update', $avance->id_avance) }}">
  @csrf
  @method('PUT')

  <div class="row g-2 mb-3">
    <div class="col-md-4">
      <label class="form-label">Fecha</label>
      <input type="date" class="form-control" name="fecha" value="{{ $fechaInput }}">
    </div>

    <div class="col-md-8">
      <label class="form-label">Empresa</label>
      <select class="form-select" name="id_empresa" required>
        @foreach($empresas as $e)
          <option value="{{ $e->id_empresa }}"
            {{ (string)$avance->id_empresa === (string)$e->id_empresa ? 'selected' : '' }}>
            {{ $e->nombre }}
          </option>
        @endforeach
      </select>
    </div>
  </div>

  <div class="mb-3">
    <label class="form-label">Descripción</label>
    <textarea class="form-control" name="descripcion" rows="5" required>{{ $descPlano }}</textarea>
  </div>

  <div class="d-flex justify-content-end gap-2">
    <button type="submit" class="btn btn-primary">Guardar cambios</button>
  </div>
</form>

<hr>

<h6 class="mb-2">Bitácora de cambios</h6>

@if($historial->isEmpty())
  <div class="text-muted">Sin historial (todavía 😅).</div>
@else
  <div class="table-responsive">
    <table class="table table-sm align-middle">
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Usuario</th>
          <th>Campo</th>
          <th>Antes</th>
          <th>Después</th>
        </tr>
      </thead>
      <tbody>
        @foreach($historial as $h)
          @php
            // created_at ya viene casteado si pusiste $casts en el modelo.
            // Si no, aquí lo dejamos bonito igual:
            $fechaHist = $h->created_at;
            try { $fechaHist = Carbon::parse($h->created_at)->format('d/m/Y H:i'); } catch (\Throwable $e) {}

            $antesRaw = $h->valor_anterior;
            $despRaw  = $h->valor_nuevo;

            // Si el campo fue "fecha", lo formateamos como fecha humana
            if (($h->campo ?? '') === 'fecha') {
              $antes = $toDateHuman($antesRaw);
              $desp  = $toDateHuman($despRaw);
            } else {
              $antes = $plain($antesRaw);
              $desp  = $plain($despRaw);
            }
          @endphp
          <tr>
            <td class="text-nowrap">{{ $fechaHist }}</td>
            <td class="text-nowrap">
              {{ $h->usuario->nombre ?? 'Usuario' }} {{ $h->usuario->apellido ?? '' }}
            </td>
            <td class="text-nowrap">{{ $h->campo }}</td>
            <td style="max-width:320px; white-space:pre-wrap;">{{ $antes }}</td>
            <td style="max-width:320px; white-space:pre-wrap;">{{ $desp }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endif
