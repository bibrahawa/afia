<?php

namespace App\Http\Controllers;

use App\Models\Module;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    public function getIndex()
    {
        $modules = Module::withCount('etablissements')->get();

        return view('modules.index', compact('modules'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:modules,code', 'alpha_dash'],
            'nom' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        Module::create($data);

        return back()->with('success', 'Module créé.');
    }

    public function update(Request $request, Module $module)
    {
        // Le code n'est volontairement PAS modifiable après création : il
        // est utilisé en dur dans le middleware `module:xxx` et les vues.
        // Le changer casserait silencieusement des vérifications d'accès
        // existantes.
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $module->update($data);

        return back()->with('success', 'Module mis à jour.');
    }

    public function delete(Module $module)
    {
        if ($module->etablissements()->exists()) {
            return back()->with('error', 'Impossible de supprimer : ce module est utilisé par au moins un établissement.');
        }

        $module->delete();

        return back()->with('success', 'Module supprimé.');
    }
}
