@extends('layouts.app')

@section('content')
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Asignar bases de datos</h3>
    <a href="{{ route('usuarios.bases') }}" class="btn btn-secondary">Volver</a>
  </div>

  @if(session('ok'))
    <div class="alert alert-success">{{ session('ok') }}</div>
  @endif

  <div class="card mb-3">
    <div class="card-body">
      <div><b>Usuario:</b> {{ $usuario->nombre }} {{ $usuario->apellido }} ({{ $usuario->usuario }})</div>
      <div class="text-muted">Marca las bases de datos que tendrá asignadas. Guardar aplica cambios (agrega y quita).</div>
    </div>
  </div>

  <form method="POST" action="{{ route('usuarios.bases.update', $usuario->id_usuario) }}">
    @csrf

    <div class="card">
      <div class="card-body">
        <div class="row">
          @foreach($bases as $b)
            <div class="col-md-4 mb-2">
              <label class="d-flex align-items-center gap-2 border rounded p-2">
                <input type="checkbox"
                       name="bases[]"
                       value="{{ $b->id_base_datos }}"
                       @checked(in_array($b->id_base_datos, $asignadas))>

                <div>
                  <div class="fw-semibold">{{ $b->nombre }}</div>
                  @if(!empty($b->descripcion))
                    <small class="text-muted">{{ $b->descripcion }}</small>
                  @endif
                </div>
              </label>
            </div>
          @endforeach
        </div>

        @error('bases')
          <div class="text-danger mt-2">{{ $message }}</div>
        @enderror
        @error('bases.*')
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

  <form id="clearForm" method="POST" action="{{ route('usuarios.bases.update', $usuario->id_usuario) }}">
    @csrf
  </form>
</div>
@endsection
