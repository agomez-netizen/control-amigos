<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario;
use App\Models\BaseDeDatos;

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
            ->withCount('basesDeDatos')
            ->orderBy('id_usuario', 'desc')
            ->paginate(10)
            ->appends(['q' => $q]);

        return view('usuarios.bases_index', compact('usuarios', 'q'));
    }

    public function edit($id)
    {
        $usuario = Usuario::with('basesDeDatos')->findOrFail($id);

        $bases = BaseDeDatos::query()
            ->orderBy('nombre')
            ->get(['id_base_datos', 'nombre', 'descripcion']);

        $asignadas = $usuario->basesDeDatos->pluck('id_base_datos')->toArray();

        return view('usuarios.bases_edit', compact('usuario', 'bases', 'asignadas'));
    }

    public function update(Request $request, $id)
    {
        $usuario = Usuario::findOrFail($id);

        $data = $request->validate([
            'bases'   => ['nullable','array'],
            'bases.*' => ['integer','exists:base_de_datos,id_base_datos'],
        ]);

        $usuario->basesDeDatos()->sync($data['bases'] ?? []);

        return redirect()
            ->route('usuarios.bases.edit', $usuario->id_usuario)
            ->with('ok', 'Bases de datos actualizadas ✅');
    }
}
