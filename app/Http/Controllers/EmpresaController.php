<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\BaseDeDatos;
use App\Models\TipoEmpresa;
use App\Models\Contacto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Exports\ArrayExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class EmpresaController extends Controller
{
    // =========================
    // LISTADO + FILTROS
    // =========================
    public function index(Request $request)
    {
        $q      = trim((string) $request->get('q', ''));
        $idBase = $request->get('id_base_datos');
        $idTipo = $request->get('id_tipo_empresa');
        $activo = $request->get('activo'); // '', '1', '0'
        $pais   = trim((string) $request->get('pais', ''));
        $depto  = trim((string) $request->get('departamento', ''));
        $muni   = trim((string) $request->get('municipio', ''));

        $empresasQuery = Empresa::query()
            ->with(['baseDeDatos', 'tipoEmpresa'])
            ->withCount('contactos');

        if (!empty($idBase)) {
            $empresasQuery->where('id_base_datos', $idBase);
        }

        if (!empty($idTipo)) {
            $empresasQuery->where('id_tipo_empresa', $idTipo);
        }

        if ($activo === '0' || $activo === '1') {
            $empresasQuery->where('activo', (int) $activo);
        }

        if ($pais !== '') $empresasQuery->where('pais', 'like', "%{$pais}%");
        if ($depto !== '') $empresasQuery->where('departamento', 'like', "%{$depto}%");
        if ($muni !== '') $empresasQuery->where('municipio', 'like', "%{$muni}%");

        if ($q !== '') {
            $empresasQuery->where(function ($w) use ($q) {
                $w->where('nombre', 'like', "%{$q}%")
                  ->orWhere('notas', 'like', "%{$q}%")
                  ->orWhere('detalles', 'like', "%{$q}%")
                  ->orWhere('sitio_web', 'like', "%{$q}%")
                  ->orWhereHas('baseDeDatos', function ($b) use ($q) {
                      $b->where('nombre', 'like', "%{$q}%");
                  })
                  ->orWhereHas('tipoEmpresa', function ($t) use ($q) {
                      $t->where('nombre', 'like', "%{$q}%");
                  })
                  ->orWhereHas('contactos', function ($c) use ($q) {
                      $c->where('nombre', 'like', "%{$q}%")
                        ->orWhere('apellido', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('telefono', 'like', "%{$q}%")
                        ->orWhere('celular', 'like', "%{$q}%")
                        ->orWhere('puesto', 'like', "%{$q}%")
                        ->orWhere('direccion', 'like', "%{$q}%");
                  });
            });
        }

        $empresas = $empresasQuery->orderBy('nombre')->paginate(5)->withQueryString();

        $bases = BaseDeDatos::orderBy('nombre')->get();
        $tipos = TipoEmpresa::orderBy('nombre')->get();

        return view('empresas.index', compact('empresas', 'bases', 'tipos'));
    }

    // =========================
    // CRUD
    // =========================
public function create()
{
    $bases = BaseDeDatos::orderBy('nombre')->get();
    $tipos = TipoEmpresa::orderBy('nombre')->get();

    $user = session('user'); // <- tu login guarda esto
    $gestorAapos = $user ? trim(($user['nombre'] ?? '') . ' ' . ($user['apellido'] ?? '')) : '';

    return view('empresas.create', compact('bases', 'tipos', 'gestorAapos'));
}

public function store(Request $request)
{
    $data = $request->validate([
        // Empresa
        'nombre' => ['required', 'string', 'max:120'],
        'id_base_datos' => ['required', 'integer', 'exists:base_de_datos,id_base_datos'],
        'id_tipo_empresa' => ['nullable', 'integer', 'exists:tipo_empresa,id_tipo_empresa'],

        'activo' => ['nullable', 'boolean'],
        'descripcion' => ['nullable', 'string', 'max:255'],
        'pais' => ['nullable', 'string', 'max:80'],
        'departamento' => ['nullable', 'string', 'max:120'],
        'municipio' => ['nullable', 'string', 'max:120'],
        'sitio_web' => ['nullable', 'string', 'max:180'],
        'detalles' => ['nullable', 'string'],
        'notas' => ['nullable', 'string'],

        // Contactos (repetible)
        'contactos' => ['nullable', 'array'],
        'contactos.*.nombre' => ['nullable', 'string', 'max:120'],
        'contactos.*.apellido' => ['nullable', 'string', 'max:120'],
        'contactos.*.telefono' => ['nullable', 'string', 'max:30'],
        'contactos.*.celular' => ['nullable', 'string', 'max:30'],
        'contactos.*.email' => ['nullable', 'string', 'max:190'],
        'contactos.*.direccion' => ['nullable', 'string', 'max:255'],
        'contactos.*.puesto' => ['nullable', 'string', 'max:120'],
        'contactos.*.departamento' => ['nullable', 'string', 'max:120'],
        'contactos.*.titulo' => ['nullable', 'string', 'max:120'],
        'contactos.*.notas' => ['nullable', 'string'],
        'contactos.*.activo' => ['nullable', 'boolean'],
    ]);

    // Normaliza activo (si no viene el checkbox => false)
    $data['activo'] = $request->boolean('activo');

    // Fuerza gestor_aapos (Nombre Apellido del usuario logueado)
    $user = session('user');
    $data['gestor_aapos'] = $user
        ? trim(($user['nombre'] ?? '') . ' ' . ($user['apellido'] ?? ''))
        : 'Sistema';

    DB::transaction(function () use ($data) {
        // Separar contactos
        $contactos = $data['contactos'] ?? [];
        unset($data['contactos']);

        // Crear empresa
        $empresa = Empresa::create($data);

        // Crear contactos
        foreach ($contactos as $c) {
            $nombre   = trim((string)($c['nombre'] ?? ''));
            $apellido = trim((string)($c['apellido'] ?? ''));
            $email    = trim((string)($c['email'] ?? ''));

            // Si no hay nada útil, no lo creamos
            if ($nombre === '' && $apellido === '' && $email === '') {
                continue;
            }

            $empresa->contactos()->create([
                'nombre' => $nombre !== '' ? $nombre : 'Contacto',
                'apellido' => $apellido !== '' ? $apellido : null,
                'telefono' => isset($c['telefono']) && trim((string)$c['telefono']) !== '' ? trim((string)$c['telefono']) : null,
                'celular'  => isset($c['celular']) && trim((string)$c['celular']) !== '' ? trim((string)$c['celular']) : null,
                'email'    => $email !== '' ? $email : null,
                'direccion'=> isset($c['direccion']) && trim((string)$c['direccion']) !== '' ? trim((string)$c['direccion']) : null,
                'puesto'   => isset($c['puesto']) && trim((string)$c['puesto']) !== '' ? trim((string)$c['puesto']) : null,
                'departamento' => isset($c['departamento']) && trim((string)$c['departamento']) !== '' ? trim((string)$c['departamento']) : null,
                'titulo'   => isset($c['titulo']) && trim((string)$c['titulo']) !== '' ? trim((string)$c['titulo']) : null,
                'notas'    => isset($c['notas']) && trim((string)$c['notas']) !== '' ? trim((string)$c['notas']) : null,
                'activo'   => isset($c['activo']) ? (int) !!$c['activo'] : 1,
            ]);
        }
    });

    return redirect()
        ->route('empresas.index')
        ->with('success', 'Empresa creada correctamente.');
}

public function show(Request $request, $id)
{
    $empresa = Empresa::findOrFail($id);

    $cq = trim((string) $request->get('cq', ''));

    $contactosQuery = $empresa->contactos()->orderBy('id_contacto', 'desc');

    if ($cq !== '') {
        $contactosQuery->where(function($w) use ($cq) {
            $w->where('nombre', 'like', "%{$cq}%")
              ->orWhere('apellido', 'like', "%{$cq}%")
              ->orWhere('email', 'like', "%{$cq}%")
              ->orWhere('telefono', 'like', "%{$cq}%")
              ->orWhere('celular', 'like', "%{$cq}%")
              ->orWhere('puesto', 'like', "%{$cq}%")
              ->orWhere('departamento', 'like', "%{$cq}%")
              ->orWhere('titulo', 'like', "%{$cq}%");
        });
    }

    // Paginado en show (puedes cambiar 10)
    $contactos = $contactosQuery->paginate(5)->withQueryString();

    return view('empresas.show', compact('empresa', 'contactos', 'cq'));
}

public function edit(Request $request, $id)
{
    $empresa = Empresa::findOrFail($id);

    $bases = BaseDeDatos::orderBy('nombre')->get();
    $tipos = TipoEmpresa::orderBy('nombre')->get();

    // Filtros de contactos
    $cq       = trim((string) $request->get('cq', ''));        // búsqueda general
    $c_puesto = trim((string) $request->get('c_puesto', ''));  // puesto
    $c_activo = $request->get('c_activo');                     // '', '1', '0'

    $contactosQuery = $empresa->contactos()->orderBy('id_contacto', 'desc');

    if ($cq !== '') {
        $contactosQuery->where(function($w) use ($cq) {
            $w->where('nombre', 'like', "%{$cq}%")
              ->orWhere('apellido', 'like', "%{$cq}%")
              ->orWhere('email', 'like', "%{$cq}%")
              ->orWhere('telefono', 'like', "%{$cq}%")
              ->orWhere('celular', 'like', "%{$cq}%")
              ->orWhere('puesto', 'like', "%{$cq}%")
              ->orWhere('departamento', 'like', "%{$cq}%")
              ->orWhere('titulo', 'like', "%{$cq}%");
        });
    }

    if ($c_puesto !== '') {
        $contactosQuery->where('puesto', 'like', "%{$c_puesto}%");
    }

    if ($c_activo === '0' || $c_activo === '1') {
        $contactosQuery->where('activo', (int) $c_activo);
    }

    // Paginado de contactos (8 por página)
    $contactos = $contactosQuery->paginate(5)->withQueryString();

    // Si eliges un contacto específico para editar (por querystring)
    $editContactoId = (int) $request->get('edit_contacto', 0);
    $contactoEdit = $editContactoId
        ? $empresa->contactos()->where('id_contacto', $editContactoId)->first()
        : null;

    return view('empresas.edit', compact(
        'empresa','bases','tipos',
        'contactos','cq','c_puesto','c_activo',
        'contactoEdit'
    ));
}

public function update(Request $request, $id)
{
    $empresa = Empresa::findOrFail($id);

    $data = $request->validate([
        'nombre' => ['required','string','max:120'],
        'id_base_datos' => ['required','integer','exists:base_de_datos,id_base_datos'],
        'id_tipo_empresa' => ['nullable','integer','exists:tipo_empresa,id_tipo_empresa'],

        'activo' => ['nullable','boolean'],
        'descripcion' => ['nullable','string','max:255'],
        'pais' => ['nullable','string','max:80'],
        'departamento' => ['nullable','string','max:120'],
        'municipio' => ['nullable','string','max:120'],
        'sitio_web' => ['nullable','string','max:180'],
        'detalles' => ['nullable','string'],
        'notas' => ['nullable','string'],
        'gestor_aapos' => ['nullable','string','max:120'],
    ]);

    $data['activo'] = $request->boolean('activo');

    $empresa->update($data);

    return redirect()
      ->route('empresas.edit', $empresa->id_empresa)
      ->with('success', 'Empresa actualizada correctamente.');
}

    public function destroy($id)
    {
        $empresa = Empresa::findOrFail($id);
        $empresa->delete();

        return redirect()->route('empresas.index')->with('success', 'Empresa eliminada correctamente.');
    }

    // =========================
    // IMPORT
    // =========================
    public function importForm()
    {
        return view('empresas.import');
    }

    private function normHeader(string $h): string
    {
        $h = mb_strtolower(trim($h));
        $h = preg_replace('/\s+/', '_', $h);
        $h = str_replace(['á','é','í','ó','ú','ñ'], ['a','e','i','o','u','n'], $h);
        $h = preg_replace('/[^a-z0-9_]/', '', $h);
        return $h;
    }

    private function sheetToRows($sheet): array
    {
        $highestRow = $sheet->getHighestRow();
        $highestCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn());

        // headers row 1
        $headers = [];
        for ($c = 1; $c <= $highestCol; $c++) {
            $headers[] = $this->normHeader((string)$sheet->getCellByColumnAndRow($c, 1)->getValue());
        }

        $rows = [];
        for ($r = 2; $r <= $highestRow; $r++) {
            $row = [];
            $allEmpty = true;

            for ($c = 1; $c <= $highestCol; $c++) {
                $key = $headers[$c - 1] ?: "col_{$c}";
                $val = $sheet->getCellByColumnAndRow($c, $r)->getValue();
                $val = is_string($val) ? trim($val) : $val;

                if ($val !== null && $val !== '') $allEmpty = false;
                $row[$key] = $val;
            }

            if (!$allEmpty) $rows[] = $row;
        }

        return $rows;
    }

    public function importExcel(Request $request)
    {
        $request->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx'],
        ]);

        $filePath = $request->file('archivo')->getRealPath();

        $errors = [];
        $createdEmpresas = 0;
        $createdContactos = 0;

        try {
            $spreadsheet = IOFactory::load($filePath);

            $wsEmp = $spreadsheet->getSheetByName('Empresas');
            $wsCon = $spreadsheet->getSheetByName('Contactos');

            // Compat: si vienen hojas viejas
            $wsTel = $spreadsheet->getSheetByName('Telefonos');
            $wsCel = $spreadsheet->getSheetByName('Celulares');

            if (!$wsEmp) {
                return back()->with('errors_import', ["No existe la hoja 'Empresas'."]);
            }

            $empRows = $this->sheetToRows($wsEmp);
            $conRows = $wsCon ? $this->sheetToRows($wsCon) : [];

            DB::transaction(function () use (&$errors, &$createdEmpresas, &$createdContactos, $empRows, $conRows, $wsTel, $wsCel) {

                // 1) Importar empresas
                foreach ($empRows as $row) {
                    $nombre = trim((string)($row['nombre'] ?? $row['empresa'] ?? ''));
                    if ($nombre === '') {
                        $errors[] = "Fila de Empresas sin nombre.";
                        continue;
                    }

                    $baseNombre = trim((string)($row['base_de_datos'] ?? $row['base'] ?? $row['nombre_base_datos'] ?? ''));
                    if ($baseNombre === '') $baseNombre = 'DB_PRINCIPAL';

                    $base = BaseDeDatos::firstOrCreate(
                        ['nombre' => $baseNombre],
                        ['descripcion' => null]
                    );

                    $tipoNombre = trim((string)($row['tipo_empresa'] ?? $row['tipo'] ?? ''));
                    $tipo = null;
                    if ($tipoNombre !== '') {
                        $tipo = TipoEmpresa::firstOrCreate(
                            ['nombre' => $tipoNombre],
                            ['descripcion' => null, 'activo' => 1]
                        );
                    }

                    $payload = [
                        'nombre' => $nombre,
                        'descripcion' => $row['descripcion'] ?? null,
                        'activo' => isset($row['activo']) ? (int) !!$row['activo'] : 1,
                        'pais' => $row['pais'] ?? 'Guatemala',
                        'departamento' => $row['departamento'] ?? null,
                        'municipio' => $row['municipio'] ?? null,
                        'sitio_web' => $row['sitio_web'] ?? $row['web'] ?? null,
                        'detalles' => $row['detalles'] ?? null,
                        'notas' => $row['notas'] ?? null,
                        'gestor_aapos' => $row['gestor_aapos'] ?? null,
                        'id_base_datos' => (int)$base->id_base_datos,
                        'id_tipo_empresa' => $tipo ? (int)$tipo->id_tipo_empresa : null,
                    ];

                    $empresa = Empresa::firstOrCreate(['nombre' => $nombre], $payload);
                    if ($empresa->wasRecentlyCreated) {
                        $createdEmpresas++;
                    } else {
                        // si ya existe, actualizamos campos clave
                        $empresa->update($payload);
                    }
                }

                // 2) Importar contactos (si viene hoja nueva)
                if (!empty($conRows)) {
                    foreach ($conRows as $row) {
                        $empresaNombre = trim((string)($row['empresa'] ?? $row['nombre_empresa'] ?? ''));
                        $empresa = $empresaNombre !== '' ? Empresa::where('nombre', $empresaNombre)->first() : null;
                        if (!$empresa) {
                            $errors[] = "Contacto sin empresa válida: '{$empresaNombre}'.";
                            continue;
                        }

                        $nombre = trim((string)($row['nombre'] ?? ''));
                        $apellido = trim((string)($row['apellido'] ?? ''));
                        $email = trim((string)($row['email'] ?? ''));

                        if ($nombre === '' && $apellido === '' && $email === '') {
                            continue;
                        }

                        $empresa->contactos()->create([
                            'nombre' => $nombre !== '' ? $nombre : 'Contacto',
                            'apellido' => $apellido !== '' ? $apellido : null,
                            'telefono' => trim((string)($row['telefono'] ?? '')) ?: null,
                            'celular'  => trim((string)($row['celular'] ?? '')) ?: null,
                            'email'    => $email !== '' ? $email : null,
                            'direccion'=> trim((string)($row['direccion'] ?? '')) ?: null,
                            'puesto'   => trim((string)($row['puesto'] ?? '')) ?: null,
                            'departamento' => trim((string)($row['departamento'] ?? '')) ?: null,
                            'titulo'   => trim((string)($row['titulo'] ?? '')) ?: null,
                            'notas'    => trim((string)($row['notas'] ?? '')) ?: null,
                            'activo'   => isset($row['activo']) ? (int) !!$row['activo'] : 1,
                        ]);

                        $createdContactos++;
                    }
                }

                // 3) Compat: hojas viejas Telefonos/Celulares (si existen)
                //    -> crea un contacto "Contacto" y le mete telefono/celular
                if ($wsTel) {
                    $telRows = $this->sheetToRows($wsTel);
                    foreach ($telRows as $row) {
                        $empresaId = $row['id_empresa'] ?? null;
                        $telefono = trim((string)($row['telefono'] ?? ''));
                        if (!$empresaId || $telefono === '') continue;

                        $empresa = Empresa::find($empresaId);
                        if (!$empresa) continue;

                        $empresa->contactos()->create([
                            'nombre' => 'Contacto',
                            'telefono' => $telefono,
                            'notas' => $row['nota'] ?? 'Importado (Telefonos)',
                            'activo' => 1,
                        ]);
                        $createdContactos++;
                    }
                }

                if ($wsCel) {
                    $celRows = $this->sheetToRows($wsCel);
                    foreach ($celRows as $row) {
                        $empresaId = $row['id_empresa'] ?? null;
                        $celular = trim((string)($row['celular'] ?? ''));
                        if (!$empresaId || $celular === '') continue;

                        $empresa = Empresa::find($empresaId);
                        if (!$empresa) continue;

                        $empresa->contactos()->create([
                            'nombre' => 'Contacto',
                            'celular' => $celular,
                            'notas' => $row['nota'] ?? 'Importado (Celulares)',
                            'activo' => 1,
                        ]);
                        $createdContactos++;
                    }
                }
            });

        } catch (\Throwable $e) {
            return back()->with('errors_import', ["Error leyendo el archivo: " . $e->getMessage()]);
        }

        $msg = "Importación lista. Empresas: {$createdEmpresas}. Contactos: {$createdContactos}.";
        return redirect()->route('empresas.index')
            ->with('success', $msg)
            ->with('errors_import', $errors);
    }


