@extends('layouts.app')

@section('content')
<div class="container">

  @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
  @endif

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Avances por fecha</h3>

    <div class="d-flex gap-2">
      <a href="{{ route('avances.export', request()->all()) }}"
        class="btn btn-success">
            Exportar Excel
        </a>
        <a href="{{ route('avances.create') }}" class="btn btn-outline-primary">
        Registrar avance
      </a>
    </div>
  </div>

 <form class="card card-body mb-3" method="GET" action="{{ route('avances.byDate') }}">
  <div class="row g-3 align-items-end">

    {{-- Fila 1 --}}
    <div class="col-md-6">
      <label class="form-label">Punto de Venta</label>
      <select id="empresaSelect" name="id_empresa" class="form-select">
        <option value="" {{ empty($idEmpresa) ? 'selected' : '' }}>— Todos —</option>
        @foreach($empresas as $e)
          <option value="{{ $e->id_empresa }}"
            {{ (string)$idEmpresa === (string)$e->id_empresa ? 'selected' : '' }}>
            {{ $e->nombre }}
          </option>
        @endforeach
      </select>
      <div class="form-text">Escribe para buscar y selecciona una empresa.</div>
    </div>

    <div class="col-md-6">
      <label class="form-label">Usuario</label>
      <select id="usuarioSelect" name="id_usuario" class="form-select">
        <option value="">— Todos —</option>
        @foreach(($usuarios ?? collect()) as $usuario)
          <option value="{{ $usuario->id_usuario }}"
            {{ (string)request('id_usuario') === (string)$usuario->id_usuario ? 'selected' : '' }}>
            {{ $usuario->nombre }} {{ $usuario->apellido }}
          </option>
        @endforeach
      </select>
      <div class="form-text">Escribe para buscar y selecciona un usuario.</div>
    </div>

    {{-- Fila 2 --}}
    <div class="col-md-4">
      <label class="form-label">Desde</label>
      <input class="form-control" type="date"
             name="desde"
             value="{{ request('desde', $desde ?? '') }}">
    </div>

    <div class="col-md-4">
      <label class="form-label">Hasta</label>
      <input class="form-control" type="date"
             name="hasta"
             value="{{ request('hasta', $hasta ?? '') }}">
    </div>

    <div class="col-md-4 text-end">
      <label class="form-label d-block invisible">Botón</label>
      <button class="btn btn-primary w-100" type="submit">
        Filtrar
      </button>
    </div>

  </div>
</form>

  @forelse($grouped as $fecha => $items)
    <div class="mb-2">
      <span class="badge bg-dark">{{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</span>
      <span class="text-muted small">({{ $items->count() }} avances)</span>
    </div>

    @foreach($items as $a)
      <div class="card mb-2">
        <div class="card-body d-flex justify-content-between align-items-start gap-3">

          <div class="flex-grow-1">
            <div class="fw-bold">
              <i class="bi bi-folder-fill me-1"></i>
              {{ $a->empresa->nombre ?? '—' }}
            </div>

            <div class="text-muted">
              {!! nl2br(e($a->descripcion ?? '')) !!}
            </div>

            <div class="small mt-2 text-muted">
              <i class="bi bi-person-fill me-1"></i>
              {{ $a->usuario->nombre ?? 'Usuario eliminado' }} {{ $a->usuario->apellido ?? '' }}
            </div>
          </div>

          <div class="text-end" style="min-width: 210px;">
            <div class="small text-muted mb-2 text-nowrap">
              <i class="bi bi-clock me-1"></i>
              {{ $a->created_at ? \Carbon\Carbon::parse($a->created_at)->format('d/m/Y H:i') : '' }}
            </div>

            <button
              type="button"
              class="btn btn-outline-primary btn-sm js-edit"
              data-id="{{ $a->id_avance }}"
            >
              Editar
            </button>
          </div>

        </div>
      </div>
    @endforeach

  @empty
    <div class="alert alert-info">No hay avances para mostrar.</div>
  @endforelse

</div>

<!-- MODAL FULLSCREEN -->
<div class="modal fade" id="modalHistorial" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Editar avance + Bitácora</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div id="modalBodyHistorial" class="text-muted">Cargando…</div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // Tom Select (si está disponible)
  const empresaSel = document.getElementById('empresaSelect');
  if (empresaSel && window.TomSelect) {
    new TomSelect(empresaSel, {
      create: false,
      allowEmptyOption: true,
      placeholder: '— Todos —',
      maxOptions: 5000,
      render: {
        no_results: function() {
          return '<div class="no-results">No hay resultados</div>';
        }
      }
    });
  }

  const usuarioSel = document.getElementById('usuarioSelect');
  if (usuarioSel && window.TomSelect) {
    new TomSelect(usuarioSel, {
      create: false,
      allowEmptyOption: true,
      placeholder: '— Todos —',
      maxOptions: 5000,
      render: {
        no_results: function() {
          return '<div class="no-results">No hay resultados</div>';
        }
      }
    });
  }

  const modalEl   = document.getElementById('modalHistorial');
  const modalBody = document.getElementById('modalBodyHistorial');

  async function openHistorial(id) {
    if (!modalEl || !modalBody) return;

    if (!window.bootstrap || !bootstrap.Modal) {
      console.error('Bootstrap JS no está disponible en window.bootstrap');
      return;
    }

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

    modalBody.innerHTML = 'Cargando…';
    modal.show();

    try {
      const url = `{{ route('avances.historial', ['id' => '__ID__']) }}`.replace('__ID__', id);
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });

      if (!res.ok) {
        modalBody.innerHTML = `<div class="alert alert-danger">No se pudo cargar el historial (HTTP ${res.status}).</div>`;
        return;
      }

      modalBody.innerHTML = await res.text();
    } catch (e) {
      modalBody.innerHTML = `<div class="alert alert-danger">Error cargando historial.</div>`;
    }
  }

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.js-edit');
    if (!btn) return;

    e.preventDefault();
    openHistorial(btn.dataset.id);
  });
});
</script>
@endsection
