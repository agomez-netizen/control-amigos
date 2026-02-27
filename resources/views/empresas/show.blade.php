@extends('layouts.app')

@section('content')
<div class="container">

  {{-- Header --}}
  <div class="d-flex justify-content-between align-items-start mb-3">
    <div>
      <h2 class="mb-1">{{ $empresa->nombre ?? 'Empresa' }}</h2>
      <div class="text-muted">
        Tipo: <strong>{{ $empresa->tipoEmpresa->nombre ?? ($empresa->tipo_empresa->nombre ?? '—') }}</strong>
        · Base: <strong>{{ $empresa->baseDeDatos->nombre ?? ($empresa->base_de_datos->nombre ?? '—') }}</strong>
        · Activo: <strong>{{ ($empresa->activo ?? 0) ? 'Sí' : 'No' }}</strong>
        · Proyectos: <strong>{{ ($empresa->proyectos ?? 0) ? 'Sí' : 'No' }}</strong>
      </div>
    </div>

    <div class="d-flex gap-2">
      <a href="{{ route('empresas.edit', $empresa->id_empresa) }}" class="btn btn-outline-primary">
        Editar
      </a>
      <a href="{{ route('empresas.index') }}" class="btn btn-outline-secondary">
        Volver
      </a>
    </div>
  </div>

  {{-- Info Empresa --}}
  <div class="card mb-4">
    <div class="card-body">

      <div class="row g-3">
        <div class="col-md-8">
          <div class="fw-bold">Descripción:</div>
          <div>{{ $empresa->descripcion ?? '—' }}</div>
        </div>

        <div class="col-md-4">
          <div class="fw-bold">Sitio web:</div>
          @if(!empty($empresa->sitio_web))
            <a href="{{ $empresa->sitio_web }}" target="_blank" rel="noopener">
              {{ $empresa->sitio_web }}
            </a>
          @else
            —
          @endif
        </div>

        <div class="col-md-4">
          <div class="fw-bold">País:</div>
          <div>{{ $empresa->pais ?? '—' }}</div>
        </div>

        <div class="col-md-4">
          <div class="fw-bold">Departamento:</div>
          <div>{{ $empresa->departamento ?? '—' }}</div>
        </div>

        <div class="col-md-4">
          <div class="fw-bold">Municipio:</div>
          <div>{{ $empresa->municipio ?? '—' }}</div>
        </div>

        <div class="col-md-6">
          <div class="fw-bold">Gestor AAPOS:</div>
          <div>{{ $empresa->gestor_aapos ?? '—' }}</div>
        </div>

        <div class="col-md-12">
          <div class="fw-bold">Detalles:</div>
          <div class="white-space-pre">{{ $empresa->detalles ?? '—' }}</div>
        </div>

        <div class="col-md-12">
          <div class="fw-bold">Notas:</div>
          <div class="white-space-pre">{{ $empresa->notas ?? '—' }}</div>
        </div>
      </div>

    </div>
  </div>

  {{-- CONTACTOS --}}
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h3 class="mb-0">Contactos</h3>

        <a class="btn btn-success"
        href="{{ route('empresas.contactos.export.excel', [$empresa->id_empresa] + request()->query()) }}">
        Exportar
        </a>
  </div>

  {{-- Filtro --}}
  <form method="GET" action="{{ route('empresas.show', $empresa->id_empresa) }}" class="row g-2 mb-3">
    <div class="col-md-10">
      <input
        class="form-control"
        name="cq"
        value="{{ $cq ?? '' }}"
        placeholder="Buscar: nombre, apellido, email, tel, puesto..."
      >
    </div>
    <div class="col-md-2 d-grid">
      <button class="btn btn-primary">Buscar</button>
    </div>
  </form>

  <div class="card">
    <div class="card-body">

      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Contacto</th>
              <th>Puesto</th>
              <th>Departamento</th>
              <th>Activo</th>
            </tr>
          </thead>
          <tbody>
            @forelse($contactos as $c)
              <tr>
                <td>
                  <div class="fw-semibold">
                    {{ trim(($c->titulo ?? '').' '.($c->nombre ?? '').' '.($c->apellido ?? '')) ?: '—' }}
                  </div>
                  <div class="text-muted small">
                    {{ $c->departamento ?? '—' }}
                  </div>
                </td>

                <td>
                  <div><strong>Email:</strong> {{ $c->email ?? '—' }}</div>
                  <div><strong>Tel:</strong> {{ $c->telefono ?? '—' }}</div>
                  <div><strong>Cel:</strong> {{ $c->celular ?? '—' }}</div>
                </td>

                <td>{{ $c->puesto ?? '—' }}</td>

                <td>{{ $c->departamento ?? '—' }}</td>

                <td>
                  @if(($c->activo ?? 0) == 1)
                    <span class="badge text-bg-success">Sí</span>
                  @else
                    <span class="badge text-bg-secondary">No</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-muted">
                  No hay contactos {{ !empty($cq) ? 'con ese filtro' : '' }}.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Paginado --}}
      <div class="d-flex justify-content-end">
        {{ $contactos->links() }}
      </div>

    </div>
  </div>

</div>

{{-- mini estilo para que respete saltos de línea --}}
<style>
  .white-space-pre { white-space: pre-wrap; }
</style>
@endsection
