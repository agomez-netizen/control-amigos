@extends('layouts.app')

@section('content')
<div class="container">

  {{-- Header --}}
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Editar empresa</h3>

    <a href="{{ route('empresas.index') }}" class="btn btn-outline-secondary">
      ← Volver al listado
    </a>
  </div>

  {{-- Alerts --}}
  @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
  @endif

  @if ($errors->any())
    <div class="alert alert-danger">
      <div class="fw-semibold mb-2">Revisa estos campos:</div>
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  {{-- FORM EMPRESA --}}
  <form method="POST" action="{{ route('empresas.update', $empresa->id_empresa) }}" class="card card-body mb-4">
    @method('PUT')

    @include('empresas._form', [
      'empresa' => $empresa,
      'bases' => $bases,
      'tipos' => $tipos,

      'showContactos' => false,  {{-- 👈 NO mostrar todos los contactos aquí --}}
      'showButtons' => true,
      'empresaOpen' => false,     {{-- 👈 Acordeón EMPRESA cerrado por defecto --}}
    ])
  </form>

  {{-- CONTACTOS --}}
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <div class="fw-semibold">Contactos</div>

      <button class="btn btn-sm btn-outline-success" type="button" data-bs-toggle="collapse" data-bs-target="#addContacto">
        + Agregar contacto
      </button>
    </div>

    <div class="card-body">

      {{-- FILTROS --}}
      <form method="GET" action="{{ route('empresas.edit', $empresa->id_empresa) }}" class="row g-2 mb-3">
        <div class="col-md-5">
          <input class="form-control" name="cq" value="{{ $cq ?? '' }}"
                 placeholder="Buscar: nombre, apellido, email, tel, puesto...">
        </div>

        <div class="col-md-3">
          <input class="form-control" name="c_puesto" value="{{ $c_puesto ?? '' }}" placeholder="Puesto">
        </div>

        <div class="col-md-2">
          <select class="form-select" name="c_activo">
            <option value="" @selected(($c_activo ?? '') === '')>Activo / Inactivo</option>
            <option value="1" @selected(($c_activo ?? '') === '1')>Activos</option>
            <option value="0" @selected(($c_activo ?? '') === '0')>Inactivos</option>
          </select>
        </div>

        <div class="col-md-2 d-grid">
          <button class="btn btn-primary">Filtrar</button>
        </div>
      </form>

      {{-- AGREGAR CONTACTO --}}
      <div class="collapse mb-3" id="addContacto">
        <form method="POST" action="{{ route('empresas.contactos.store', $empresa->id_empresa) }}" class="border rounded p-3">
          @csrf

          <div class="row g-2">
            <div class="col-md-4">
              <label class="form-label">Nombre *</label>
              <input name="nombre" class="form-control" required>
            </div>

            <div class="col-md-4">
              <label class="form-label">Apellido</label>
              <input name="apellido" class="form-control">
            </div>

            <div class="col-md-4">
              <label class="form-label">Email</label>
              <input name="email" class="form-control">
            </div>

            <div class="col-md-3">
              <label class="form-label">Teléfono</label>
              <input name="telefono" class="form-control">
            </div>

            <div class="col-md-3">
              <label class="form-label">Celular</label>
              <input name="celular" class="form-control">
            </div>

            <div class="col-md-3">
              <label class="form-label">Puesto</label>
              <input name="puesto" class="form-control">
            </div>

            <div class="col-md-3">
              <label class="form-label">Título</label>
              <input name="titulo" class="form-control">
            </div>

            <div class="col-md-6">
              <label class="form-label">Departamento</label>
              <input name="departamento" class="form-control">
            </div>

            <div class="col-md-6">
              <label class="form-label">Dirección</label>
              <input name="direccion" class="form-control">
            </div>

            <div class="col-md-10">
              <label class="form-label">Notas</label>
              <input name="notas" class="form-control">
            </div>

            <div class="col-md-2">
              <label class="form-label d-block">Activo</label>
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="activo" value="1" checked>
                <label class="form-check-label">Sí</label>
              </div>
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 mt-2">
              <button class="btn btn-success">Guardar contacto</button>
            </div>
          </div>
        </form>
      </div>

      {{-- LISTA --}}
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Email</th>
              <th>Tel/Cel</th>
              <th>Puesto</th>
              <th>Activo</th>
              <th class="text-end">Acciones</th>
            </tr>
          </thead>
          <tbody>
            @forelse($contactos as $c)
              <tr>
                <td>{{ $c->nombre }} {{ $c->apellido }}</td>
                <td>{{ $c->email }}</td>
                <td>{{ $c->telefono }} {{ $c->celular ? ' / '.$c->celular : '' }}</td>
                <td>{{ $c->puesto }}</td>
                <td>
                  @if($c->activo)
                    <span class="badge text-bg-success">Sí</span>
                  @else
                    <span class="badge text-bg-secondary">No</span>
                  @endif
                </td>
                <td class="text-end">
                  <a class="btn btn-sm btn-outline-primary"
                     href="{{ route('empresas.edit', [$empresa->id_empresa, 'edit_contacto' => $c->id_contacto]) }}">
                    Editar
                  </a>

                  <form method="POST"
                        action="{{ route('empresas.contactos.destroy', [$empresa->id_empresa, $c->id_contacto]) }}"
                        class="d-inline"
                        onsubmit="return confirm('¿Eliminar este contacto?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger">Eliminar</button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-muted">No hay contactos con esos filtros.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>



      {{-- EDITAR CONTACTO SELECCIONADO --}}
      @if($contactoEdit)
        <hr class="my-4">

        <h5 class="mb-3">Editando contacto: {{ $contactoEdit->nombre }} {{ $contactoEdit->apellido }}</h5>

        <form method="POST"
              action="{{ route('empresas.contactos.update', [$empresa->id_empresa, $contactoEdit->id_contacto]) }}"
              class="border rounded p-3">
          @csrf
          @method('PUT')

          <div class="row g-2">
            <div class="col-md-4">
              <label class="form-label">Nombre *</label>
              <input name="nombre" class="form-control" required
                     value="{{ old('nombre', $contactoEdit->nombre) }}">
            </div>

            <div class="col-md-4">
              <label class="form-label">Apellido</label>
              <input name="apellido" class="form-control"
                     value="{{ old('apellido', $contactoEdit->apellido) }}">
            </div>

            <div class="col-md-4">
              <label class="form-label">Email</label>
              <input name="email" class="form-control"
                     value="{{ old('email', $contactoEdit->email) }}">
            </div>

            <div class="col-md-3">
              <label class="form-label">Teléfono</label>
              <input name="telefono" class="form-control"
                     value="{{ old('telefono', $contactoEdit->telefono) }}">
            </div>

            <div class="col-md-3">
              <label class="form-label">Celular</label>
              <input name="celular" class="form-control"
                     value="{{ old('celular', $contactoEdit->celular) }}">
            </div>

            <div class="col-md-3">
              <label class="form-label">Puesto</label>
              <input name="puesto" class="form-control"
                     value="{{ old('puesto', $contactoEdit->puesto) }}">
            </div>

            <div class="col-md-3">
              <label class="form-label">Título</label>
              <input name="titulo" class="form-control"
                     value="{{ old('titulo', $contactoEdit->titulo) }}">
            </div>

            <div class="col-md-6">
              <label class="form-label">Departamento</label>
              <input name="departamento" class="form-control"
                     value="{{ old('departamento', $contactoEdit->departamento) }}">
            </div>

            <div class="col-md-6">
              <label class="form-label">Dirección</label>
              <input name="direccion" class="form-control"
                     value="{{ old('direccion', $contactoEdit->direccion) }}">
            </div>

            <div class="col-md-10">
              <label class="form-label">Notas</label>
              <input name="notas" class="form-control"
                     value="{{ old('notas', $contactoEdit->notas) }}">
            </div>

            <div class="col-md-2">
              <label class="form-label d-block">Activo</label>
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="activo" value="1"
                       @checked(old('activo', $contactoEdit->activo))>
                <label class="form-check-label">Sí</label>
              </div>
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 mt-2">
              <a class="btn btn-outline-secondary"
                 href="{{ route('empresas.edit', $empresa->id_empresa) }}">
                Cancelar
              </a>

              <button class="btn btn-primary">Guardar cambios</button>
            </div>
          </div>
        </form>
      @endif

    {{-- PAGINADO (siempre al fondo del módulo Contactos) --}}
    <hr class="my-4">

    <div class="d-flex justify-content-center">
    {{ $contactos->links() }}
    </div>

    </div>
  </div>

</div>
@endsection
