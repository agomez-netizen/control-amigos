{{-- resources/views/avances/dashboard.blade.php --}}
@extends('layouts.app')

@section('title', 'Dashboard de Avances')

@section('content')
<div class="container py-4">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Dashboard de Avances</h4>

    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="{{ route('avances.create') }}">➕ Registrar</a>
      <a class="btn btn-outline-secondary" href="{{ route('avances.byDate') }}">📅 Por fecha</a>
    </div>
  </div>

  {{-- Filtros --}}
  <div class="card shadow-sm border-0 mb-3">
    <div class="card-body">
      <form method="GET" action="{{ route('avances.dashboard') }}" class="row g-3 align-items-end">
        <div class="col-md-4">
          <label class="form-label">Desde</label>
          <input type="date" name="desde" value="{{ $desde }}" class="form-control">
        </div>

        <div class="col-md-4">
          <label class="form-label">Hasta</label>
          <input type="date" name="hasta" value="{{ $hasta }}" class="form-control">
        </div>

        <div class="col-md-4 d-flex gap-2">
          <button class="btn btn-primary flex-grow-1" type="submit">Filtrar</button>
          <a class="btn btn-outline-secondary" href="{{ route('avances.dashboard') }}">Limpiar</a>
        </div>
      </form>
    </div>
  </div>

  {{-- Tarjetas resumen --}}
  <div class="row g-3 mb-3">
    <div class="col-md-4">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <div class="text-muted">Total de avances</div>
          <div class="fs-3 fw-bold">{{ $totalAvances }}</div>
        </div>
      </div>
    </div>

    <div class="col-md-8">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <div class="text-muted">Punto de Venta con más avances</div>
          <div class="fs-5 fw-bold">
            {{ $topEmpresa?->nombre ?? '—' }}
            @if($topEmpresa)
              <span class="badge bg-success ms-2">{{ $topEmpresa->total }}</span>
            @endif
          </div>
          <div class="text-muted small">
            @if($desde || $hasta)
              Rango aplicado: {{ $desde ?: '—' }} a {{ $hasta ?: '—' }}
            @else
              Sin filtro de fechas
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Gráfica --}}
  <div class="card shadow-sm border-0">
    <div class="card-header bg-white">
      <div class="fw-semibold">Avances por Punto de Venta (barras)</div>
      <div class="text-muted small">Cuenta cuántos avances tiene cada Punto de Venta.</div>
    </div>
    <div class="card-body">
      @if(count($labels) === 0)
        <div class="alert alert-info mb-0">No hay datos para graficar con esos filtros.</div>
      @else
        <canvas id="barChart" height="110"></canvas>
      @endif
    </div>
  </div>

</div>
@endsection

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

  <script>
    const labels = @json($labels);
    const data = @json($data);

    const el = document.getElementById('barChart');
    if (el) {
      new Chart(el, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [{
            label: 'Avances',
            data: data,
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { display: true },
            tooltip: { enabled: true }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: { precision: 0 }
            }
          }
        }
      });
    }
  </script>
@endpush
