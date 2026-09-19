<?php

namespace App\Http\Controllers;

use App\Models\Etablissement;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EtablissementController extends Controller
{
    public function getIndex()
    {
        $etablissements = Etablissement::withCount('utilisateurs', 'patients')->latest()->get();

        return view('etablissements.index', compact('etablissements'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:clinique,laboratoire,pharmacie,cabinet'],
            'statut' => ['required', 'in:essai,actif,suspendu,resilie'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'contact' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            // Nom d'expéditeur SMS enregistré chez Nimba pour cette clinique.
            'sms_expediteur' => ['nullable', 'string', 'max:11', 'regex:/^[A-Za-z0-9 \-]+$/'],
        ], [
            'sms_expediteur.max' => 'Le nom d\'expéditeur SMS fait 11 caractères au plus.',
            'sms_expediteur.regex' => 'Le nom d\'expéditeur SMS ne peut contenir que des lettres sans accent, des chiffres, des espaces et des tirets.',
        ]);

        $data['slug'] = $this->slugUnique($data['nom']);

        Etablissement::create($data);

        return back()->with('success', 'Établissement créé avec succès.');
    }

    public function update(Request $request, Etablissement $etablissement)
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:clinique,laboratoire,pharmacie,cabinet'],
            'statut' => ['required', 'in:essai,actif,suspendu,resilie'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'contact' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            // Nom d'expéditeur SMS enregistré chez Nimba pour cette clinique.
            'sms_expediteur' => ['nullable', 'string', 'max:11', 'regex:/^[A-Za-z0-9 \-]+$/'],
        ], [
            'sms_expediteur.max' => 'Le nom d\'expéditeur SMS fait 11 caractères au plus.',
            'sms_expediteur.regex' => 'Le nom d\'expéditeur SMS ne peut contenir que des lettres sans accent, des chiffres, des espaces et des tirets.',
        ]);

        $etablissement->update($data);

        return back()->with('success', 'Établissement mis à jour.');
    }

    public function delete(Etablissement $etablissement)
    {
        if ($etablissement->utilisateurs()->exists() || $etablissement->patients()->exists()) {
            return back()->with('error', "Impossible de supprimer : cet établissement a déjà des utilisateurs ou des patients rattachés. Suspendez-le plutôt (statut) que de le supprimer.");
        }

        $etablissement->delete();

        return back()->with('success', 'Établissement supprimé.');
    }

    /**
     * Écran de gestion de la licence : quels modules cet établissement
     * a le droit d'utiliser. Séparé du CRUD principal — activer/désactiver
     * un module n'est pas une édition de champ, c'est une décision
     * commerciale distincte qui mérite son propre écran et sa propre
     * permission (`etablissement.licence`).
     */
    public function modules(Etablissement $etablissement)
    {
        $modules = \App\Models\Module::all();
        $actifs = $etablissement->modules()->wherePivot('est_actif', true)->pluck('modules.id')->toArray();

        return view('etablissements.modules', compact('etablissement', 'modules', 'actifs'));
    }

    public function syncModules(Request $request, Etablissement $etablissement)
    {
        $request->validate(['modules' => ['array']]);
        $moduleIds = $request->input('modules', []);

        $sync = [];
        foreach (\App\Models\Module::pluck('id') as $id) {
            $estActif = in_array($id, $moduleIds);
            $sync[$id] = [
                'est_actif' => $estActif,
                'active_depuis' => $estActif ? now() : null,
                'desactive_le' => $estActif ? null : now(),
            ];
        }

        $etablissement->modules()->sync($sync);

        return back()->with('success', 'Licence mise à jour.');
    }

    protected function slugUnique(string $nom): string
    {
        $base = Str::slug($nom);
        $slug = $base;
        $i = 1;

        while (Etablissement::where('slug', $slug)->exists()) {
            $slug = "{$base}-" . $i++;
        }

        return $slug;
    }
}
