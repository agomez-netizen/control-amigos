@extends('layouts.app')

@section('content')
<div class="container">
  <div class="d-flex justify-content-between align-items-start mb-3">
    <div>
      <h2 class="mb-1">{{ $empresa->nombre }}</h2>
      <div class="text-muted">
        Tipo: <b>{{ $empresa->tipoEmpresa->nombre ?? '—' }}</b>
        · Base: <b>{{ $empresa->baseDeDatos->nombre ?? '—' }}</b>
        · Activo: <b>{{ $empresa->activo ? 'Sí' : 'No' }}</b>
      </div>
    </div>

    <div class="d-flex gap-2">
      <a href="{{ route('empresas.edit', $empresa->id_empresa) }}" class="btn btn-outline-primary">Editar</a>
      <a href="{{ route('empresas.index') }}" class="btn btn-outline-secondary">Volver</a>
    </div>
  </div>

  <div class="card card-body mb-3">
    <div class="row g-2">
      <div class="col-md-6"><b>Descripción:</b> {{ $empresa->descripcion ?: '—' }}</div>
      <div class="col-md-6"><b>Sitio web:</b>
        @if(!empty($empresa->sitio_web))
          <a href="{{ $empresa->sitio_web }}" target="_blank" rel="noopener">{{ $empresa->sitio_web }}</a>
        @else
          —
        @endif
      </div>

      <div class="col-md-4"><b>País:</b> {{ $empresa->pais ?: '—' }}</div>
      <div class="col-md-4"><b>Departamento:</b> {{ $empresa->departamento ?: '—' }}</div>
      <div class="col-md-4"><b>Municipio:</b> {{ $empresa->municipio ?: '—' }}</div>

      <div class="col-md-12"><b>Gestor AAPOS:</b> {{ $empresa->gestor_aapos ?: '—' }}</div>

      <div class="col-md-12"><b>Detalles:</b><br>{{ $empresa->detalles ?: '—' }}</div>
      <div class="col-md-12"><b>Notas:</b><br>{{ $empresa->notas ?: '—' }}</div>
    </div>
  </div>

  <h4 class="mt-3">Contactos</h4>

  @if($empresa->contactos->isEmpty())
    <div class="alert alert-light border">No hay contactos registrados.</div>
  @else
    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Contacto</th>
            <th>Puesto</th>
            <th>Dirección</th>
            <th class="text-center">Activo</th>
          </tr>
        </thead>
        <tbody>
          @foreach($empresa->contactos as $c)
            <tr>
              <td>
                <div class="fw-semibold">{{ $c->titulo ? $c->titulo.' ' : '' }}{{ $c->nombre }} {{ $c->apellido }}</div>
                <div class="text-muted small">{{ $c->departamento ?: '—' }}</div>
              </td>
              <td>
                <div><b>Email:</b> {{ $c->email ?: '—' }}</div>
                <div><b>Tel:</b> {{ $c->telefono ?: '—' }}</div>
                <div><b>Cel:</b> {{ $c->celular ?: '—' }}</div>
              </td>
              <td>{{ $c->puesto ?: '—' }}</td>
              <td>{{ $c->direccion ?: '—' }}</td>
              <td class="text-center">
                @if($c->activo)
                  <span class="badge bg-success">Sí</span>
                @else
                  <span class="badge bg-secondary">No</span>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
@endsection
