<?php

namespace App\Http\Controllers\Catalogue;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Services\Catalogue\ImportCatalogueService;
use App\Support\Catalogue\LecteurTableur;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Lot S3 — Import du catalogue depuis Excel / CSV, en deux temps :
 * 1. analyser : le fichier est lu et chaque ligne classée, RIEN n'est enregistré ;
 * 2. confirmer : l'aperçu validé par l'utilisateur est enregistré d'un bloc.
 * L'aperçu est gardé en session entre les deux étapes.
 */
class ImportCatalogueController extends Controller
{
    private const SESSION = 'import_catalogue';

    /** Droit de création requis par type, et écran de liste où revenir. */
    private const TYPES = [
        'actes' => ['service.create', 'service.index'],
        'examens' => ['test.create', 'test.index'],
        'medicaments' => ['medicament.create', 'medicaments.index'],
    ];

    public function index(Request $request)
    {
        $types = $this->typesPermis();
        abort_if(empty($types), 403);

        $analyse = session(self::SESSION);
        if ($analyse && ! in_array($analyse['type'], $types, true)) {
            $analyse = null;
        }

        return view('catalogue.import', [
            'types' => $types,
            'typeChoisi' => in_array($request->query('type'), $types, true) ? $request->query('type') : ($analyse['type'] ?? $types[0]),
            'analyse' => $analyse,
            'departments' => Department::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function modele(string $type, ImportCatalogueService $service)
    {
        $this->autoriser($type);

        return response($service->modeleCsv($type), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="modele-import-' . $type . '.csv"',
        ]);
    }

    public function analyser(Request $request, LecteurTableur $lecteur, ImportCatalogueService $service)
    {
        $donnees = $request->validate([
            'type' => ['required', 'in:' . implode(',', array_keys(self::TYPES))],
            'fichier' => ['required', 'file', 'max:5120'],
            'departement_defaut' => ['nullable', 'exists_etablissement:departments,id'],
            'famille_defaut' => ['nullable', 'string', 'max:30'],
            'type_defaut' => ['nullable', 'in:' . implode(',', ImportCatalogueService::TYPES_EXAMEN)],
            'mettre_a_jour_prix' => ['nullable', 'boolean'],
        ], [
            'fichier.required' => 'Choisissez un fichier Excel (.xlsx) ou CSV.',
            'fichier.max' => 'Fichier trop lourd (5 Mo au plus).',
        ]);
        $this->autoriser($donnees['type']);

        try {
            $fichier = $request->file('fichier');
            $lignes = $lecteur->lire($fichier->getRealPath(), $fichier->getClientOriginalName());
        } catch (RuntimeException $e) {
            return back()->withErrors(['fichier' => $e->getMessage()])->withInput();
        }

        if (count($lignes) < 1) {
            return back()->withErrors(['fichier' => 'Le fichier est vide.'])->withInput();
        }

        $analyse = $service->analyser($donnees['type'], $lignes, [
            'departement_defaut' => $donnees['departement_defaut'] ?? null,
            'famille_defaut' => $donnees['famille_defaut'] ?? null,
            'type_defaut' => $donnees['type_defaut'] ?? null,
            'mettre_a_jour_prix' => $request->boolean('mettre_a_jour_prix'),
        ]);
        $analyse['fichier'] = $fichier->getClientOriginalName();

        session([self::SESSION => $analyse]);

        return redirect()->route('catalogue.import', ['type' => $donnees['type']]);
    }

    public function confirmer(ImportCatalogueService $service)
    {
        $analyse = session(self::SESSION);
        if (! $analyse) {
            return redirect()->route('catalogue.import')->with('error', 'Aperçu expiré : envoyez à nouveau le fichier.');
        }
        $this->autoriser($analyse['type']);

        $bilan = $service->importer($analyse);
        session()->forget(self::SESSION);

        $message = collect([
            $bilan['crees'] ? $bilan['crees'] . ' ' . ImportCatalogueService::LIBELLES[$analyse['type']] . ' ajouté(s)' : null,
            $bilan['mis_a_jour'] ? $bilan['mis_a_jour'] . ' prix mis à jour' : null,
            $bilan['departements_crees'] ? $bilan['departements_crees'] . ' département(s) créé(s)' : null,
        ])->filter()->implode(', ') ?: 'Rien à importer.';

        return redirect()->route(self::TYPES[$analyse['type']][1])->with('success', 'Import terminé : ' . $message . '.');
    }

    public function annuler()
    {
        $type = session(self::SESSION)['type'] ?? null;
        session()->forget(self::SESSION);

        return redirect()->route('catalogue.import', array_filter(['type' => $type]));
    }

    private function typesPermis(): array
    {
        return array_keys(array_filter(self::TYPES, fn ($t) => auth()->user()?->can($t[0])));
    }

    private function autoriser(string $type): void
    {
        abort_unless(isset(self::TYPES[$type]) && auth()->user()?->can(self::TYPES[$type][0]), 403);
    }
}
