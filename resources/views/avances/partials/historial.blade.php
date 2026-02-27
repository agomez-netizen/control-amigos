@php
  use Carbon\Carbon;

  // Convierte HTML a texto plano (para no ver <p>, <br>, etc.)
  $plain = function ($value) {
      $value = $value ?? '';
      $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
      $value = strip_tags($value);
      // Normaliza espacios y saltos
      $value = preg_replace("/\r\n|\r|\n/", "\n", $value);
      $value = preg_replace('/[ \t]+/', ' ', $value);
      $value = preg_replace("/\n{3,}/", "\n\n", $value);
      return trim($value);
  };

  // Helpers de fecha
  $toDateInput = function ($value) {
      if (!$value) return '';
      try {
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

  // Descripción del avance en texto plano (para textarea)
  $descPlano = $plain($avance->descripcion);

  $fechaHuman = $toDateHuman($avance->fecha);
  $fechaInput = $toDateInput($avance->fecha);

  // Contacto (si existe)
  $contactoNombre = '';
  $contactoPuesto = '';
  if (!empty($avance->contacto)) {
      $contactoNombre = trim(($avance->contacto->nombre ?? '') . ' ' . ($avance->contacto->apellido ?? ''));
      $contactoPuesto = $avance->contacto->puesto ?? '';
  }
@endphp

<div class="mb-3">
  <div class="d-flex justify-content-between align-items-start">
    <div>
      <div class="fw-bold">{{ $avance->empresa->nombre ?? 'Empresa' }}</div>

      {{-- contacto (si hay) --}}
      @if($contactoNombre)
        <div class="text-muted small">
          <i class="bi bi-person-badge me-1"></i>
          {{ $contactoNombre }}@if($contactoPuesto) — {{ $contactoPuesto }}@endif
        </div>
      @endif

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
    <div class="form-text">Se guardará como texto (sin etiquetas HTML).</div>
  </div>

  <div class="d-flex justify-content-end gap-2">
    <button type="submit" class="btn btn-primary">Guardar cambios</button>
  </div>
</form>

<hr>

<h6 class="mb-2">Bitácora de cambios</h6>

@if($historial->isEmpty())
  <div class="text-muted">Sin historial.</div>
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
            $fechaHist = $h->created_at;
            try { $fechaHist = Carbon::parse($h->created_at)->format('d/m/Y H:i'); } catch (\Throwable $e) {}

            $antesRaw = $h->valor_anterior;
            $despRaw  = $h->valor_nuevo;

            $campo = (string)($h->campo ?? '');

            if ($campo === 'fecha') {
              $antes = $toDateHuman($antesRaw);
              $desp  = $toDateHuman($despRaw);
            } else {
              // para descripcion y cualquier otro campo: limpiar HTML
              $antes = $plain($antesRaw);
              $desp  = $plain($despRaw);
            }
          @endphp
          <tr>
            <td class="text-nowrap">{{ $fechaHist }}</td>
            <td class="text-nowrap">
              {{ $h->usuario->nombre ?? 'Usuario' }} {{ $h->usuario->apellido ?? '' }}
            </td>
            <td class="text-nowrap">{{ $campo }}</td>
            <td style="max-width:320px; white-space:pre-wrap;">{{ $antes }}</td>
            <td style="max-width:320px; white-space:pre-wrap;">{{ $desp }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endif
