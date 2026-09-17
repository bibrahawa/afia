<?php

namespace App\Services\Assurance;

use App\Enums\Assurance\LienBeneficiaire;
use App\Enums\Assurance\StatutCouverture;
use App\Enums\TypeRelationFamiliale;
use App\Exceptions\Assurance\OperationAssuranceImpossible;
use App\Models\Assurance\Adhesion;
use App\Models\Assurance\Beneficiaire;
use App\Models\Assurance\Contrat;
use App\Models\Assurance\Entreprise;
use App\Models\Assurance\Formule;
use App\Models\Assurance\PatientEmploi;
use App\Models\InsuranceCompany;
use App\Models\Patient;
use App\Models\RelationFamiliale;
use App\Support\EtablissementContext;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Opérations métier du référentiel assurance. Les contrôleurs ne créent
 * jamais adhésions et bénéficiaires directement : les règles sont ici.
 */
class ReferentielAssuranceService
{
    // ------------------------------------------------------------------ Emplois

    public function rattacherEmploi(Patient $patient, Entreprise $entreprise, array $donnees): PatientEmploi
    {
        $dejaEnCours = PatientEmploi::where('patient_id', $patient->id)
            ->where('entreprise_id', $entreprise->id)
            ->enCours()
            ->exists();

        if ($dejaEnCours) {
            throw new OperationAssuranceImpossible("{$patient->full_name} est déjà rattaché(e) à {$entreprise->nom}.");
        }

        return DB::transaction(function () use ($patient, $entreprise, $donnees) {
            $this->suivreParEtablissement($patient);

            return PatientEmploi::create([
                'patient_id' => $patient->id,
                'entreprise_id' => $entreprise->id,
                'matricule' => $donnees['matricule'] ?? null,
                'poste' => $donnees['poste'] ?? null,
                'date_debut' => $donnees['date_debut'] ?? null,
                'date_fin' => $donnees['date_fin'] ?? null,
            ]);
        });
    }

    /** Fin d'emploi : les adhésions liées à cet emploi sont clôturées à la même date. */
    public function terminerEmploi(PatientEmploi $emploi, Carbon $dateFin): void
    {
        DB::transaction(function () use ($emploi, $dateFin) {
            $emploi->update(['date_fin' => $dateFin->toDateString()]);

            Adhesion::where('patient_emploi_id', $emploi->id)
                ->where(fn ($q) => $q->whereNull('date_fin')->orWhere('date_fin', '>', $dateFin->toDateString()))
                ->get()
                ->each(fn (Adhesion $a) => $this->cloturerAdhesion($a, $dateFin));
        });
    }

    // ------------------------------------------------------------------ Adhésions

    public function creerAdhesion(Formule $formule, Patient $patient, array $donnees): Adhesion
    {
        $formule->loadMissing('contrat');
        $contrat = $formule->contrat;
        $emploi = ! empty($donnees['patient_emploi_id']) ? PatientEmploi::findOrFail($donnees['patient_emploi_id']) : null;

        if ($emploi && (int) $emploi->patient_id !== (int) $patient->id) {
            throw new OperationAssuranceImpossible("L'emploi choisi n'appartient pas à ce patient.");
        }

        if ($emploi && $contrat->entreprise_id && (int) $emploi->entreprise_id !== (int) $contrat->entreprise_id) {
            throw new OperationAssuranceImpossible("Ce contrat est souscrit par {$contrat->entreprise->nom} : l'emploi doit être dans cette entreprise.");
        }

        $dejaAdherent = Adhesion::where('patient_id', $patient->id)
            ->whereHas('formule', fn ($q) => $q->where('contrat_id', $contrat->id))
            ->where('statut', '!=', StatutCouverture::Resiliee->value)
            ->exists();

        if ($dejaAdherent) {
            throw new OperationAssuranceImpossible("{$patient->full_name} est déjà adhérent(e) de ce contrat.");
        }

        $debut = Carbon::parse($donnees['date_debut'] ?? $contrat->date_debut);
        if ($debut->lt($contrat->date_debut)) {
            throw new OperationAssuranceImpossible("L'adhésion ne peut pas commencer avant le début du contrat ({$contrat->date_debut->format('d/m/Y')}).");
        }

        return DB::transaction(function () use ($formule, $patient, $donnees, $emploi, $debut) {
            $this->suivreParEtablissement($patient);

            $adhesion = Adhesion::create([
                'formule_id' => $formule->id,
                'patient_id' => $patient->id,
                'patient_emploi_id' => $emploi?->id,
                'numero_carte' => $donnees['numero_carte'] ?? null,
                'date_debut' => $debut->toDateString(),
                'date_fin' => $donnees['date_fin'] ?? null,
                'statut' => StatutCouverture::Active,
                'reprise_patient_insurance_id' => $donnees['reprise_patient_insurance_id'] ?? null,
                'notes' => $donnees['notes'] ?? null,
            ]);

            Beneficiaire::create([
                'adhesion_id' => $adhesion->id,
                'patient_id' => $patient->id,
                'lien' => LienBeneficiaire::Adherent,
                'numero_carte' => $adhesion->numero_carte,
                'date_debut' => $adhesion->date_debut,
                'date_fin' => $adhesion->date_fin,
                'statut' => StatutCouverture::Active,
            ]);

            return $adhesion;
        });
    }

