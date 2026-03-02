@extends('layouts.app')

@section('content')
<div class="container">
  <h3>Importar Empresas (Excel)</h3>

  @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <a class="btn btn-outline-secondary mb-3" href="{{ route('empresas.template') }}">
  Descargar plantilla Excel
</a>

  <form method="POST" action="{{ route('empresas.import.excel') }}" enctype="multipart/form-data">
    @csrf

    <div class="mb-3">
      <label class="form-label">Archivo Excel (.xlsx)</label>
      <input type="file" name="file" class="form-control" required>
      <div class="form-text">
        Debe tener encabezados: nombre, id_base_datos, id_tipo_empresa, activo, gestor_aapos, proyectos, etc.
      </div>
    </div>

    <div class="form-check mb-3">
      <input class="form-check-input" type="checkbox" name="actualizar" id="actualizar" value="1" checked>
      <label class="form-check-label" for="actualizar">
        Actualizar si ya existe (mismo nombre + misma base)
      </label>
    </div>

    <button class="btn btn-primary">Importar</button>
    <a class="btn btn-secondary" href="{{ route('empresas.index') }}">Cancelar</a>
  </form>
</div>
@endsection
