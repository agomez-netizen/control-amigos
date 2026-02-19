@extends('layouts.app')

@section('content')
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Asignar empresas</h3>
    <a href="{{ route('usuarios.empresas') }}" class="btn btn-secondary">Volver</a>
  </div>

  @if(session('ok'))
    <div class="alert alert-success">{{ session('ok') }}</div>
  @endif

  <div class="card mb-3">
    <div class="card-body">
      <div><b>Usuario:</b> {{ $usuario->nombre }} {{ $usuario->apellido }} ({{ $usuario->usuario }})</div>
      <div class="text-muted">Marca las empresas que tendrá asignadas. Guardar aplica cambios (agrega y quita).</div>
    </div>
  </div>

  <form method="POST" action="{{ route('usuarios.empresas.update', $usuario->id_usuario) }}">
    @csrf

    <div class="card">
      <div class="card-body">
        <div class="row">
          @foreach($empresas as $e)
            <div class="col-md-4 mb-2">
              <label class="d-flex align-items-center gap-2 border rounded p-2">
                <input type="checkbox"
                       name="empresas[]"
                       value="{{ $e->id_empresa }}"
                       @checked(in_array($e->id_empresa, $asignadas))>

                <div>
                  <div>{{ $e->nombre }}</div>
                  <small class="text-muted">
                    {{ (int)$e->activo === 1 ? 'Activa' : 'Inactiva' }}
                  </small>
                </div>
              </label>
            </div>
          @endforeach
        </div>

        @error('empresas')
          <div class="text-danger mt-2">{{ $message }}</div>
        @enderror
        @error('empresas.*')
          <div class="text-danger mt-2">{{ $message }}</div>
        @enderror
      </div>

      <div class="card-footer d-flex gap-2">
        <button class="btn btn-success">Guardar cambios</button>
        <a class="btn btn-outline-danger"
           href="#"
           onclick="event.preventDefault(); document.getElementById('clearForm').submit();">
          Quitar todas
        </a>
      </div>
    </div>
  </form>

  {{-- Form oculto para limpiar todas --}}
  <form id="clearForm" method="POST" action="{{ route('usuarios.empresas.update', $usuario->id_usuario) }}">
    @csrf
    {{-- sin empresas[] => sync([]) --}}
  </form>
</div>
@endsection