    public function cloturerAdhesion(Adhesion $adhesion, Carbon $dateFin): void
    {
        DB::transaction(function () use ($adhesion, $dateFin) {
            foreach ($adhesion->beneficiaires()->get() as $b) {
                if (! $b->date_fin || $b->date_fin->gt($dateFin)) {
                    $b->update(['date_fin' => $dateFin->toDateString(), 'statut' => $this->statutPourFin($dateFin)]);
                }
            }

            $adhesion->update(['date_fin' => $dateFin->toDateString(), 'statut' => $this->statutPourFin($dateFin)]);
        });
    }

    // ------------------------------------------------------------------ Ayants droit

    public function ajouterBeneficiaire(Adhesion $adhesion, Patient $patient, array $donnees): Beneficiaire
    {
        $adhesion->loadMissing('formule', 'patient');
        $lien = LienBeneficiaire::from($donnees['lien']);

        if ($lien === LienBeneficiaire::Adherent) {
            throw new OperationAssuranceImpossible("L'adhérent est inscrit automatiquement avec l'adhésion.");
        }

        if ($adhesion->statut === StatutCouverture::Resiliee) {
            throw new OperationAssuranceImpossible('Cette adhésion est résiliée : impossible d\'y ajouter un ayant droit.');
        }

        if ((int) $patient->id === (int) $adhesion->patient_id) {
            throw new OperationAssuranceImpossible("{$patient->full_name} est l'adhérent de ce contrat.");
        }

        if ($adhesion->beneficiaires()->where('patient_id', $patient->id)->exists()) {
            throw new OperationAssuranceImpossible("{$patient->full_name} figure déjà parmi les bénéficiaires de cette adhésion.");
        }

        $debut = Carbon::parse($donnees['date_debut'] ?? today());
        if ($debut->lt($adhesion->date_debut)) {
            $debut = $adhesion->date_debut->copy();
        }

        $beneficiaire = new Beneficiaire([
            'adhesion_id' => $adhesion->id,
            'patient_id' => $patient->id,
            'lien' => $lien,
            'etudiant' => (bool) ($donnees['etudiant'] ?? false),
            'numero_carte' => $donnees['numero_carte'] ?? null,
            'date_debut' => $debut->toDateString(),
            'date_fin' => $adhesion->date_fin,
            'statut' => StatutCouverture::Active,
        ]);
        $beneficiaire->setRelation('patient', $patient);

        $finAge = $beneficiaire->finDroitsParAge($adhesion->formule);
        if ($finAge && $finAge->lt($debut)) {
            $ageMax = $beneficiaire->etudiant ? $adhesion->formule->age_max_enfant_etudiant : $adhesion->formule->age_max_enfant;
            throw new OperationAssuranceImpossible(
                "{$patient->full_name} a dépassé l'âge limite de couverture des enfants pour cette formule ({$ageMax} ans"
                . ($beneficiaire->etudiant ? ', étudiant' : '') . ').'
            );
        }

        return DB::transaction(function () use ($beneficiaire, $patient) {
            $this->suivreParEtablissement($patient);
            $beneficiaire->save();

            return $beneficiaire;
        });
    }

    public function cloturerBeneficiaire(Beneficiaire $beneficiaire, Carbon $dateFin): void
    {
        if ($beneficiaire->lien === LienBeneficiaire::Adherent) {
            throw new OperationAssuranceImpossible("Pour arrêter la couverture de l'adhérent, clôturez l'adhésion (ses ayants droit perdent aussi leurs droits).");
        }

        $beneficiaire->update(['date_fin' => $dateFin->toDateString(), 'statut' => $this->statutPourFin($dateFin)]);
    }