public function contactoStore(Request $request, $empresaId)
{
    $empresa = Empresa::findOrFail($empresaId);

    $data = $request->validate([
        'nombre' => ['required','string','max:120'],
        'apellido' => ['nullable','string','max:120'],
        'telefono' => ['nullable','string','max:30'],
        'celular' => ['nullable','string','max:30'],
        'email' => ['nullable','string','max:190'],
        'direccion' => ['nullable','string','max:255'],
        'puesto' => ['nullable','string','max:120'],
        'departamento' => ['nullable','string','max:120'],
        'titulo' => ['nullable','string','max:120'],
        'notas' => ['nullable','string'],
        'activo' => ['nullable','boolean'],
    ]);

    $data['activo'] = $request->boolean('activo');

    $empresa->contactos()->create($data);

    return back()->with('success', 'Contacto agregado.');
}

public function contactoUpdate(Request $request, $empresaId, $contactoId)
{
    $empresa = Empresa::findOrFail($empresaId);

    // Seguridad: el contacto debe pertenecer a la empresa
    $contacto = $empresa->contactos()->where('id_contacto', $contactoId)->firstOrFail();

    $data = $request->validate([
        'nombre' => ['required','string','max:120'],
        'apellido' => ['nullable','string','max:120'],
        'telefono' => ['nullable','string','max:30'],
        'celular' => ['nullable','string','max:30'],
        'email' => ['nullable','string','max:190'],
        'direccion' => ['nullable','string','max:255'],
        'puesto' => ['nullable','string','max:120'],
        'departamento' => ['nullable','string','max:120'],
        'titulo' => ['nullable','string','max:120'],
        'notas' => ['nullable','string'],
        'activo' => ['nullable','boolean'],
    ]);

    $data['activo'] = $request->boolean('activo');

    $contacto->update($data);

        return redirect()
        ->route('empresas.edit', $empresaId)   // 👈 sin edit_contacto
        ->with('success', 'Contacto actualizado.')
        ->withFragment('contactos');          // 👈 te manda a #contactos
}

