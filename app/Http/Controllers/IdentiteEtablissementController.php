<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Support\Etablissement\IdentiteDocument;
use App\Support\EtablissementContext;
use App\Support\Images\ImageControlee;
use Illuminate\Http\Request;

/**
 * Paramètres › Identité de la clinique : coordonnées imprimées sur les documents,
 * logos, signature et cachet. Réservé à l'administrateur de la clinique (setting.access).
 */
class IdentiteEtablissementController extends Controller
{
    private const IMAGES = ['logo', 'logo_documents', 'signature', 'cachet'];

    public function show()
    {
        $etablissement = EtablissementContext::current();
        abort_unless($etablissement, 404);

        return view('etablissements.identite', [
            'etablissement' => $etablissement,
            'identite' => IdentiteDocument::pour($etablissement),
            'fiches' => ImageControlee::FICHES,
        ]);
    }

    public function update(Request $request)
    {
        $etablissement = EtablissementContext::current();
        abort_unless($etablissement, 404);

        $donnees = $request->validate([
            'adresse' => ['nullable', 'string', 'max:255'],
            'contact' => ['nullable', 'string', 'max:60'],
            'email' => ['nullable', 'email', 'max:255'],
            'site_web' => ['nullable', 'url', 'max:255'],
            'numero_enregistrement' => ['nullable', 'string', 'max:120'],
            'message_facture' => ['nullable', 'string', 'max:300'],
            'signataire_nom' => ['nullable', 'string', 'max:120'],
            'signataire_fonction' => ['nullable', 'string', 'max:120'],
            // Le contrôle détaillé (type réel, poids, dimensions, proportions, transparence)
            // est fait par ImageControlee ; ici seulement « un fichier, 5 Mo au plus reçus ».
            'logo' => ['nullable', 'file', 'max:5120'],
            'logo_documents' => ['nullable', 'file', 'max:5120'],
            'signature' => ['nullable', 'file', 'max:5120'],
            'cachet' => ['nullable', 'file', 'max:5120'],
            'supprimer' => ['nullable', 'array'],
            'supprimer.*' => ['in:' . implode(',', self::IMAGES)],
        ], [
            'site_web.url' => 'Le site web doit commencer par https://',
            '*.max' => 'Fichier trop lourd : 5 Mo au plus avant contrôle.',
        ]);

        $etablissement->fill(collect($donnees)->only([
            'adresse', 'contact', 'email', 'site_web', 'numero_enregistrement', 'message_facture', 'signataire_nom', 'signataire_fonction',
        ])->all());

        $changees = [];
        $supprimer = $donnees['supprimer'] ?? [];
        foreach (self::IMAGES as $type) {
            if ($request->hasFile($type)) {
                $chemin = ImageControlee::enregistrer($request->file($type), $type, "identite/etablissements/{$etablissement->id}", $type, $etablissement->{$type});
                $etablissement->forceFill([$type => $chemin]);
                $changees[] = $type;
            } elseif (in_array($type, $supprimer, true) && $etablissement->{$type}) {
                ImageControlee::supprimer($type, $etablissement->{$type});
                $etablissement->forceFill([$type => null]);
                $changees[] = $type . ' (retiré)';
            }
        }
        $etablissement->save();

        if ($changees) {
            try {
                ActivityLog::create([
                    'etablissement_id' => $etablissement->id,
                    'causer_type' => \App\Models\User::class, 'causer_id' => auth()->id(),
                    'subject_type' => $etablissement::class, 'subject_id' => $etablissement->id,
                    'action' => 'etablissement.identite',
                    'description' => 'Identité visuelle modifiée : ' . implode(', ', $changees),
                    'ip_address' => $request->ip(),
                ]);
            } catch (\Throwable) {
            }
        }

        return back()->with('success', 'Identité de la clinique enregistrée.');
    }

    /** Aperçu protégé d'une image (les signatures et cachets n'ont pas d'adresse publique). */
    public function image(string $type)
    {
        abort_unless(in_array($type, self::IMAGES, true), 404);
        $etablissement = EtablissementContext::current();
        $chemin = $etablissement ? ImageControlee::cheminAbsolu($type, $etablissement->{$type}) : null;
        abort_unless($chemin, 404);

        return response()->file($chemin, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'inline',
        ]);
    }
}
