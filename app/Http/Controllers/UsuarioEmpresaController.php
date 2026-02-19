<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario;
use App\Models\Empresa;

class UsuarioEmpresaController extends Controller
{
    public function index(Request $request)
    {
        $q = trim($request->get('q', ''));

        $usuarios = Usuario::query()
            ->when($q, function ($query) use ($q) {
                $query->where('nombre', 'like', "%$q%")
                      ->orWhere('apellido', 'like', "%$q%")
                      ->orWhere('usuario', 'like', "%$q%");
            })
            ->withCount('empresas')
            ->orderBy('id_usuario', 'desc')
            ->paginate(10)
            ->appends(['q' => $q]);

        return view('usuarios.empresas_index', compact('usuarios', 'q'));
    }

    public function edit($id)
    {
        $usuario = Usuario::with('empresas')->findOrFail($id);

        $empresas = Empresa::query()
            ->orderBy('nombre')
            ->get(['id_empresa', 'nombre', 'activo']);

        $asignadas = $usuario->empresas->pluck('id_empresa')->toArray();

        return view('usuarios.empresas_edit', compact('usuario', 'empresas', 'asignadas'));
    }

    public function update(Request $request, $id)
    {
        $usuario = Usuario::findOrFail($id);

        $data = $request->validate([
            'empresas'   => ['nullable','array'],
            'empresas.*' => ['integer','exists:empresa,id_empresa'],
        ]);

        // sync = deja EXACTAMENTE esas empresas asignadas (agrega/quita)
        $usuario->empresas()->sync($data['empresas'] ?? []);

        return redirect()
            ->route('usuarios.empresas.edit', $usuario->id_usuario)
            ->with('ok', 'Empresas actualizadas ✅');
    }
}
