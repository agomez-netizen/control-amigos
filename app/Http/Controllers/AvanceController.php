<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\Avance;
use App\Models\Empresa;
use App\Models\AvanceHistorial;
use App\Models\Usuario;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ArrayExport;

class AvanceController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function sessionUserOrFail(): array
    {
        $u = session('user');
        $userId = $u['id_usuario'] ?? null;
        if (!$userId) abort(403, 'No hay usuario en sesión');
        return $u;
    }

    private function isAdmin(array $u): bool
    {
        $rolName = strtoupper(trim($u['rol'] ?? $u['nombre_rol'] ?? ''));
        $rolId   = (int)($u['id_rol'] ?? 0);
        return ($rolId === 1) || ($rolName === 'ADMIN');
    }

    private function canEditAvance(array $u, Avance $avance): bool
    {
        if ($this->isAdmin($u)) return true;
        $userId = (int)($u['id_usuario'] ?? 0);
        return (int)$avance->id_usuario === $userId;
    }

    private function plainText(?string $html): string
    {
        if (!$html) return '';
        $text = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = strip_tags($text);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace("/\r\n|\r|\n/", "\n", $text);
        return trim($text);
    }

    /**
     * Empresas visibles para el usuario (admin ve todas; usuario ve las asignadas)
     */
    private function empresasVisibles(int $userId, bool $isAdmin)
    {
        return $isAdmin
            ? Empresa::where('activo', 1)->orderBy('nombre')->get(['id_empresa', 'nombre', 'activo'])
            : Empresa::where('activo', 1)
                ->whereHas('usuarios', fn ($q) => $q->where('usuarios.id_usuario', $userId))
                ->orderBy('nombre')
                ->get(['id_empresa', 'nombre', 'activo']);
    }

    /**
     * Verifica que una empresa pertenezca al usuario (si no es admin)
     * Tabla pivote: empresa_usuario
     */
    private function assertEmpresaPermitida(bool $isAdmin, int $userId, ?int $idEmpresa): void
    {
        if ($isAdmin || !$idEmpresa) return;

        $ok = DB::table('empresa_usuario')
            ->where('id_usuario', $userId)
            ->where('id_empresa', $idEmpresa)
            ->exists();

        abort_unless($ok, 403, 'Empresa no permitida.');
    }

    /*
    |--------------------------------------------------------------------------
    | Create (pantalla registrar avance)
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        $u = $this->sessionUserOrFail();
        $userId  = (int)($u['id_usuario'] ?? 0);
        $isAdmin = $this->isAdmin($u);

        $empresas = $this->empresasVisibles($userId, $isAdmin);

        return view('avances.create', compact('empresas'));
    }

    /*
    |--------------------------------------------------------------------------
    | Store (crear avance + bitácora)  -> SIN HTML
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $u = $this->sessionUserOrFail();
        $userId  = (int)($u['id_usuario'] ?? 0);
        $isAdmin = $this->isAdmin($u);

        $data = $request->validate([
            'id_empresa'  => ['required', 'integer'],
            'descripcion' => ['required', 'string'],
            'fecha'       => ['nullable', 'date'],
        ]);

        $idEmpresa = (int)$data['id_empresa'];
        $this->assertEmpresaPermitida($isAdmin, $userId, $idEmpresa);

        $fecha = ($data['fecha'] ?? null)
            ? Carbon::parse($data['fecha'])->toDateString()
            : Carbon::now()->toDateString();

        $descPlano = $this->plainText($data['descripcion']);

        DB::transaction(function () use ($idEmpresa, $fecha, $userId, $descPlano) {

            $avance = Avance::create([
                'id_empresa'  => $idEmpresa,
                'descripcion' => $descPlano,
                'fecha'       => $fecha,
                'id_usuario'  => $userId, // ✅
            ]);

            // ✅ Tu tabla avance_historial exige user_id (NOT NULL)
            AvanceHistorial::create([
                'id_avance'      => (int)$avance->id_avance,
                'user_id'        => $userId, // ✅ requerido por tu tabla historial
                'id_usuario'     => $userId, // ✅ por compatibilidad si existe
                'campo'          => 'CREADO',
                'valor_anterior' => null,
                'valor_nuevo'    => 'Avance creado',
                'created_at'     => now(),
            ]);
        });

        return redirect()->back()->with('success', '✅ Avance creado correctamente.');
    }

    /*
    |--------------------------------------------------------------------------
    | Listado por fecha
    |--------------------------------------------------------------------------
    */
    public function byDate(Request $request)
    {
        $u = $this->sessionUserOrFail();
        $userId  = (int)($u['id_usuario'] ?? 0);
        $isAdmin = $this->isAdmin($u);

        $empresas = $this->empresasVisibles($userId, $isAdmin);

        $idEmpresa   = $request->input('id_empresa');
        $empresaTxt  = trim((string)$request->input('empresa'));
        $idUsuario   = $request->input('id_usuario');
        $desde       = $request->input('desde');
        $hasta       = $request->input('hasta');

        $idEmpresaInt = $idEmpresa ? (int)$idEmpresa : null;
        $idUsuarioInt = $idUsuario ? (int)$idUsuario : null;

        // ✅ No admin: si selecciona empresa por ID, debe ser suya
        $this->assertEmpresaPermitida($isAdmin, $userId, $idEmpresaInt);

        // ✅ Combo usuarios:
        // Admin: todos
        // No admin: solo él mismo (para no elegir a otros)
        $usuarios = $isAdmin
            ? Usuario::orderBy('nombre')->orderBy('apellido')->get()
            : Usuario::where('id_usuario', $userId)->get();

        $q = Avance::query()
            ->with(['empresa', 'usuario'])

            // ✅ REGLA PRINCIPAL:
            // - Admin ve todo
            // - No admin SOLO sus avances
            ->when(!$isAdmin, fn ($qq) => $qq->where('id_usuario', $userId))

            // filtros normales
            ->when($idEmpresaInt, fn ($qq) => $qq->where('id_empresa', $idEmpresaInt))
            ->when(!$idEmpresaInt && $empresaTxt !== '', function ($qq) use ($empresaTxt) {
                $qq->whereHas('empresa', fn ($e) => $e->where('nombre', 'like', "%{$empresaTxt}%"));
            })

            // ✅ Admin puede filtrar por usuario; no-admin IGNORA este filtro
            ->when($isAdmin && $idUsuarioInt, fn ($qq) => $qq->where('id_usuario', $idUsuarioInt))

            ->when($desde, fn ($qq) => $qq->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn ($qq) => $qq->whereDate('fecha', '<=', $hasta))
            ->orderBy('fecha', 'desc')
            ->orderBy('created_at', 'desc');

        $avances = $q->get();
        $grouped = $avances->groupBy(fn ($a) => Carbon::parse($a->fecha)->toDateString());

        $empresaSeleccionadaNombre = $idEmpresaInt
            ? (Empresa::where('id_empresa', $idEmpresaInt)->value('nombre') ?? '')
            : $empresaTxt;

        return view('avances.by_date', compact(
            'empresas',
            'usuarios',
            'grouped',
            'idEmpresa',
            'empresaTxt',
            'idUsuario',
            'empresaSeleccionadaNombre',
            'desde',
            'hasta'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Update (editar + bitácora) -> SIN HTML
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, $id)
    {
        $u = $this->sessionUserOrFail();
        $userId  = (int)($u['id_usuario'] ?? 0);
        $isAdmin = $this->isAdmin($u);

        $avance = Avance::with(['empresa'])->findOrFail($id);

        if (!$this->canEditAvance($u, $avance)) {
            abort(403, 'No tienes permiso para editar este avance.');
        }

        $data = $request->validate([
            'id_empresa'  => ['required', 'integer'],
            'descripcion' => ['required', 'string'],
            'fecha'       => ['nullable', 'date'],
        ]);

        $idEmpresaNueva = (int)$data['id_empresa'];

        // ✅ no admin: no puede mover el avance a una empresa que no tenga asignada
        $this->assertEmpresaPermitida($isAdmin, $userId, $idEmpresaNueva);

        $nuevaFecha = ($data['fecha'] ?? null)
            ? Carbon::parse($data['fecha'])->toDateString()
            : $avance->fecha;

        $oldDescPlain = $this->plainText($avance->descripcion);
        $newDescPlain = $this->plainText($data['descripcion']);

        $oldEmpresaNombre = $avance->empresa->nombre ?? ('ID ' . $avance->id_empresa);
        $newEmpresaNombre = Empresa::where('id_empresa', $idEmpresaNueva)->value('nombre')
            ?? ('ID ' . $idEmpresaNueva);

        $cambios = [];

        if ((int)$avance->id_empresa !== $idEmpresaNueva) {
            $cambios[] = ['campo' => 'empresa', 'old' => $oldEmpresaNombre, 'new' => $newEmpresaNombre];
        }
        if ($oldDescPlain !== $newDescPlain) {
            $cambios[] = ['campo' => 'descripcion', 'old' => $oldDescPlain, 'new' => $newDescPlain];
        }
        if ((string)$avance->fecha !== (string)$nuevaFecha) {
            $cambios[] = ['campo' => 'fecha', 'old' => (string)$avance->fecha, 'new' => (string)$nuevaFecha];
        }

        DB::transaction(function () use ($avance, $idEmpresaNueva, $nuevaFecha, $cambios, $userId, $newDescPlain) {

            $avance->update([
                'id_empresa'  => $idEmpresaNueva,
                'descripcion' => $newDescPlain,
                'fecha'       => $nuevaFecha,
            ]);

            foreach ($cambios as $c) {
                AvanceHistorial::create([
                    'id_avance'      => (int)$avance->id_avance,
                    'user_id'        => $userId,   // ✅ requerido por tu tabla historial
                    'id_usuario'     => $userId,   // ✅ por compatibilidad si existe
                    'campo'          => $c['campo'],
                    'valor_anterior' => $c['old'],
                    'valor_nuevo'    => $c['new'],
                    'created_at'     => now(),
                ]);
            }
        });

        return redirect()->back()->with(
            'success',
            $cambios ? '✅ Avance actualizado y registrado en bitácora.' : 'ℹ️ No hubo cambios.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Historial (HTML para modal) + empresas para SELECT
    |--------------------------------------------------------------------------
    */
    public function historial($id)
    {
        $u = $this->sessionUserOrFail();
        $userId  = (int)($u['id_usuario'] ?? 0);
        $isAdmin = $this->isAdmin($u);

        $avance = Avance::with(['empresa', 'usuario'])->findOrFail($id);

        if (!$this->canEditAvance($u, $avance)) {
            abort(403, 'No tienes permiso.');
        }

        // ✅ No admin: además la empresa debe ser asignada (tu regla actual)
        if (!$isAdmin) {
            $this->assertEmpresaPermitida(false, $userId, (int)$avance->id_empresa);
        }

        $historial = AvanceHistorial::with('usuario')
            ->where('id_avance', $id)
            ->orderByDesc('created_at')
            ->orderByDesc('id_historial')
            ->get();

        $empresas = $this->empresasVisibles($userId, $isAdmin);

        return view('avances.partials.historial', compact('avance', 'historial', 'empresas'));
    }

    /*
    |--------------------------------------------------------------------------
    | Upload Image (si lo usas en editor)
    |--------------------------------------------------------------------------
    */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'file' => ['required', 'image', 'max:5120'],
        ]);

        $path = $request->file('file')->store('avances', 'public');

        return response()->json([
            'location' => asset('storage/' . $path),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Dashboard de avances (REGLA: no-admin solo sus avances)
    |--------------------------------------------------------------------------
    */
    public function dashboard(Request $request)
    {
        $u = $this->sessionUserOrFail();
        $userId  = (int)($u['id_usuario'] ?? 0);
        $isAdmin = $this->isAdmin($u);

        $desde = $request->get('desde');
        $hasta = $request->get('hasta');

        $rows = DB::table('avances as a')
            ->join('empresa as e', 'e.id_empresa', '=', 'a.id_empresa')
            // ✅ No admin: solo sus avances
            ->when(!$isAdmin, fn ($q) => $q->where('a.id_usuario', $userId))
            ->when($desde, fn($q) => $q->whereDate('a.fecha', '>=', $desde))
            ->when($hasta, fn($q) => $q->whereDate('a.fecha', '<=', $hasta))
            ->select('e.nombre', DB::raw('COUNT(*) as total'))
            ->groupBy('e.nombre')
            ->orderByDesc('total')
            ->get();

        $labels = $rows->pluck('nombre')->values();
        $data   = $rows->pluck('total')->values();

        $totalAvances = (int)$rows->sum('total');
        $topEmpresa   = $rows->first();

        return view('avances.dashboard', compact(
            'labels',
            'data',
            'desde',
            'hasta',
            'totalAvances',
            'topEmpresa'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Export Excel (REGLA: no-admin solo sus avances)
    |--------------------------------------------------------------------------
    */
    public function exportExcel(Request $request)
    {
        $u = $this->sessionUserOrFail();
        $userId  = (int)($u['id_usuario'] ?? 0);
        $isAdmin = $this->isAdmin($u);

        $desde     = $request->input('desde');
        $hasta     = $request->input('hasta');
        $idEmpresa = $request->input('id_empresa');
        $idUsuario = $request->input('id_usuario');

        $idEmpresaInt = $idEmpresa ? (int)$idEmpresa : null;
        $idUsuarioInt = $idUsuario ? (int)$idUsuario : null;

        $this->assertEmpresaPermitida($isAdmin, $userId, $idEmpresaInt);

        $avances = Avance::with(['empresa', 'usuario'])
            // ✅ No admin: solo sus avances
            ->when(!$isAdmin, fn ($q) => $q->where('id_usuario', $userId))
            // filtros
            ->when($idEmpresaInt, fn($q) => $q->where('id_empresa', $idEmpresaInt))
            // ✅ Admin puede filtrar por usuario; no-admin ignora
            ->when($isAdmin && $idUsuarioInt, fn ($q) => $q->where('id_usuario', $idUsuarioInt))
            ->when($desde, fn($q) => $q->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn($q) => $q->whereDate('fecha', '<=', $hasta))
            ->orderBy('fecha', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $rows = [[
            'Fecha',
            'Empresa',
            'Descripción',
            'Nombre',
            'Apellido',
            'Hora',
        ]];

        foreach ($avances as $a) {
            $rows[] = [
                $a->fecha,
                $a->empresa->nombre ?? '—',
                $this->plainText($a->descripcion),
                $a->usuario->nombre ?? 'Usuario eliminado',
                $a->usuario->apellido ?? '',
                $a->created_at ? $a->created_at->format('H:i') : '',
            ];
        }

        return Excel::download(new \App\Exports\ArrayExport($rows), 'avances.xlsx');
    }

    /*
    |--------------------------------------------------------------------------
    | Export PDF (REGLA: no-admin solo sus avances)
    |--------------------------------------------------------------------------
    */
    public function exportPdf(Request $request)
    {
        $u = $this->sessionUserOrFail();
        $userId  = (int)($u['id_usuario'] ?? 0);
        $isAdmin = $this->isAdmin($u);

        $empresas = $this->empresasVisibles($userId, $isAdmin);

        $idEmpresa = $request->input('id_empresa');
        $idUsuario = $request->input('id_usuario');
        $desde     = $request->input('desde');
        $hasta     = $request->input('hasta');

        $idEmpresaInt = $idEmpresa ? (int)$idEmpresa : null;
        $idUsuarioInt = $idUsuario ? (int)$idUsuario : null;

        $this->assertEmpresaPermitida($isAdmin, $userId, $idEmpresaInt);

        $avances = Avance::query()
            ->with(['empresa', 'usuario'])
            // ✅ No admin: solo sus avances
            ->when(!$isAdmin, fn ($q) => $q->where('id_usuario', $userId))
            // filtros
            ->when($idEmpresaInt, fn ($qq) => $qq->where('id_empresa', $idEmpresaInt))
            // ✅ Admin puede filtrar por usuario; no-admin ignora
            ->when($isAdmin && $idUsuarioInt, fn ($qq) => $qq->where('id_usuario', $idUsuarioInt))
            ->when($desde, fn ($qq) => $qq->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn ($qq) => $qq->whereDate('fecha', '<=', $hasta))
            ->orderBy('fecha', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $grouped = $avances->groupBy(fn($a) => Carbon::parse($a->fecha)->toDateString());

        $pdf = Pdf::loadView('avances.pdf_by_date', compact(
            'empresas', 'grouped', 'idEmpresa', 'desde', 'hasta'
        ))->setPaper('a4', 'portrait');

        return $pdf->download('avances.pdf');
    }

    public function exportByDate(Request $request)
{
    $query = Avance::with(['empresa', 'usuario']);

    // Filtros opcionales
    if ($request->filled('id_empresa')) {
        $query->where('id_empresa', $request->id_empresa);
    }

    if ($request->filled('id_usuario')) {
        $query->where('id_usuario', $request->id_usuario);
    }

    if ($request->filled('desde')) {
        $query->whereDate('fecha', '>=', $request->desde);
    }

    if ($request->filled('hasta')) {
        $query->whereDate('fecha', '<=', $request->hasta);
    }

    $avances = $query->orderBy('fecha', 'desc')->get();

    // Construcción del array para Excel
    $data = [];

    // Encabezados
    $data[] = [
        'Fecha',
        'Empresa',
        'Usuario',
        'Descripción'
    ];

    foreach ($avances as $avance) {
        $data[] = [
            $avance->fecha,
            $avance->empresa->nombre ?? '',
            ($avance->usuario->nombre ?? '') . ' ' . ($avance->usuario->apellido ?? ''),
            strip_tags($avance->descripcion)
        ];
    }

    return Excel::download(
        new ArrayExport($data),
        'avances.xlsx'
    );
}
}
