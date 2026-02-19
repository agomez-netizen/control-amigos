@extends('layouts.app')

@section('content')
<div class="container">
  <h3 class="mb-3">Editar empresa</h3>

  @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('empresas.update', $empresa->id_empresa) }}" class="card card-body">
    @method('PUT')
    @include('empresas._form', ['empresa' => $empresa])
  </form>
</div>
@endsection