public function contactoDestroy($empresaId, $contactoId)
{
    $empresa = Empresa::findOrFail($empresaId);

    $contacto = $empresa->contactos()->where('id_contacto', $contactoId)->firstOrFail();
    $contacto->delete();

    return back()->with('success', 'Contacto eliminado.');
}


public function exportEmpresasExcel(Request $request)
{
    // Estos nombres deben coincidir con tus filtros del index
    $q      = trim((string) $request->get('q', ''));
    $idBase = $request->get('id_base_datos');
    $idTipo = $request->get('id_tipo_empresa');
    $activo = $request->get('activo'); // '', '1', '0'
    $pais   = trim((string) $request->get('pais', ''));
    $depto  = trim((string) $request->get('departamento', ''));
    $muni   = trim((string) $request->get('municipio', ''));

    $query = Empresa::query()
        ->with(['baseDeDatos', 'tipoEmpresa'])   // ajusta nombres si difieren
        ->withCount('contactos')
        ->orderBy('nombre');

    if ($q !== '') {
        $query->where(function ($w) use ($q) {
            $w->where('nombre', 'like', "%{$q}%")
              ->orWhere('descripcion', 'like', "%{$q}%")
              ->orWhere('sitio_web', 'like', "%{$q}%")
              ->orWhereHas('contactos', function($c) use ($q) {
                  $c->where('nombre', 'like', "%{$q}%")
                    ->orWhere('apellido', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('telefono', 'like', "%{$q}%")
                    ->orWhere('celular', 'like', "%{$q}%")
                    ->orWhere('puesto', 'like', "%{$q}%");
              });
        });
    }

    if ($idBase) $query->where('id_base_datos', $idBase);
    if ($idTipo) $query->where('id_tipo_empresa', $idTipo);

    if ($activo === '0' || $activo === '1') {
        $query->where('activo', (int) $activo);
    }

    if ($pais !== '')  $query->where('pais', 'like', "%{$pais}%");
    if ($depto !== '') $query->where('departamento', 'like', "%{$depto}%");
    if ($muni !== '')  $query->where('municipio', 'like', "%{$muni}%");

    $rows = $query->get();

    // Primera fila = encabezados
    $data = [[
        'Empresa', 'Tipo', 'Base de datos', 'País', 'Departamento', 'Municipio',
        'Sitio web', 'Gestor AAPOS', 'Activo', '# Contactos', 'Descripción'
    ]];

    foreach ($rows as $e) {
        $data[] = [

            $e->nombre,
            $e->tipoEmpresa->nombre ?? ($e->tipo_empresa->nombre ?? ''),
            $e->baseDeDatos->nombre ?? ($e->base_de_datos->nombre ?? ''),
            $e->pais,
            $e->departamento,
            $e->municipio,
            $e->sitio_web,
            $e->gestor_aapos,
            ($e->activo ? 'Sí' : 'No'),
            $e->contactos_count ?? 0,
            $e->descripcion,
        ];
    }

    $filename = 'empresas_' . Carbon::now()->format('Ymd_His') . '.xlsx';
    return Excel::download(new ArrayExport($data), $filename);
}

public function exportContactosExcel(Request $request, $empresaId)
{
    $empresa = Empresa::findOrFail($empresaId);

    $cq = trim((string) $request->get('cq', ''));

    $query = $empresa->contactos()->orderBy('apellido')->orderBy('nombre');

    if ($cq !== '') {
        $query->where(function($w) use ($cq) {
            $w->where('nombre', 'like', "%{$cq}%")
              ->orWhere('apellido', 'like', "%{$cq}%")
              ->orWhere('email', 'like', "%{$cq}%")
              ->orWhere('telefono', 'like', "%{$cq}%")
              ->orWhere('celular', 'like', "%{$cq}%")
              ->orWhere('puesto', 'like', "%{$cq}%")
              ->orWhere('departamento', 'like', "%{$cq}%")
              ->orWhere('titulo', 'like', "%{$cq}%");
        });
    }

    $rows = $query->get();

    $data = [[
         'Nombre', 'Apellido', 'Título', 'Puesto',
        'Email', 'Teléfono', 'Celular', 'Departamento', 'Dirección', 'Notas', 'Activo'
    ]];

    foreach ($rows as $c) {
        $data[] = [

            $c->nombre,
            $c->apellido,
            $c->titulo,
            $c->puesto,
            $c->email,
            $c->telefono,
            $c->celular,
            $c->departamento,
            $c->direccion,
            $c->notas,
            ($c->activo ? 'Sí' : 'No'),
        ];
    }

    $safeName = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $empresa->nombre ?? 'empresa');
    $filename = "contactos_{$safeName}_" . Carbon::now()->format('Ymd_His') . ".xlsx";

    return Excel::download(new ArrayExport($data), $filename);
}

}
