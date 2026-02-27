<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\Avance;
use App\Models\Empresa;
use App\Models\Usuario;
use App\Models\AvanceHistorial;

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
     * Empresas visibles por BASES asignadas
     */
    private function empresasVisibles(int $userId, bool $isAdmin)
    {
        return $isAdmin
            ? Empresa::where('activo', 1)->orderBy('nombre')->get(['id_empresa', 'nombre', 'activo'])
            : Empresa::where('activo', 1)
                ->whereIn('id_base_datos', function ($q) use ($userId) {
                    $q->select('id_base_datos')
                      ->from('usuario_base_datos')
                      ->where('id_usuario', $userId);
                })
                ->orderBy('nombre')
                ->get(['id_empresa', 'nombre', 'activo']);
    }

    /**
     * Permiso de empresa por BASES asignadas
     */
    private function assertEmpresaPermitida(bool $isAdmin, int $userId, ?int $idEmpresa): void
    {
        if ($isAdmin || !$idEmpresa) return;

        $ok = DB::table('empresa as e')
            ->join('usuario_base_datos as ubd', 'ubd.id_base_datos', '=', 'e.id_base_datos')
            ->where('ubd.id_usuario', $userId)
            ->where('e.id_empresa', $idEmpresa)
            ->exists();

        abort_unless($ok, 403, 'Empresa no permitida.');
    }

    /**
     * Contactos visibles por BASES asignadas (admin ve todo)
     */
    private function contactosVisibles(int $userId, bool $isAdmin)
    {
        if ($isAdmin) {
            return DB::table('contactos as c')
                ->join('empresa as e', 'e.id_empresa', '=', 'c.id_empresa')
                ->where('c.activo', 1)
                ->orderBy('e.nombre')->orderBy('c.nombre')->orderBy('c.apellido')
                ->select([
                    'c.id_contacto',
                    'c.nombre as contacto_nombre',
                    'c.apellido as contacto_apellido',
                    'c.puesto',
                    'c.telefono',
                    'c.celular',
                    'c.email',
                    'e.id_empresa',
                    'e.nombre as empresa_nombre',
                ])
                ->get();
        }

        $baseIds = DB::table('usuario_base_datos')
            ->where('id_usuario', $userId)
            ->pluck('id_base_datos');

        return DB::table('contactos as c')
            ->join('empresa as e', 'e.id_empresa', '=', 'c.id_empresa')
            ->whereIn('e.id_base_datos', $baseIds)
            ->where('c.activo', 1)
            ->orderBy('e.nombre')->orderBy('c.nombre')->orderBy('c.apellido')
            ->select([
                'c.id_contacto',
                'c.nombre as contacto_nombre',
                'c.apellido as contacto_apellido',
                'c.puesto',
                'c.telefono',
                'c.celular',
                'c.email',
                'e.id_empresa',
                'e.nombre as empresa_nombre',
            ])
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        $u = $this->sessionUserOrFail();
        $userId = (int)($u['id_usuario'] ?? 0);
        $isAdmin = $this->isAdmin($u);

        $contactos = $this->contactosVisibles($userId, $isAdmin);

        return view('avances.create', compact('contactos'));
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $u = $this->sessionUserOrFail();
        $userId = (int)($u['id_usuario'] ?? 0);
        $isAdmin = $this->isAdmin($u);

        $data = $request->validate([
            'id_contacto' => ['required', 'integer', 'exists:contactos,id_contacto'],
            'descripcion' => ['nullable', 'string'],
        ]);

        // Verifica que el contacto sea permitido para este usuario (si no es admin)
        if (!$isAdmin) {
            $allowed = DB::table('contactos as c')
                ->join('empresa as e', 'e.id_empresa', '=', 'c.id_empresa')
                ->join('usuario_base_datos as ubd', 'ubd.id_base_datos', '=', 'e.id_base_datos')
                ->where('ubd.id_usuario', $userId)
                ->where('c.id_contacto', (int)$data['id_contacto'])
                ->exists();

            abort_unless($allowed, 403, 'Contacto no permitido.');
        }

        $contacto = DB::table('contactos')->where('id_contacto', (int)$data['id_contacto'])->first();
        abort_unless($contacto, 403);

        DB::table('avances')->insert([
            'id_empresa'   => (int)$contacto->id_empresa,
            'id_contacto'  => (int)$contacto->id_contacto,
            'id_usuario'   => $userId,
            // guardamos HTML si viene de TinyMCE; lo mostramos sin HTML en el listado
            'descripcion'  => $data['descripcion'] ?? null,
            'fecha'        => now()->toDateString(),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return redirect()->back()->with('success', 'Avance registrado ✅');
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

        $contactos = $this->contactosVisibles($userId, $isAdmin);

        $idContacto = $request->input('id_contacto');
        $idUsuario  = $request->input('id_usuario');
        $desde      = $request->input('desde');
        $hasta      = $request->input('hasta');

        $idContactoInt = $idContacto ? (int)$idContacto : null;
        $idUsuarioInt  = $idUsuario ? (int)$idUsuario : null;

        // Usuarios: admin todos / no-admin solo él
        $usuarios = $isAdmin
            ? Usuario::orderBy('nombre')->orderBy('apellido')->get()
            : Usuario::where('id_usuario', $userId)->get();

        // Seguridad: no-admin no puede filtrar un contacto fuera de sus bases
        if (!$isAdmin && $idContactoInt) {
            $allowed = DB::table('contactos as c')
                ->join('empresa as e', 'e.id_empresa', '=', 'c.id_empresa')
                ->join('usuario_base_datos as ubd', 'ubd.id_base_datos', '=', 'e.id_base_datos')
                ->where('ubd.id_usuario', $userId)
                ->where('c.id_contacto', $idContactoInt)
                ->exists();

            abort_unless($allowed, 403, 'Contacto no permitido.');
        }

        $q = Avance::query()
            ->with(['empresa', 'usuario', 'contacto'])
            // Regla principal: no-admin solo ve sus avances
            ->when(!$isAdmin, fn($qq) => $qq->where('id_usuario', $userId))
            ->when($idContactoInt, fn($qq) => $qq->where('id_contacto', $idContactoInt))
            ->when($isAdmin && $idUsuarioInt, fn($qq) => $qq->where('id_usuario', $idUsuarioInt))
            ->when($desde, fn($qq) => $qq->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn($qq) => $qq->whereDate('fecha', '<=', $hasta))
            ->orderBy('fecha', 'desc')
            ->orderBy('created_at', 'desc');

        $avances = $q->get();
        $grouped = $avances->groupBy(fn ($a) => Carbon::parse($a->fecha)->toDateString());

        return view('avances.by_date', compact(
            'contactos',
            'usuarios',
            'grouped',
            'idContacto',
            'idUsuario',
            'desde',
            'hasta'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Update (editar + bitácora)
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, $id)
    {
        $u = $this->sessionUserOrFail();
        $userId  = (int)($u['id_usuario'] ?? 0);
        $isAdmin = $this->isAdmin($u);

        $avance = Avance::with(['empresa', 'usuario', 'contacto'])->findOrFail($id);

        if (!$this->canEditAvance($u, $avance)) {
            abort(403, 'No tienes permiso para editar este avance.');
        }

        $data = $request->validate([
            'id_empresa'  => ['required', 'integer'],
            'descripcion' => ['required', 'string'],
            'fecha'       => ['nullable', 'date'],
        ]);

        $idEmpresaNueva = (int)$data['id_empresa'];

        // no-admin: no puede mover a empresa fuera de sus bases
        $this->assertEmpresaPermitida($isAdmin, $userId, $idEmpresaNueva);

        $nuevaFecha = ($data['fecha'] ?? null)
            ? Carbon::parse($data['fecha'])->toDateString()
            : $avance->fecha;

        // guardamos texto plano (para no almacenar html en ediciones)
        $newDescPlain = $this->plainText($data['descripcion']);
        $oldDescPlain = $this->plainText($avance->descripcion);

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
                    'user_id'        => $userId,
                    'id_usuario'     => $userId,
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
    | Historial (HTML para modal)
    |--------------------------------------------------------------------------
    */

    public function historial($id)
    {
        $u = $this->sessionUserOrFail();
        $userId  = (int)($u['id_usuario'] ?? 0);
        $isAdmin = $this->isAdmin($u);

        $avance = Avance::with(['empresa', 'usuario', 'contacto'])->findOrFail($id);

        if (!$this->canEditAvance($u, $avance)) {
            abort(403, 'No tienes permiso.');
        }

        // no-admin: empresa debe ser permitida por base
        $this->assertEmpresaPermitida($isAdmin, $userId, (int)$avance->id_empresa);

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
    | Dashboard
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
    | Export Excel
    |--------------------------------------------------------------------------
    */

    public function exportExcel(Request $request)
    {
        $u = $this->sessionUserOrFail();
        $userId  = (int)($u['id_usuario'] ?? 0);
        $isAdmin = $this->isAdmin($u);

        $desde      = $request->input('desde');
        $hasta      = $request->input('hasta');
        $idUsuario  = $request->input('id_usuario');
        $idContacto = $request->input('id_contacto');

        $idUsuarioInt  = $idUsuario ? (int)$idUsuario : null;
        $idContactoInt = $idContacto ? (int)$idContacto : null;

        $avances = Avance::with(['empresa', 'usuario', 'contacto'])
            ->when(!$isAdmin, fn ($q) => $q->where('id_usuario', $userId))
            ->when($idContactoInt, fn($q) => $q->where('id_contacto', $idContactoInt))
            ->when($isAdmin && $idUsuarioInt, fn ($q) => $q->where('id_usuario', $idUsuarioInt))
            ->when($desde, fn($q) => $q->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn($q) => $q->whereDate('fecha', '<=', $hasta))
            ->orderBy('fecha', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $rows = [[
            'Fecha',
            'Empresa',
            'Contacto',
            'Usuario',
            'Descripción',
            'Hora',
        ]];

        foreach ($avances as $a) {
            $contacto = trim(($a->contacto->nombre ?? '') . ' ' . ($a->contacto->apellido ?? ''));
            if (!empty($a->contacto->puesto)) $contacto .= ' — ' . $a->contacto->puesto;

            $rows[] = [
                $a->fecha,
                $a->empresa->nombre ?? '—',
                $contacto ?: '—',
                trim(($a->usuario->nombre ?? 'Usuario eliminado') . ' ' . ($a->usuario->apellido ?? '')),
                $this->plainText($a->descripcion),
                $a->created_at ? $a->created_at->format('H:i') : '',
            ];
        }

        return Excel::download(new ArrayExport($rows), 'avances.xlsx');
    }
}
