@extends('layouts.app')

@section('content')
<div class="container">

@if (session('success'))
    <div id="autoCloseAlert" class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    <script>
        setTimeout(function () {
            let alert = document.getElementById('autoCloseAlert');
            if (alert) {
                let bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 3000); // 3 segundos
    </script>
@endif

  @if (session('errors_import'))
    @php $errs = session('errors_import'); @endphp
    @if (is_array($errs) && count($errs))
      <div class="alert alert-warning">
        <div class="fw-semibold mb-1">Importación con avisos:</div>
        <ul class="mb-0">
          @foreach($errs as $e)
            <li>{{ $e }}</li>
          @endforeach
        </ul>
      </div>
    @endif
  @endif

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Empresas</h3>

    <div class="d-flex gap-2">
      <a href="{{ route('empresas.create') }}" class="btn btn-outline-primary">Nueva</a>
      <a class="btn btn-success"
        href="{{ route('empresas.export.excel', request()->query()) }}">
        Exportar Excel
        </a>
    </div>
  </div>

  {{-- Filtros --}}
  <form method="GET" class="card card-body mb-3">
    <div class="row g-2">
      <div class="col-md-4">
        <label class="form-label">Búsqueda</label>
        <input type="text" name="q" class="form-control"
               value="{{ request('q') }}"
               placeholder="Empresa, contacto, correo, teléfono...">
      </div>

      <div class="col-md-3">
        <label class="form-label">Base de datos</label>
        <select name="id_base_datos" class="form-select">
          <option value="">-- Todas --</option>
          @foreach($bases as $b)
            <option value="{{ $b->id_base_datos }}"
              @selected((string)request('id_base_datos') === (string)$b->id_base_datos)>
              {{ $b->nombre }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label">Tipo</label>
        <select name="id_tipo_empresa" class="form-select">
          <option value="">-- Todos --</option>
          @foreach($tipos as $t)
            <option value="{{ $t->id_tipo_empresa }}"
              @selected((string)request('id_tipo_empresa') === (string)$t->id_tipo_empresa)>
              {{ $t->nombre }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label">Activo</label>
        <select name="activo" class="form-select">
          <option value="">-- Todos --</option>
          <option value="1" @selected(request('activo') === '1')>Sí</option>
          <option value="0" @selected(request('activo') === '0')>No</option>
        </select>
      </div>

      <div class="col-md-12 d-flex gap-2">
        <button class="btn btn-primary">Filtrar</button>
        <a href="{{ route('empresas.index') }}" class="btn btn-outline-secondary">Limpiar</a>
      </div>
    </div>
  </form>

  {{-- Tabla --}}
  <div class="table-responsive">
    <table class="table align-middle">
      <thead class="table-light">
        <tr>
          <th>Empresa</th>
          <th>Tipo</th>
          <th>Base</th>
          <th>Ubicación</th>
          <th class="text-center">Contactos</th>
          <th class="text-center">Activo</th>
          <th class="text-end">Acciones</th>
        </tr>
      </thead>
      <tbody>
        @forelse($empresas as $e)
          <tr class="js-row-click"
              role="button"
              tabindex="0"
              data-href="{{ route('empresas.show', $e->id_empresa) }}">

            <td>
              <div class="fw-semibold text-dark">
                {{ $e->nombre }}
              </div>
              <div class="text-muted small">
                {{ $e->descripcion ?: '—' }}
              </div>
            </td>

            <td>{{ $e->tipoEmpresa->nombre ?? '—' }}</td>
            <td>{{ $e->baseDeDatos->nombre ?? '—' }}</td>

            <td>
              <div>{{ $e->pais ?: '—' }}</div>
              <div class="text-muted small">
                {{ $e->departamento ?: '' }}
                {{ $e->municipio ? ' / '.$e->municipio : '' }}
              </div>
            </td>

            <td class="text-center">
              {{ $e->contactos_count ?? 0 }}
            </td>

            <td class="text-center">
              @if($e->activo)
                <span class="badge bg-success">Sí</span>
              @else
                <span class="badge bg-secondary">No</span>
              @endif
            </td>

            <td class="text-end">
              <a href="{{ route('empresas.edit', $e->id_empresa) }}"
                 class="btn btn-sm btn-outline-primary"
                 data-no-rowclick="1">
                 Editar
              </a>

              <form action="{{ route('empresas.destroy', $e->id_empresa) }}"
                    method="POST"
                    class="d-inline"
                    data-no-rowclick="1"
                    onsubmit="return confirm('¿Eliminar esta empresa?');">
                @csrf
                @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">
                  Eliminar
                </button>
              </form>
            </td>

          </tr>
        @empty
          <tr>
            <td colspan="7" class="text-center text-muted py-4">
              No hay empresas registradas
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-3">
    {{ $empresas->links('pagination::bootstrap-5') }}
  </div>

</div>

{{-- ============================= --}}
{{-- JS fila clickeable --}}
{{-- ============================= --}}
<script>
document.addEventListener('DOMContentLoaded', () => {

  const goTo = (row) => {
    const url = row.dataset.href;
    if (url) window.location.href = url;
  };

  document.querySelectorAll('.js-row-click').forEach(row => {

    row.addEventListener('click', (e) => {
      if (e.target.closest('[data-no-rowclick]')) return;
      if (e.target.closest('a, button, input, select, textarea, form')) return;
      goTo(row);
    });

    row.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        goTo(row);
      }
    });

  });
});
</script>

{{-- ============================= --}}
{{-- Hover suave elegante --}}
{{-- ============================= --}}
<style>
  /* fila clickeable */
  table tbody tr.js-row-click { cursor: pointer; }

  /* transición suave en las celdas */
  table tbody tr.js-row-click > td {
    transition: background-color .18s ease-in-out;
  }

  /* hover: pintar celdas, no el tr */
  table tbody tr.js-row-click:hover > td {
    background-color: rgba(13, 110, 253, 0.06) !important;
  }

  /* opcional: nombre azul en hover */
  table tbody tr.js-row-click:hover .fw-semibold {
    color: #0d6efd;
  }
  table.table-striped tbody tr.js-row-click:hover > td{
  --bs-table-accent-bg: rgba(13, 110, 253, 0.06) !important;
  background-color: rgba(13, 110, 253, 0.06) !important;
}
</style>

@endsection
