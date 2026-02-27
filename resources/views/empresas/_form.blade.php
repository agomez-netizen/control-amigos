@csrf

@php
  $empresa = $empresa ?? null;

  // Flags para reutilizar el mismo form en create/edit
  $showContactos = $showContactos ?? true;
  $showButtons   = $showButtons   ?? true;

  // Controla si el panel "Empresa" inicia abierto o cerrado
    $empresaOpen = $empresaOpen ?? true;

    // Si hubo errores de validación, lo abrimos para que veas qué falló
    if ($errors->any()) {
    $empresaOpen = true;
    }

  // Contactos para edit/create (solo si se van a mostrar)
  $contactos = [];
  $openContactos = false;

  if ($showContactos) {
    // Contactos desde old() si falló validación
    $oldContactos = old('contactos');

    if (is_array($oldContactos)) {
      $contactos = $oldContactos;
    } else {
      $contactos = $empresa?->contactos?->map(function($c){
        return [
          'id_contacto'   => $c->id_contacto,
          'nombre'        => $c->nombre,
          'apellido'      => $c->apellido,
          'telefono'      => $c->telefono,
          'celular'       => $c->celular,
          'email'         => $c->email,
          'direccion'     => $c->direccion,
          'puesto'        => $c->puesto,
          'departamento'  => $c->departamento,
          'titulo'        => $c->titulo,
          'notas'         => $c->notas,
          'activo'        => $c->activo,

        ];
      })->toArray() ?? [];
    }

    // Auto abrir Contactos si ya hay contactos o si venía enviando contactos
    $openContactos = (is_array(old('contactos')) && count(old('contactos')) > 0)
      || (is_array($contactos) && count($contactos) > 0);
  }
@endphp

