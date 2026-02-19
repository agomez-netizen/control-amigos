<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\BaseDeDatos;
use App\Models\TipoEmpresa;
use App\Models\Contacto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

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

        $empresas = $empresasQuery->orderBy('nombre')->paginate(20)->withQueryString();

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

    public function show($id)
    {
        $empresa = Empresa::with(['baseDeDatos','tipoEmpresa','contactos'])->findOrFail($id);
        return view('empresas.show', compact('empresa'));
    }

    public function edit($id)
    {
        $empresa = Empresa::with('contactos')->findOrFail($id);
        $bases = BaseDeDatos::orderBy('nombre')->get();
        $tipos = TipoEmpresa::orderBy('nombre')->get();

        return view('empresas.edit', compact('empresa','bases','tipos'));
    }

    public function update(Request $request, $id)
    {
        $empresa = Empresa::with('contactos')->findOrFail($id);

        $data = $request->validate([
            // Empresa
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

            // Contactos
            'contactos' => ['nullable','array'],
            'contactos.*.id_contacto' => ['nullable','integer','exists:contactos,id_contacto'],
            'contactos.*.nombre' => ['nullable','string','max:120'],
            'contactos.*.apellido' => ['nullable','string','max:120'],
            'contactos.*.telefono' => ['nullable','string','max:30'],
            'contactos.*.celular' => ['nullable','string','max:30'],
            'contactos.*.email' => ['nullable','string','max:190'],
            'contactos.*.direccion' => ['nullable','string','max:255'],
            'contactos.*.puesto' => ['nullable','string','max:120'],
            'contactos.*.departamento' => ['nullable','string','max:120'],
            'contactos.*.titulo' => ['nullable','string','max:120'],
            'contactos.*.notas' => ['nullable','string'],
            'contactos.*.activo' => ['nullable','boolean'],
        ]);

        $data['activo'] = $request->boolean('activo');

        DB::transaction(function () use ($request, $empresa, $data) {
            $empresaData = collect($data)->except(['contactos'])->toArray();
            $empresa->update($empresaData);

            $incoming = (array) $request->input('contactos', []);
            $keepIds = [];

            foreach ($incoming as $c) {
                $idContacto = $c['id_contacto'] ?? null;

                $nombre = trim((string)($c['nombre'] ?? ''));
                $apellido = trim((string)($c['apellido'] ?? ''));
                $email = trim((string)($c['email'] ?? ''));

                if ($nombre === '' && $apellido === '' && $email === '') {
                    continue;
                }

                $payload = [
                    'nombre' => $nombre !== '' ? $nombre : 'Contacto',
                    'apellido' => $apellido !== '' ? $apellido : null,
                    'telefono' => trim((string)($c['telefono'] ?? '')) ?: null,
                    'celular'  => trim((string)($c['celular'] ?? '')) ?: null,
                    'email'    => $email !== '' ? $email : null,
                    'direccion'=> trim((string)($c['direccion'] ?? '')) ?: null,
                    'puesto'   => trim((string)($c['puesto'] ?? '')) ?: null,
                    'departamento' => trim((string)($c['departamento'] ?? '')) ?: null,
                    'titulo'   => trim((string)($c['titulo'] ?? '')) ?: null,
                    'notas'    => trim((string)($c['notas'] ?? '')) ?: null,
                    'activo'   => isset($c['activo']) ? (int) !!$c['activo'] : 1,
                ];

                if ($idContacto) {
                    // Seguridad: solo actualizar si el contacto pertenece a esta empresa
                    $contacto = $empresa->contactos->firstWhere('id_contacto', (int)$idContacto);
                    if ($contacto) {
                        $contacto->update($payload);
                        $keepIds[] = (int)$contacto->id_contacto;
                    }
                } else {
                    $new = $empresa->contactos()->create($payload);
                    $keepIds[] = (int)$new->id_contacto;
                }
            }

            // Eliminar contactos removidos del form
            $empresa->contactos()
                ->whereNotIn('id_contacto', $keepIds)
                ->delete();
        });

        return redirect()->route('empresas.index')->with('success', 'Empresa actualizada correctamente.');
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
}
