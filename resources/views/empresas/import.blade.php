@extends('layouts.app')

@section('content')
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Importar empresas (Excel)</h3>
    <a href="{{ route('empresas.index') }}" class="btn btn-outline-secondary">Volver</a>
  </div>

  @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  @if (session('errors_import'))
    <div class="alert alert-danger">
      <div class="fw-bold mb-2">Se encontraron errores:</div>
      <ul class="mb-0">
        @foreach(session('errors_import') as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card card-body">
    <form method="POST" action="{{ route('empresas.importExcel') }}" enctype="multipart/form-data">
      @csrf

      <div class="mb-3">
        <label class="form-label">Archivo Excel (.xlsx)</label>
        <input type="file" name="archivo" class="form-control" accept=".xlsx" required>
        <div class="form-text">
          Usa la plantilla: Empresas / Telefonos / Celulares con empresa_key.
        </div>
      </div>

      <button class="btn btn-primary">Importar</button>
    </form>
  </div>
</div>
@endsection
