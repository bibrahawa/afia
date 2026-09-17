<?php

namespace App\Services\Labo;

use App\Models\User;
use App\Enums\Labo\StatutExamen;
use App\Exceptions\Labo\OperationLaboImpossible;
use App\Jobs\Labo\EnvoyerSmsResultatsJob;
use App\Jobs\Labo\GenererPdfCompteRenduJob;
use App\Models\Labo\LaboCompteRendu;
use App\Models\Labo\LaboDemande;
use App\Models\Labo\LaboDemandeExamen;
use App\Support\Labo\ContexteLabo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CompteRenduService
{
    public function __construct(private DemandeService $demandes, private NotificationReseauService $notifications)
    {
    }

    /**
     * Publie les examens validés par le biologiste et émet une NOUVELLE
     * version du compte rendu (cumulative : elle reprend tout ce qui a déjà
     * été publié). Les versions précédentes restent consultables.
     */
    public function publier(LaboDemande $demande, User $auteur, bool $notifierPatient = true): LaboCompteRendu
    {
        ContexteLabo::verifierAppartenance($demande);

        $compteRendu = DB::transaction(function () use ($demande, $auteur) {
            // Verrou sur la demande : deux clics simultanés sur « Publier »
            // ne doivent pas produire deux versions portant le même numéro.
            $demande = LaboDemande::whereKey($demande->id)->lockForUpdate()->firstOrFail();

            $aPublier = $demande->examens()->where('statut', StatutExamen::VALIDE_BIOLOGIQUE->value)->get();
            if ($aPublier->isEmpty()) {
                throw new OperationLaboImpossible('Aucun examen validé par le biologiste en attente de publication.');
            }

            foreach ($aPublier as $ligne) {
                $ligne->update(['statut' => StatutExamen::PUBLIE, 'publie_le' => now()]);
            }

            $precedent = $demande->comptesRendus()->first();
            $contenu = $this->construireContenu($demande->fresh(), $precedent);

            $cr = LaboCompteRendu::create([
                'etablissement_id' => $demande->etablissement_id,
                'demande_id' => $demande->id,
                'version' => ($precedent?->version ?? 0) + 1,
                'est_partiel' => $contenu['est_partiel'],
                'est_rectificatif' => $contenu['est_rectificatif'],
                'motif_rectification' => $contenu['motif_rectification'],
                'contenu' => $contenu,
                'empreinte' => hash('sha256', json_encode($contenu, JSON_UNESCAPED_UNICODE)),
                'publie_par' => $auteur->id,
                'publie_le' => now(),
            ]);

            if (! $demande->premiere_publication_le) {
                $demande->update(['premiere_publication_le' => now()]);
            }

            $this->demandes->rafraichirStatut($demande);
            ContexteLabo::journaliser('compte_rendu_publie', $cr, "Version {$cr->version}", ['rectificatif' => $cr->est_rectificatif]);

            return $cr;
        });

        // Hors transaction : le worker ne doit jamais voir un CR non encore commité.
        GenererPdfCompteRenduJob::dispatch($compteRendu->id);

        if ($notifierPatient && $demande->fresh()->peutEtreRemisAuPatient()) {
            EnvoyerSmsResultatsJob::dispatch($compteRendu->id);
        }

        // Réseau (lot 4c) : la clinique qui a prescrit est prévenue à la première publication.
        $this->notifications->notifierPublication($demande->fresh('partenariat'));

        return $compteRendu;
    }

    /** Contenu du PDF, calculé à la volée si le fichier n'est pas (encore) généré. */
    public function pdf(LaboCompteRendu $compteRendu): string
    {
        if ($compteRendu->pdf_path && Storage::disk('local')->exists($compteRendu->pdf_path)) {
            return Storage::disk('local')->get($compteRendu->pdf_path);
        }

        return $this->genererPdf($compteRendu);
    }

    public function genererPdf(LaboCompteRendu $compteRendu): string
    {
        $binaire = Pdf::loadView('labo.comptes-rendus.pdf', [
            'cr' => $compteRendu,
            'c' => $compteRendu->contenu,
        ])->setPaper('a4')->output();

        $chemin = sprintf('laboratoire/%d/comptes-rendus/%s-v%d.pdf',
            $compteRendu->etablissement_id,
            $compteRendu->contenu['demande']['numero'],
            $compteRendu->version
        );

        Storage::disk('local')->put($chemin, $binaire);
        $compteRendu->update(['pdf_path' => $chemin]);

        return $binaire;
    }

    /**
     * Instantané COMPLET et autonome : le PDF se construit uniquement à
     * partir de ce tableau, sans relire le catalogue ni les tables vivantes.
     */
    public function construireContenu(LaboDemande $demande, ?LaboCompteRendu $precedent = null): array
    {
        $demande->load([
            'patient', 'prescripteur', 'etablissement',
            'examens' => fn ($q) => $q->where('statut', StatutExamen::PUBLIE->value),
            'examens.examen.section', 'examens.examen.parametres',
            'examens.resultats', 'examens.germesIsoles.antibiogramme', 'examens.validateurBiologique',
        ]);

        $toutes = $demande->examens()->where('statut', '!=', StatutExamen::ANNULE->value)->count();
        $publiees = $demande->examens->count();

        $rectificationsPrecedentes = collect($precedent?->contenu['sections'] ?? [])
            ->flatMap(fn ($s) => $s['examens'])
            ->pluck('rectification', 'code');

        $motifs = [];
        $sections = $demande->examens
            ->sortBy(fn (LaboDemandeExamen $l) => sprintf('%05d-%05d', $l->examen->section->ordre, $l->examen->ordre))
            ->groupBy(fn (LaboDemandeExamen $l) => $l->examen->section->nom)
            ->map(function ($lignes, $nomSection) use ($rectificationsPrecedentes, &$motifs) {
                return [
                    'nom' => $nomSection,
                    'examens' => $lignes->map(function (LaboDemandeExamen $l) use ($rectificationsPrecedentes, &$motifs) {
                        $estRectifie = $l->nombre_rectifications > (int) ($rectificationsPrecedentes[$l->examen->code] ?? 0);
                        if ($estRectifie && $l->motif_derniere_rectification) {
                            $motifs[] = $l->examen_nom . ' : ' . $l->motif_derniere_rectification;
                        }

                        $ordre = $l->examen->parametres->pluck('ordre', 'id');

                        return [
                            'code' => $l->examen->code,
                            'nom' => $l->examen_nom,
                            'methode' => $l->examen->methode,
                            'sous_traitant' => $l->sous_traite ? $l->laboratoire_sous_traitant : null,
                            'commentaire' => $l->commentaire_biologiste,
                            'valide_par' => $l->validateurBiologique?->name,
                            'valide_le' => $l->valide_biologique_le?->format('d/m/Y H:i'),
                            'rectification' => $l->nombre_rectifications,
                            'rectifie_dans_cette_version' => $estRectifie,
                            'lignes' => $l->resultats
                                ->filter(fn ($r) => $r->aUneValeur() && ($l->examen->parametres->firstWhere('id', $r->parametre_id)?->imprimable ?? true))
                                ->sortBy(fn ($r) => $ordre[$r->parametre_id] ?? 999)
                                ->map(fn ($r) => [
                                    'groupe' => $l->examen->parametres->firstWhere('id', $r->parametre_id)?->groupe,
                                    'libelle' => $r->libelle,
                                    'valeur' => $r->valeurAffichee(),
                                    'unite' => $r->unite,
                                    'norme' => $r->normeAffichee(),
                                    'flag' => $r->flag?->value,
                                    'symbole' => $r->flag?->symbole() ?? '',
                                ])->values()->all(),
                            'germes' => $l->germesIsoles->map(fn ($g) => [
                                'nom' => $g->germe_nom,
                                'numeration' => $g->numeration,
                                'antibiogramme' => $g->antibiogramme->sortBy('antibiotique_nom')->map(fn ($a) => [
                                    'antibiotique' => $a->antibiotique_nom,
                                    'interpretation' => $a->interpretation,
                                    'valeur' => $a->valeur,
                                ])->values()->all(),
                            ])->values()->all(),
                        ];
                    })->values()->all(),
                ];
            })->values()->all();

        $etab = $demande->etablissement;
        $patient = $demande->patient;
        $ageJours = ContexteLabo::ageEnJours($patient, $demande->created_at);
        $biologistes = $demande->examens->map(fn ($l) => $l->validateurBiologique?->name)->filter()->unique()->values()->all();

        return [
            'etablissement' => [
                'nom' => $etab->nom,
                'adresse' => $etab->adresse,
                'contact' => $etab->contact,
                'email' => $etab->email,
                'logo' => $etab->logo,
                'numero_enregistrement' => $etab->numero_enregistrement,
            ],
            'demande' => [
                'numero' => $demande->numero,
                'date' => $demande->created_at->format('d/m/Y H:i'),
                'origine' => $demande->origine->libelle(),
                'prescripteur' => $demande->nomPrescripteur(),
                'renseignements_cliniques' => $demande->renseignements_cliniques,
                'urgence' => $demande->urgence,
            ],
            'patient' => [
                'nom' => $patient->full_name,
                'identifiant' => $patient->identifiant_national_sante,
                'sexe' => $patient->gender,
                'age' => ContexteLabo::ageTexte($ageJours),
                'grossesse' => $demande->grossesse ? ($demande->semaines_amenorrhee ? "Enceinte ({$demande->semaines_amenorrhee} SA)" : 'Enceinte') : null,
            ],
            'sections' => $sections,
            'biologistes' => $biologistes,
            'est_partiel' => $publiees < $toutes,
            'est_rectificatif' => (bool) $motifs,
            'motif_rectification' => $motifs ? implode("\n", $motifs) : null,
            'emis_le' => now()->format('d/m/Y H:i'),
        ];
    }
}
