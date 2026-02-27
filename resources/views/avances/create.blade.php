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
          <label class="form-label">Contacto</label>

          <select name="id_contacto" class="form-select" required>
            <option value="">— Seleccionar —</option>

            @foreach ($contactos as $c)
            <option
                value="{{ $c->id_contacto }}"
                {{ old('id_contacto') == $c->id_contacto ? 'selected' : '' }}
                data-empresa="{{ e($c->empresa_nombre ?? '') }}"
                data-nombre="{{ e(trim(($c->contacto_nombre ?? '').' '.($c->contacto_apellido ?? ''))) }}"
                data-puesto="{{ e($c->puesto ?? '') }}"
                data-telefono="{{ e($c->telefono ?? '') }}"
                data-celular="{{ e($c->celular ?? '') }}"
                data-email="{{ e($c->email ?? '') }}"
            >
                {{ $c->empresa_nombre }}
                — {{ $c->contacto_nombre }} {{ $c->contacto_apellido ?? '' }}
                @if(!empty($c->puesto)) ({{ $c->puesto }}) @endif
            </option>
            @endforeach
          </select>

          <small class="text-muted">
            Solo verás contactos de empresas cuya base de datos está asignada a tu usuario.
          </small>
        </div>
        <div id="contacto-card" class="border rounded p-3 mt-2 d-none">
  <div class="fw-semibold mb-2">Datos del contacto</div>

  <div class="row g-2 small">
    <div class="col-md-6">
      <div class="text-muted">Empresa</div>
      <div id="c-empresa" class="fw-semibold">—</div>
    </div>
    <div class="col-md-6">
      <div class="text-muted">Nombre</div>
      <div id="c-nombre" class="fw-semibold">—</div>
    </div>

    <div class="col-md-6">
      <div class="text-muted">Puesto</div>
      <div id="c-puesto">—</div>
    </div>
    <div class="col-md-6">
      <div class="text-muted">Email</div>
      <div id="c-email">—</div>
    </div>

    <div class="col-md-6">
      <div class="text-muted">Teléfono</div>
      <div id="c-telefono">—</div>
    </div>
    <div class="col-md-6">
      <div class="text-muted">Celular</div>
      <div id="c-celular">—</div>
    </div>
  </div>
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
      plainText = (textarea.value || '')
        .trim()
        .replace(/<[^>]*>/g, '')
        .replace(/&nbsp;/g,' ')
        .trim();

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

  const selectContacto = document.querySelector('select[name="id_contacto"]');
  const card = document.getElementById('contacto-card');

  const elEmpresa  = document.getElementById('c-empresa');
  const elNombre   = document.getElementById('c-nombre');
  const elPuesto   = document.getElementById('c-puesto');
  const elEmail    = document.getElementById('c-email');
  const elTel      = document.getElementById('c-telefono');
  const elCel      = document.getElementById('c-celular');

  function setText(el, val) {
    el.textContent = (val && String(val).trim()) ? val : '—';
  }

  function renderContacto() {
    const opt = selectContacto?.selectedOptions?.[0];
    const id = opt?.value;

    if (!opt || !id) {
      card.classList.add('d-none');
      return;
    }

    setText(elEmpresa,  opt.dataset.empresa);
    setText(elNombre,   opt.dataset.nombre);
    setText(elPuesto,   opt.dataset.puesto);

    // Email como link si existe
    const email = (opt.dataset.email || '').trim();
    if (email) {
      elEmail.innerHTML = `<a href="mailto:${email}">${email}</a>`;
    } else {
      elEmail.textContent = '—';
    }

    setText(elTel,  opt.dataset.telefono);
    setText(elCel,  opt.dataset.celular);

    card.classList.remove('d-none');
  }

  if (selectContacto) {
    selectContacto.addEventListener('change', renderContacto);
    // pinta si ya viene seleccionado (old())
    renderContacto();
  }
</script>
@endpush