    /**
     * Ayants droit proposés à partir des liens familiaux déjà enregistrés
     * pour l'adhérent. Une proposition, jamais une inscription automatique :
     * être le conjoint d'un assuré ne donne aucun droit tant que la personne
     * n'est pas déclarée au contrat.
     *
     * @return Collection<int, array{patient: Patient, lien: LienBeneficiaire}>
     */
    public function suggestionsAyantsDroit(Adhesion $adhesion): Collection
    {
        $dejaInscrits = $adhesion->beneficiaires()->pluck('patient_id')->all();
        $suggestions = collect();

        // « L est [type] de l'adhérent »
        RelationFamiliale::with('personneLiee')->where('patient_id', $adhesion->patient_id)->get()
            ->each(function (RelationFamiliale $r) use ($suggestions) {
                if ($lien = LienBeneficiaire::depuisRelation($r->type_relation)) {
                    $suggestions->push(['patient' => $r->personneLiee, 'lien' => $lien]);
                }
            });

        // « l'adhérent est [type] de P » → P est [inverse] de l'adhérent
        RelationFamiliale::with('patient')->where('personne_liee_id', $adhesion->patient_id)->get()
            ->each(function (RelationFamiliale $r) use ($suggestions) {
                $lien = match ($r->type_relation) {
                    TypeRelationFamiliale::Pere, TypeRelationFamiliale::Mere, TypeRelationFamiliale::Tuteur => LienBeneficiaire::Enfant,
                    TypeRelationFamiliale::Epoux, TypeRelationFamiliale::Epouse => LienBeneficiaire::Conjoint,
                    default => null,
                };
                if ($lien) {
                    $suggestions->push(['patient' => $r->patient, 'lien' => $lien]);
                }
            });

        return $suggestions
            ->filter(fn ($s) => $s['patient'] && ! in_array($s['patient']->id, $dejaInscrits, true))
            ->unique(fn ($s) => $s['patient']->id)
            ->values();
    }

    // ------------------------------------------------------------------ Compatibilité ancien écran

    /**
     * Ancien écran « Patients assurés » : une saisie (patient, compagnie,
     * police, taux, plafond, dates) devient un contrat individuel complet.
     * Les patients partageant un même numéro de police chez la même compagnie
     * sont regroupés dans le même contrat, une formule par couple taux/plafond.
     */
    public function creerContratIndividuel(Patient $patient, InsuranceCompany $organisme, array $donnees): Beneficiaire
    {
        return DB::transaction(function () use ($patient, $organisme, $donnees) {
            $debut = Carbon::parse($donnees['start_date']);

            $contrat = Contrat::firstOrCreate(
                ['insurance_company_id' => $organisme->id, 'numero_police' => $donnees['policy_number']],
                ['date_debut' => $debut->toDateString(), 'statut' => StatutCouverture::Active]
            );

            if ($contrat->date_debut->gt($debut)) {
                $contrat->update(['date_debut' => $debut->toDateString()]);
            }

            $formule = $this->formulePour($contrat, (float) $donnees['coverage_percentage'], $donnees['annual_limit'] ?? null);

            $adhesion = $this->creerAdhesion($formule, $patient, [
                'numero_carte' => $donnees['policy_number'],
                'date_debut' => $debut->toDateString(),
                'date_fin' => $donnees['end_date'] ?? null,
                'notes' => $donnees['notes'] ?? null,
            ]);

            return $adhesion->beneficiaires()->where('lien', LienBeneficiaire::Adherent->value)->firstOrFail();
        });
    }

    public function formulePour(Contrat $contrat, float $taux, $plafond): Formule
    {
        $plafond = $plafond !== null && $plafond !== '' ? round((float) $plafond, 2) : null;
        $libelle = 'Taux ' . rtrim(rtrim(number_format($taux, 2, '.', ''), '0'), '.') . ' %'
            . ($plafond ? ' — plafond ' . number_format($plafond, 0, ',', ' ') . ' GNF' : '');

        return Formule::firstOrCreate(
            ['contrat_id' => $contrat->id, 'libelle' => $libelle],
            ['taux_prise_en_charge' => $taux, 'plafond_annuel_beneficiaire' => $plafond]
        );
    }

    // ------------------------------------------------------------------ Outils

    /** Un patient couvert doit être retrouvable par la réception de l'établissement. */
    private function suivreParEtablissement(Patient $patient): void
    {
        if ($etablissement = EtablissementContext::current()) {
            $etablissement->patients()->syncWithoutDetaching([$patient->id]);
        }
    }

    private function statutPourFin(Carbon $dateFin): StatutCouverture
    {
        return $dateFin->lt(today()) ? StatutCouverture::Resiliee : StatutCouverture::Active;
    }
}
