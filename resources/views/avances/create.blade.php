@extends('layouts.app')

@section('title', 'Registrar Avance')

@section('content')
<div class="container py-4">

  @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
  @endif

  @if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <strong>Corrige los errores:</strong>
      <ul class="mb-0">
        @foreach ($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
  @endif

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Registrar Avance</h4>
    <a href="{{ route('avances.byDate') }}" class="btn btn-outline-secondary">
      📅 Ver avances por fecha
    </a>
  </div>

  <div class="card shadow-sm border-0">
    <div class="card-body">

      {{-- novalidate para que el navegador no bloquee por textarea oculto --}}
      <form id="form-avance" method="POST" action="{{ route('avances.store') }}" novalidate>
        @csrf

        <div class="mb-3">
          <label class="form-label">Punto de Venta</label>
          <select name="id_empresa" class="form-select" required>
            <option value="">— Seleccionar —</option>
            @foreach ($empresas as $p)
              <option value="{{ $p->id_empresa }}"
                {{ old('id_empresa') == $p->id_empresa ? 'selected' : '' }}>
                {{ $p->nombre }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Descripción</label>

          {{-- OJO: NO required aquí. TinyMCE lo oculta y Chrome bloquea el submit --}}
          <textarea
            name="descripcion"
            id="descripcion"
            class="form-control"
            rows="6"
          >{{ old('descripcion') }}</textarea>

          <small class="text-muted">
            Puedes usar negrita, listas y pegar enlaces.
          </small>

          <div id="desc-error" class="text-danger small mt-1 d-none">
            La descripción es obligatoria.
          </div>
        </div>

        <button id="btn-submit" type="submit" class="btn btn-primary">
          + Agregar
        </button>
      </form>

    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.tiny.cloud/1/687zw6kzwgqgwr2oqdot47bz1hiy7k2bndnxr058jvd73m9g/tinymce/6/tinymce.min.js"
        referrerpolicy="origin"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('form-avance');
  const textarea = document.getElementById('descripcion');
  const btn = document.getElementById('btn-submit');
  const descError = document.getElementById('desc-error');

  if (window.tinymce) {
    tinymce.init({
      selector: '#descripcion',
      height: 260,
      menubar: false,
      branding: false,
      plugins: 'link lists autoresize',
      toolbar: 'undo redo | bold italic underline | bullist numlist | link',
      link_default_target: '_blank',
      link_assume_external_targets: true,
      valid_elements: 'p,br,strong/b,em/i,u,ul,ol,li,a[href|target|rel]',
      invalid_elements: 'script,iframe,style,object,embed'
    });
  }

  form.addEventListener('submit', function (e) {
    let plainText = '';

    // Si TinyMCE está activo, valida con el contenido del editor (no el textarea)
    if (window.tinymce && tinymce.get('descripcion')) {
      const editor = tinymce.get('descripcion');
      plainText = editor.getContent({ format: 'text' }).trim();

      if (!plainText) {
        e.preventDefault();
        descError.classList.remove('d-none');
        editor.focus();
        return;
      }

      // sincroniza HTML del editor al textarea para enviarlo al backend
      tinymce.triggerSave();
    } else {
      // Fallback sin TinyMCE
      plainText = (textarea.value || '').trim().replace(/<[^>]*>/g, '').replace(/&nbsp;/g,' ').trim();
      if (!plainText) {
        e.preventDefault();
        descError.classList.remove('d-none');
        textarea.focus();
        return;
      }
    }

    descError.classList.add('d-none');

    // Evita doble submit
    btn.disabled = true;
    btn.innerText = 'Guardando...';
  });
});
</script>
@endpush
