@extends('layouts.app')

@section('content')
<div class="container">

  {{-- HERO --}}
  <div class="card border-0 shadow-sm mb-4 overflow-hidden">
    <div class="card-body p-4 p-md-5">
      <div class="row align-items-center g-4">
        <div class="col-md-8">
          <h2 class="fw-bold mb-2">
            ¿Por qué hay que digitalizarse?
          </h2>

          <p class="text-muted mb-4 fs-5">
            Digitalización significa <span class="fw-semibold text-dark">competitividad</span>,
            <span class="fw-semibold text-dark">crecimiento</span>,
            <span class="fw-semibold text-dark">innovación</span>,
            <span class="fw-semibold text-dark">liderazgo</span> y
            <span class="fw-semibold text-dark">empleo</span>.
          </p>

          <div class="d-flex flex-wrap gap-2">
            <span class="badge rounded-pill text-bg-primary px-3 py-2">Competitividad</span>
            <span class="badge rounded-pill text-bg-success px-3 py-2">Crecimiento</span>
            <span class="badge rounded-pill text-bg-warning px-3 py-2">Innovación</span>
            <span class="badge rounded-pill text-bg-info px-3 py-2">Liderazgo</span>
            <span class="badge rounded-pill text-bg-dark px-3 py-2">Empleo</span>
          </div>
        </div>

        <div class="col-md-4 text-md-end">
        <!--   <div class="p-3 rounded-4 bg-light">
           <div class="fw-semibold mb-1">Siguiente paso</div>
            <div class="text-muted small mb-3">
              Centraliza enlaces, proyectos y comunicación en un solo lugar.
            </div>
            <a href="#proyectos" class="btn btn-primary w-100">
              Ver comunicaciones ↓
            </a>
          </div>-->
        </div>
      </div>
    </div>
  </div>

  {{-- SITIOS PRINCIPALES --}}
  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <div class="card shadow-sm h-100 border-0">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <h5 class="fw-bold mb-1">Asociación Amigos Pro Obras Sociales</h5>
              <div class="text-muted small">Sitio oficial</div>
            </div>
            <span class="badge text-bg-success">Web</span>
          </div>

          <div class="mt-3">
            <a class="btn btn-outline-primary w-100"
               href="https://www.amigosproobras.org/"
               target="_blank" rel="noopener">
              Abrir sitio
            </a>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card shadow-sm h-100 border-0">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <h5 class="fw-bold mb-1">Obras Sociales del Santo Hermano Pedro</h5>
              <div class="text-muted small">Sitio oficial</div>
            </div>
            <span class="badge text-bg-success">Web</span>
          </div>

          <div class="mt-3">
            <a class="btn btn-outline-primary w-100"
               href="https://hermanopedrogt.org/"
               target="_blank" rel="noopener">
              Abrir sitio
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- COMUNICACIONES --}}
  <div id="comunicaciones" class="card shadow-sm border-0 mb-4">
    <div class="card-body p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h4 class="fw-bold mb-0">Comunicaciones</h4>
          <div class="text-muted">Acceso rápido a contenido y redes sociales</div>
        </div>
        <span class="badge text-bg-primary px-3 py-2">Centralizado</span>
      </div>

      <div class="row g-3">
        {{-- FLICKR --}}
        <div class="col-md-5">
          <div class="p-3 rounded-4 bg-light h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div class="fw-semibold">Flickr</div>
              <span class="badge text-bg-dark">Álbumes</span>
            </div>

            <div class="d-grid gap-2">
              <a class="btn btn-outline-dark"
                 href="https://www.flickr.com/photos/200997072@N06/albums/"
                 target="_blank" rel="noopener">
                📸 2024 (Álbumes)
              </a>

              <a class="btn btn-outline-dark"
                 href="https://www.flickr.com/photos/201481385@N08/albums/"
                 target="_blank" rel="noopener">
                📸 2025 (Álbumes)
              </a>
            </div>

            <div class="text-muted small mt-3">
              Repositorios de Imagenes
            </div>
          </div>
        </div>

        {{-- REDES --}}
        <div class="col-md-7">
          <div class="p-3 rounded-4 bg-light h-100">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div class="fw-semibold">Redes Sociales</div>
              <span class="badge text-bg-info">Social</span>
            </div>

            <div class="row g-2">
              <div class="col-sm-6">
                <a class="btn btn-outline-primary w-100"
                   href="https://www.facebook.com/amigosproobras"
                   target="_blank" rel="noopener">
                  Facebook
                </a>
              </div>

              <div class="col-sm-6">
                <a class="btn btn-outline-danger w-100"
                   href="https://www.instagram.com/amigosproobras"
                   target="_blank" rel="noopener">
                  Instagram
                </a>
              </div>

              <div class="col-sm-6">
                <a class="btn btn-outline-dark w-100"
                   href="https://www.tiktok.com/@amigosproobras"
                   target="_blank" rel="noopener">
                  TikTok
                </a>
              </div>

              <div class="col-sm-6">
                <a class="btn btn-outline-danger w-100"
                   href="https://www.youtube.com/@amigosproobras"
                   target="_blank" rel="noopener">
                  YouTube
                </a>
              </div>
            </div>

            <div class="text-muted small mt-3">
              Canales De Comunicación
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  {{-- PROYECTOS --}}
  <div class="card shadow-sm border-0">
    <div class="card-body p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h4 class="fw-bold mb-0">Proyectos</h4>
          <div class="text-muted">Iniciativas en marcha</div>
        </div>
        <span class="badge text-bg-success px-3 py-2">Roadmap</span>
      </div>

      <!--<div class="row g-3">
        <div class="col-md-4">
          <div class="card border-0 bg-light h-100">
            <div class="card-body">
              <div class="fw-bold mb-1">Digitalización</div>
              <div class="text-muted small">
                Ordenar información, automatizar procesos y acelerar resultados.
              </div>
              <div class="mt-3">
                <span class="badge text-bg-primary">Prioridad alta</span>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card border-0 bg-light h-100">
            <div class="card-body">
              <div class="fw-bold mb-1">Calidad de Vida 1</div>
              <div class="text-muted small">
                Proyecto orientado a mejorar procesos y experiencias.
              </div>
              <div class="mt-3">
                <span class="badge text-bg-warning">En planificación</span>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card border-0 bg-light h-100">
            <div class="card-body">
              <div class="fw-bold mb-1">Calidad de Vida 2</div>
              <div class="text-muted small">
                Continuación y expansión del proyecto anterior.
              </div>
              <div class="mt-3">
                <span class="badge text-bg-secondary">Backlog</span>
              </div>
            </div>
          </div>
        </div>
      </div> -->

      <hr class="my-4">

      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="text-muted small">
          “Digitalizarse no es moda: es sobrevivir y crecer.”
        </div>

      </div>

    </div>
  </div>

</div>

<style>
  /* toque suave: redondeo consistente */
  .rounded-4 { border-radius: 16px !important; }
</style>
@endsection
