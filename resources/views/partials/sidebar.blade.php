@php
  $u = session('user'); // array guardado en sesión

  // Normalizamos rol desde sesión
  $rolName = strtoupper(trim($u['rol'] ?? $u['nombre_rol'] ?? ''));
  $rolId   = (int)($u['id_rol'] ?? 0);

  // Reglas simples (ajusta si quieres)
  $isAdmin  = ($rolId === 1) || $rolName === 'ADMIN';

  // Si quieres limitar por roles, modifica aquí:
  $canVentas   = true;
  $canAvances  = true;
  $canEmpresas = true;

  // IDs únicos por menú (por si lo renderizas en móvil/escritorio)
  $scope = $scope ?? 'desk';
  $idVentas   = "menuVentas-{$scope}";
  $idAvances  = "menuAvances-{$scope}";
  $idEmpresas = "menuEmpresas-{$scope}";

  // Abrir submenús cuando estás en módulo
  $ventasOpen = request()->routeIs('dashboard*') || request()->routeIs('ventas.*');
  $avOpen     = request()->routeIs('avances.*');
  $empOpen    = request()->routeIs('empresas.*');
@endphp

<div>
  <div class="sidebar-title">Navegación</div>
</div>

<div class="navlist">



  {{-- ================= AVANCES ================= --}}
  @if($canAvances)
    <button type="button"
            class="navitem btn-reset {{ $avOpen ? 'active' : '' }}"
            data-bs-toggle="collapse"
            data-bs-target="#{{ $idAvances }}"
            aria-expanded="{{ $avOpen ? 'true' : 'false' }}"
            aria-controls="{{ $idAvances }}"
            title="Avances">
      <span class="navicon">📝</span>
      <span>Avances</span>
      <span class="ms-auto navcaret">▾</span>
    </button>

    <div class="collapse {{ $avOpen ? 'show' : '' }}" id="{{ $idAvances }}">
      <div class="d-grid gap-1 ms-4 mt-1">

        <a href="{{ route('avances.dashboard') }}"
           class="navitem {{ request()->routeIs('avances.dashboard') ? 'active' : '' }}"
           title="Dashboard de avances">
          <span class="navicon">📈</span>
          <span>Dashboard</span>
        </a>


        <a href="{{ route('avances.create') }}"
           class="navitem {{ request()->routeIs('avances.create') ? 'active' : '' }}"
           title="Registrar avance">
          <span class="navicon">➕</span>
          <span>Registrar</span>
        </a>

        <a href="{{ route('avances.byDate') }}"
           class="navitem {{ request()->routeIs('avances.byDate') ? 'active' : '' }}"
           title="Consultar por fecha">
          <span class="navicon">📅</span>
          <span>Por fecha</span>
        </a>

      </div>
    </div>
  @endif


  {{-- ================= EMPRESAS ================= --}}
  @if($canEmpresas)
    <button type="button"
            class="navitem btn-reset {{ $empOpen ? 'active' : '' }}"
            data-bs-toggle="collapse"
            data-bs-target="#{{ $idEmpresas }}"
            aria-expanded="{{ $empOpen ? 'true' : 'false' }}"
            aria-controls="{{ $idEmpresas }}"
            title="Empresas">
      <span class="navicon">🏢</span>
      <span>Empresas</span>
      <span class="ms-auto navcaret">▾</span>
    </button>

    <div class="collapse {{ $empOpen ? 'show' : '' }}" id="{{ $idEmpresas }}">
      <div class="d-grid gap-1 ms-4 mt-1">

        <a href="{{ route('empresas.index') }}"
           class="navitem {{ request()->routeIs('empresas.index') ? 'active' : '' }}"
           title="Listado de empresas">
          <span class="navicon">📋</span>
          <span>Listado</span>
        </a>

        <a href="{{ route('empresas.create') }}"
           class="navitem {{ request()->routeIs('empresas.create') ? 'active' : '' }}"
           title="Crear empresa">
          <span class="navicon">➕</span>
          <span>Nueva</span>
        </a>

      </div>
    </div>
  @endif


  {{-- ================= CERRAR SESIÓN ================= --}}
  <form method="POST" action="{{ route('logout') }}" class="navlogout">
    @csrf
    <button type="submit"
            class="navitem btn-reset navlogout-btn"
            title="Cerrar la sesión actual">
      <span class="navicon">🚪</span>
      <span>Cerrar sesión</span>
      <span class="ms-auto navcaret navcaret-placeholder">▾</span>
    </button>
  </form>

</div>

<div class="sidebar-footer text-center text-white-50 small py-3 border-top mt-auto">
  © {{ date('Y') }} Seguimiento de Ventas
  <div class="opacity-75">
    Ingeniería que impulsa resultados.
    Arquitectura & Desarrollo <br> Ing. Aníbal Gómez
  </div>
</div>
