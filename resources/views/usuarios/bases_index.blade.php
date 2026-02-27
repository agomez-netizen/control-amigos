@extends('layouts.app')

@section('content')
<div class="container">
  <h3 class="mb-3">Usuarios → Bases de datos</h3>

  <form class="row g-2 mb-3" method="GET" action="{{ route('usuarios.bases') }}">
    <div class="col-md-6">
      <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Buscar por nombre, apellido o usuario...">
    </div>
    <div class="col-md-2">
      <button class="btn btn-primary w-100">Buscar</button>
    </div>
    <div class="col-md-2">
      <a href="{{ route('usuarios.bases') }}" class="btn btn-secondary w-100">Limpiar</a>
    </div>
  </form>

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-striped align-middle mb-0">
          <thead>
            <tr>
              <th>ID</th>
              <th>Usuario</th>
              <th>Nombre</th>
              <th># Bases</th>
              <th style="width:140px;">Acción</th>
            </tr>
          </thead>
          <tbody>
            @forelse($usuarios as $u)
              <tr>
                <td>{{ $u->id_usuario }}</td>
                <td>{{ $u->usuario }}</td>
                <td>{{ $u->nombre }} {{ $u->apellido }}</td>
                <td>{{ $u->bases_de_datos_count }}</td>
                <td>
                  <a class="btn btn-sm btn-success"
                     href="{{ route('usuarios.bases.edit', $u->id_usuario) }}">
                    Asignar
                  </a>
                </td>
              </tr>
            @empty
              <tr><td colspan="5" class="text-center py-4">No hay usuarios.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="mt-3">
    {{ $usuarios->links() }}
  </div>
</div>
@endsection