{{-- ============================= --}}
{{-- ACORDEÓN: EMPRESA + CONTACTOS --}}
{{-- ============================= --}}
<div class="accordion soft-accordion enter-anim" id="empresaAccordion">

  {{-- ============================= --}}
  {{-- PANEL 1: EMPRESA --}}
  {{-- ============================= --}}
  <div class="accordion-item soft-card section-empresa">
    <h2 class="accordion-header" id="headingEmpresa">
      <button class="accordion-button {{ $empresaOpen ? '' : 'collapsed' }}" type="button"
              data-bs-toggle="collapse"
              data-bs-target="#collapseEmpresa"
              aria-expanded="true"
              aria-controls="collapseEmpresa">
        <span class="me-2">🏢</span> Información de la Empresa
      </button>
    </h2>

   <div id="collapseEmpresa" class="accordion-collapse collapse {{ $empresaOpen ? 'show' : '' }}"
         aria-labelledby="headingEmpresa"
         data-bs-parent="#empresaAccordion">
      <div class="accordion-body">

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Nombre de la empresa</label>
            <input type="text" name="nombre" class="form-control"
                  value="{{ old('nombre', $empresa->nombre ?? '') }}" required>
          </div>

          <div class="col-md-4">
            <label class="form-label">Base de datos</label>
            <select name="id_base_datos" class="form-select" required>
              <option value="">-- Selecciona --</option>
              @foreach($bases as $b)
                <option value="{{ $b->id_base_datos }}"
                  @selected(old('id_base_datos', $empresa->id_base_datos ?? '') == $b->id_base_datos)>
                  {{ $b->nombre }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-md-1">
            <label class="form-label d-block">Activo</label>
            <div class="form-check mt-2">
              <input class="form-check-input" type="checkbox" name="activo" id="activo"
                    value="1" @checked(old('activo', $empresa->activo ?? 1))>
              <label class="form-check-label" for="activo">Sí</label>
            </div>
          </div>

          <div class="col-md-1">
              <label class="form-label d-block" for="proyectos">Proyectos</label>
            <input class="form-check-input"
                type="checkbox"
                name="proyectos"
                id="proyectos"
                value="1"
                @checked(old('proyectos', $empresa->proyectos ?? 0))>
                <label class="form-check-label" for="activo">Sí</label>
        </div>

          <div class="col-md-4">
            <label class="form-label">Tipo de empresa</label>
            <select name="id_tipo_empresa" class="form-select">
              <option value="">-- Sin tipo --</option>
              @foreach($tipos as $t)
                <option value="{{ $t->id_tipo_empresa }}"
                  @selected(old('id_tipo_empresa', $empresa->id_tipo_empresa ?? '') == $t->id_tipo_empresa)>
                  {{ $t->nombre }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-md-8">
            <label class="form-label">Descripción</label>
            <input type="text" name="descripcion" class="form-control"
                  value="{{ old('descripcion', $empresa->descripcion ?? '') }}">
          </div>

          <div class="col-md-4">
            <label class="form-label">País</label>
            <input type="text" name="pais" class="form-control"
                  value="{{ old('pais', $empresa->pais ?? 'Guatemala') }}">
          </div>

          <div class="col-md-4">
            <label class="form-label">Departamento</label>
            <input type="text" name="departamento" class="form-control"
                  value="{{ old('departamento', $empresa->departamento ?? '') }}">
          </div>

          <div class="col-md-4">
            <label class="form-label">Municipio</label>
            <input type="text" name="municipio" class="form-control"
                  value="{{ old('municipio', $empresa->municipio ?? '') }}">
          </div>

          <div class="col-md-12">
            <label class="form-label">Sitio web</label>
            <input type="text" name="sitio_web" class="form-control"
                  value="{{ old('sitio_web', $empresa->sitio_web ?? '') }}"
                  placeholder="https://...">
          </div>

          <div class="col-md-6">
            <label class="form-label">Gestor AAPOS</label>

            {{-- Visible readonly --}}
            <input
              type="text"
              class="form-control"
              value="{{ old('gestor_aapos', $gestorAapos ?? ($empresa->gestor_aapos ?? '')) }}"
              readonly
            >

            {{-- Hidden para que SÍ se mande al server --}}
            <input
              type="hidden"
              name="gestor_aapos"
              value="{{ old('gestor_aapos', $gestorAapos ?? ($empresa->gestor_aapos ?? '')) }}"
            >
          </div>

          <div class="col-md-12">
            <label class="form-label">Dirección</label>
            <textarea name="detalles" class="form-control" rows="3">{{ old('detalles', $empresa->detalles ?? '') }}</textarea>
          </div>

          <div class="col-md-12">
            <label class="form-label">Notas</label>
            <textarea name="notas" class="form-control" rows="3">{{ old('notas', $empresa->notas ?? '') }}</textarea>
          </div>
        </div>

        {{-- Botón rápido para ir a contactos (solo si contactos se muestran) --}}
        @if($showContactos)
          <div class="mt-3 d-flex justify-content-end">
            <button type="button" class="btn btn-outline-primary btn-sm" id="btnIrContactos">
              Ir a Contactos →
            </button>
          </div>
        @endif

      </div>
    </div>
  </div>

  {{-- ============================= --}}
  {{-- PANEL 2: CONTACTOS (opcional) --}}
  {{-- ============================= --}}
  @if($showContactos)
    <div class="accordion-item soft-card section-contactos mt-3">
      <h2 class="accordion-header" id="headingContactos">
        <button class="accordion-button {{ $openContactos ? '' : 'collapsed' }}" type="button"
                data-bs-toggle="collapse"
                data-bs-target="#collapseContactos"
                aria-expanded="{{ $openContactos ? 'true' : 'false' }}"
                aria-controls="collapseContactos">
          <span class="me-2">👥</span> Contactos
          @if(is_array($contactos) && count($contactos) > 0)
            <span class="ms-2 badge text-bg-success">{{ count($contactos) }}</span>
          @endif
        </button>
      </h2>

      <div id="collapseContactos" class="accordion-collapse collapse {{ $openContactos ? 'show' : '' }}"
           aria-labelledby="headingContactos"
           data-bs-parent="#empresaAccordion">
        <div class="accordion-body">

          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="text-muted small">
              Agrega uno o varios contactos por empresa.
            </div>
            <button type="button" class="btn btn-outline-success btn-sm" id="btnAddContacto">
              + Agregar contacto
            </button>
          </div>

          <div id="contactosWrap">
            @forelse($contactos as $i => $c)
              <div class="card mb-3 js-contacto-item contact-card">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-semibold">Contacto #{{ $i + 1 }}</div>
                    <button type="button" class="btn btn-outline-danger btn-sm js-remove-contacto">Quitar</button>
                  </div>

                  <input type="hidden" name="contactos[{{ $i }}][id_contacto]" value="{{ $c['id_contacto'] ?? '' }}">

                  <div class="row g-2">
                    <div class="col-md-4">
                      <label class="form-label">Nombre</label>
                      <input type="text" class="form-control" name="contactos[{{ $i }}][nombre]"
                            value="{{ $c['nombre'] ?? '' }}">
                    </div>

                    <div class="col-md-4">
                      <label class="form-label">Apellido</label>
                      <input type="text" class="form-control" name="contactos[{{ $i }}][apellido]"
                            value="{{ $c['apellido'] ?? '' }}">
                    </div>

                    <div class="col-md-4">
                      <label class="form-label">Email</label>
                      <input type="text" class="form-control" name="contactos[{{ $i }}][email]"
                            value="{{ $c['email'] ?? '' }}">
                    </div>

                    <div class="col-md-3">
                      <label class="form-label">Teléfono</label>
                      <input type="text" class="form-control" name="contactos[{{ $i }}][telefono]"
                            value="{{ $c['telefono'] ?? '' }}">
                    </div>

                    <div class="col-md-3">
                      <label class="form-label">Celular</label>
                      <input type="text" class="form-control" name="contactos[{{ $i }}][celular]"
                            value="{{ $c['celular'] ?? '' }}">
                    </div>

                    <div class="col-md-3">
                      <label class="form-label">Puesto</label>
                      <input type="text" class="form-control" name="contactos[{{ $i }}][puesto]"
                            value="{{ $c['puesto'] ?? '' }}">
                    </div>

                    <div class="col-md-3">
                      <label class="form-label">Título</label>
                      <input type="text" class="form-control" name="contactos[{{ $i }}][titulo]"
                            value="{{ $c['titulo'] ?? '' }}">
                    </div>

                    <div class="col-md-6">
                      <label class="form-label">Departamento (contacto)</label>
                      <input type="text" class="form-control" name="contactos[{{ $i }}][departamento]"
                            value="{{ $c['departamento'] ?? '' }}">
                    </div>

                    <div class="col-md-6">
                      <label class="form-label">Dirección</label>
                      <input type="text" class="form-control" name="contactos[{{ $i }}][direccion]"
                            value="{{ $c['direccion'] ?? '' }}">
                    </div>

                    <div class="col-md-10">
                      <label class="form-label">Notas</label>
                      <input type="text" class="form-control" name="contactos[{{ $i }}][notas]"
                            value="{{ $c['notas'] ?? '' }}">
                    </div>

                    <div class="col-md-2">
                      <label class="form-label d-block">Activo</label>
                      <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox"
                              name="contactos[{{ $i }}][activo]" value="1"
                              @checked((int)($c['activo'] ?? 1) === 1)>
                        <label class="form-check-label">Sí</label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            @empty
              <div class="text-muted" id="noContactosMsg">
                No hay contactos aún. Agrega al menos uno si aplica.
              </div>
            @endforelse
          </div>

        </div>
      </div>
    </div>
  @endif

</div>

{{-- ============================= --}}
{{-- Botones finales (opcional) --}}
{{-- ============================= --}}
@if($showButtons)
  <div class="mt-4 d-flex gap-2">
    <button class="btn btn-primary" type="submit">Guardar</button>
    <a href="{{ route('empresas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
  </div>
@endif

{{-- ============================= --}}
{{-- JS: solo si se muestran contactos --}}
{{-- ============================= --}}
@if($showContactos)
  <script>
  document.addEventListener('DOMContentLoaded', () => {
    const wrap = document.getElementById('contactosWrap');
    const btnAdd = document.getElementById('btnAddContacto');
    const noMsg = document.getElementById('noContactosMsg');
    const btnIrContactos = document.getElementById('btnIrContactos');

    const nextIndex = () => wrap.querySelectorAll('.js-contacto-item').length;

    const ensureContactosOpen = () => {
      const el = document.getElementById('collapseContactos');
      if (!el) return;

      if (window.bootstrap && bootstrap.Collapse) {
        const inst = bootstrap.Collapse.getOrCreateInstance(el, { toggle: false });
        inst.show();
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
      } else {
        el.classList.add('show');
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    };

    btnIrContactos?.addEventListener('click', () => ensureContactosOpen());

    btnAdd?.addEventListener('click', () => {
      const i = nextIndex();
      if (noMsg) noMsg.remove();

      const html = `
        <div class="card mb-3 js-contacto-item contact-card pop-in">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="fw-semibold">Contacto #${i + 1}</div>
              <button type="button" class="btn btn-outline-danger btn-sm js-remove-contacto">Quitar</button>
            </div>

            <input type="hidden" name="contactos[${i}][id_contacto]" value="">

            <div class="row g-2">
              <div class="col-md-4">
                <label class="form-label">Nombre</label>
                <input type="text" class="form-control" name="contactos[${i}][nombre]">
              </div>

              <div class="col-md-4">
                <label class="form-label">Apellido</label>
                <input type="text" class="form-control" name="contactos[${i}][apellido]">
              </div>

              <div class="col-md-4">
                <label class="form-label">Email</label>
                <input type="text" class="form-control" name="contactos[${i}][email]">
              </div>

              <div class="col-md-3">
                <label class="form-label">Teléfono</label>
                <input type="text" class="form-control" name="contactos[${i}][telefono]">
              </div>

              <div class="col-md-3">
                <label class="form-label">Celular</label>
                <input type="text" class="form-control" name="contactos[${i}][celular]">
              </div>

              <div class="col-md-3">
                <label class="form-label">Puesto</label>
                <input type="text" class="form-control" name="contactos[${i}][puesto]">
              </div>

              <div class="col-md-3">
                <label class="form-label">Título</label>
                <input type="text" class="form-control" name="contactos[${i}][titulo]">
              </div>

              <div class="col-md-6">
                <label class="form-label">Departamento (contacto)</label>
                <input type="text" class="form-control" name="contactos[${i}][departamento]">
              </div>

              <div class="col-md-6">
                <label class="form-label">Dirección</label>
                <input type="text" class="form-control" name="contactos[${i}][direccion]">
              </div>

              <div class="col-md-10">
                <label class="form-label">Notas</label>
                <input type="text" class="form-control" name="contactos[${i}][notas]">
              </div>

              <div class="col-md-2">
                <label class="form-label d-block">Activo</label>
                <div class="form-check mt-2">
                  <input class="form-check-input" type="checkbox" name="contactos[${i}][activo]" value="1" checked>
                  <label class="form-check-label">Sí</label>
                </div>
              </div>
            </div>
          </div>
        </div>
      `;

      wrap.insertAdjacentHTML('beforeend', html);

      ensureContactosOpen();
      const last = wrap.querySelectorAll('.js-contacto-item');
      const card = last[last.length - 1];
      setTimeout(() => card?.querySelector('input[name^="contactos"]')?.focus(), 50);
    });

    document.addEventListener('click', (e) => {
      const btn = e.target.closest('.js-remove-contacto');
      if (!btn) return;

      const card = btn.closest('.js-contacto-item');
      card?.remove();

      const count = wrap.querySelectorAll('.js-contacto-item').length;
      if (count === 0) {
        wrap.insertAdjacentHTML('beforeend', `
          <div class="text-muted" id="noContactosMsg">
            No hay contactos aún. Agrega al menos uno si aplica.
          </div>
        `);
      }
    });
  });
  </script>
@endif

{{-- ============================= --}}
{{-- ESTILO: sombra suave + colores + animaciones --}}
{{-- ============================= --}}
<style>
  .soft-card{
    border: 0;
    border-radius: 16px;
    box-shadow: 0 10px 25px rgba(0,0,0,.06);
    overflow: hidden;
  }

  .soft-accordion .accordion-button{
    font-weight: 700;
    letter-spacing: .2px;
  }

  .soft-accordion .accordion-button:focus{
    box-shadow: none;
  }

  .soft-accordion .accordion-body{
    background: #fff;
  }

  .section-empresa{
    background: linear-gradient(180deg, rgba(13,110,253,.06), rgba(13,110,253,.00));
    border-left: 6px solid rgba(13,110,253,.85);
  }

  .section-contactos{
    background: linear-gradient(180deg, rgba(25,135,84,.07), rgba(25,135,84,.00));
    border-left: 6px solid rgba(25,135,84,.85);
  }

  .enter-anim{
    animation: fadeUp .35s ease-out both;
  }

  @keyframes fadeUp{
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  .pop-in{
    animation: popIn .22s ease-out both;
  }

  @keyframes popIn{
    from { opacity: 0; transform: scale(.98) translateY(6px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
  }

  .contact-card{
    border-radius: 14px;
    border: 1px solid rgba(0,0,0,.06);
    box-shadow: 0 6px 16px rgba(0,0,0,.05);
  }
</style>
