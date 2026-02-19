{{-- resources/views/ventas/create.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-4">

    <style>
        /* Base */
        .card-soft { border: 1px solid #e5e7eb; border-radius: 14px; background: #fff; }
        .section-title { font-weight: 700; letter-spacing: .3px; }
        .muted { color: #6b7280; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        @media (max-width: 992px){ .grid-2 { grid-template-columns: 1fr; } }
        .table-wrap { overflow-x: auto; }

        /* Badge con fondo para estados */
        .estado-badge{
            display:inline-block;
            padding:6px 12px;
            border-radius:999px;
            font-weight:700;
            font-size:13px;
            line-height:1;
            color:#fff;
        }
        .estado-liquidado{ background:#198754; }                   /* verde */
        .estado-proceso{ background:#ffc107; color:#111827; }      /* amarillo */
        .estado-perdido{ background:#dc3545; }                     /* rojo */
        .estado-sin{ background:#6c757d; }                         /* gris */

        /* Checkbox bonitos y notorios */
        .talonario-check{
            width: 22px;
            height: 22px;
            cursor: pointer;
            accent-color: #0d6efd; /* navegadores modernos */
        }
        .check-cell{
            width: 52px;
            text-align: center;
            vertical-align: middle;
        }
        /* opcional: ayuda a separar la columna visualmente */
        .check-col-divider{
            position: relative;
        }
        .check-col-divider:after{
            content:"";
            position:absolute;
            top:10px; bottom:10px; right:0;
            width:1px;
            background:#e5e7eb;
        }
    </style>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('debug'))
        <div class="alert alert-secondary">
            <div class="fw-bold mb-2">Debug (modo maqueta):</div>
            <pre class="mb-0" style="white-space: pre-wrap;">{{ print_r(session('debug'), true) }}</pre>
        </div>
    @endif

    <div class="row g-4">
        {{-- Header / Bienvenida --}}
        <div class="col-12">
            <div class="p-4 card-soft">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <h3 class="mb-2">Bienvenido</h3>

                        <div class="mb-2">
                            <span class="fw-bold">TM:</span>
                            <span class="ms-1">{{ $tm ?? '—' }}</span>
                        </div>

                        <div>
                            <span class="fw-bold">Operadores:</span>
                            <ul class="mb-0 mt-1 muted">
                                @foreach(($operadores ?? []) as $op)
                                    <li>{{ $op }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    <div style="min-width: 260px;">
                        <label class="form-label section-title mb-2">Estaciones</label>
                        <select class="form-select" name="estacion" form="ventaForm" required>
                            <option value="">-- Selecciona --</option>
                            @foreach(($estaciones ?? []) as $est)
                                <option value="{{ $est }}" {{ old('estacion') == $est ? 'selected' : '' }}>
                                    {{ $est }}
                                </option>
                            @endforeach
                        </select>
                        @error('estacion')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                        <div class="form-text muted">Elige la estación donde se realizará la liquidación.</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Form --}}
        <div class="col-12">
            {{-- IMPORTANTE: multipart para subir archivo --}}
            <form id="ventaForm" method="POST" action="{{ route('ventas.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="grid-2">
                    {{-- Talonario --}}
                    <div class="p-4 card-soft">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="mb-0 section-title">Talonario</h5>
                            <small class="muted">Selecciona un número</small>
                        </div>

                        <div class="table-wrap">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="check-cell check-col-divider"></th>
                                        <th>Número</th>
                                        <th>Estado</th>
                                        <th class="text-end">Monto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(($talonarios ?? []) as $t)
                                        @php
                                            $estado = $t['estado'] ?? null;
                                            $estadoTexto = $estado ?: 'Sin vender';

                                            $estadoClase = match ($estadoTexto) {
                                                'Liquidado'  => 'estado-liquidado',
                                                'En Proceso' => 'estado-proceso',
                                                'Perdido'    => 'estado-perdido',
                                                default      => 'estado-sin',
                                            };

                                            $monto = $t['monto'] ?? null;
                                        @endphp

                                        <tr>
                                            {{-- CHECKBOX NOTORIO --}}
                                            <td class="check-cell check-col-divider">
                                                <input
                                                    class="talonario-check"
                                                    type="checkbox"
                                                    name="talonarios[]"
                                                    value="{{ $t['numero'] }}"
                                                    {{ is_array(old('talonarios')) && in_array($t['numero'], old('talonarios')) ? 'checked' : '' }}
                                                >
                                            </td>

                                            <td class="fw-semibold">
                                                {{ $t['numero'] }}
                                            </td>

                                            <td>
                                                <span class="estado-badge {{ $estadoClase }}">
                                                    {{ $estadoTexto }}
                                                </span>
                                            </td>

                                            <td class="text-end {{ $estadoTexto === 'Perdido' ? 'text-danger' : 'text-muted' }}">
                                                @if(is_null($monto))
                                                    —
                                                @else
                                                    Q {{ number_format($monto, 2) }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @error('talonarios')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Datos de la Liquidación --}}
                    <div class="p-4 card-soft">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="mb-0 section-title">Datos de la Liquidación</h5>
                        </div>

                        {{-- Montos --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Monto Calculado</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    value="Q {{ number_format($montoCalculado ?? 0, 2) }}"
                                    disabled
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Monto en Boleta</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    name="monto_en_boleta"
                                    class="form-control"
                                    placeholder="Ej: 200.00"
                                    value="{{ old('monto_en_boleta') }}"
                                    required
                                >
                                @error('monto_en_boleta')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Banco</label>
                                <select class="form-select" name="banco" required>
                                    <option value="">-- Selecciona --</option>
                                    @foreach(($bancos ?? []) as $banco)
                                        <option value="{{ $banco }}" {{ old('banco') == $banco ? 'selected' : '' }}>
                                            {{ $banco }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('banco') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Fecha</label>
                                <input
                                    type="date"
                                    class="form-control"
                                    name="fecha"
                                    value="{{ old('fecha') }}"
                                    required
                                >
                                @error('fecha') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            {{-- No. Boleta + Cargar archivo (a la par) --}}
                            <div class="col-md-6">
                                <label class="form-label">No. Boleta</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    name="no_boleta"
                                    placeholder="Ej: 000123"
                                    value="{{ old('no_boleta') }}"
                                    required
                                >
                                @error('no_boleta') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Cargar archivo</label>
                                <input
                                    type="file"
                                    class="form-control"
                                    name="boleta_archivo"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                >
                                <div class="form-text muted">PDF o imagen (JPG/PNG).</div>
                                @error('boleta_archivo') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Nota</label>
                                <textarea
                                    class="form-control"
                                    rows="4"
                                    name="nota"
                                    placeholder="Escribe una nota (opcional)"
                                >{{ old('nota') }}</textarea>
                                @error('nota') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12 d-flex justify-content-end pt-2">
                                <button type="submit" class="btn btn-primary px-4">
                                    Guardar
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </form>
        </div>

    </div>
</div>
@endsection
