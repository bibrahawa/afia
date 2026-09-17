<?php

namespace App\Services\Parcours;

use App\Exceptions\Parcours\OperationParcoursImpossible;
use App\Models\Consultation;
use App\Enums\TypeRelationFamiliale;
use App\Models\Parcours\Constante;
use App\Models\Parcours\Grossesse;
use App\Models\RelationFamiliale;
use App\Support\EtablissementContext;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use App\Enums\Assurance\FamilleActe;
use App\Support\Assurance\FamillesActes;
use Illuminate\Support\Facades\DB;

/**
 * Ouverture et suivi d'une grossesse. La DDR saisie à l'accueil suffit :
 * terme, date prévue d'accouchement et calendrier des CPN en découlent.
 */
class GrossesseService
{
    public function ouvrir(Patient $patient, Carbon $ddr, array $donnees = [], ?User $auteur = null): Grossesse
    {
        if ($ddr->isFuture()) {
            throw new OperationParcoursImpossible('La date des dernières règles ne peut pas être dans le futur.');
        }

        if ($ddr->diffInDays(today()) > 44 * 7) {
            throw new OperationParcoursImpossible('Cette date des dernières règles remonte à plus de 44 semaines : vérifiez la saisie.');
        }

        if ($this->enCours($patient)) {
            throw new OperationParcoursImpossible("Une grossesse est déjà suivie pour {$patient->full_name}. Clôturez-la d'abord.");
        }

        return Grossesse::create([
            'patient_id' => $patient->id,
            'medecin_id' => $donnees['medecin_id'] ?? null,
            'ddr' => $ddr->toDateString(),
            'dpa' => Grossesse::dpaDepuis($ddr)->toDateString(),
            'gestite' => $donnees['gestite'] ?? null,
            'parite' => $donnees['parite'] ?? null,
            'notes' => $donnees['notes'] ?? null,
            'statut' => Grossesse::EN_COURS,
            'ouverte_par' => $auteur?->id,
        ]);
    }

    public function enCours(Patient $patient): ?Grossesse
    {
        return Grossesse::where('patient_id', $patient->id)->where('statut', Grossesse::EN_COURS)->latest('id')->first();
    }

    /** Correction de la DDR (échographie de datation) : la DPA et le calendrier suivent. */
    public function corrigerDdr(Grossesse $grossesse, Carbon $ddr): Grossesse
    {
        if (! $grossesse->estEnCours()) {
            throw new OperationParcoursImpossible('Cette grossesse est clôturée.');
        }

        $grossesse->update(['ddr' => $ddr->toDateString(), 'dpa' => Grossesse::dpaDepuis($ddr)->toDateString()]);

        return $grossesse->fresh();
    }

    /**
     * Clôture du suivi. En cas d'accouchement, le dossier du nouveau-né peut être
     * créé dans la foulée : il est rattaché à sa mère et son poids de naissance
     * enregistré, sans ressaisie au guichet.
     *
     * @param array $nouveauNe prenom, sexe, poids_kg, taille_cm
     */
    public function cloturer(Grossesse $grossesse, string $issue, Carbon $date, ?string $notes = null, array $nouveauNe = [], ?User $auteur = null): Grossesse
    {
        if (! isset(Grossesse::ISSUES[$issue])) {
            throw new OperationParcoursImpossible('Issue de grossesse inconnue.');
        }

        if ($date->lt($grossesse->ddr)) {
            throw new OperationParcoursImpossible('La date de l\'issue est antérieure aux dernières règles.');
        }

        return DB::transaction(function () use ($grossesse, $issue, $date, $notes, $nouveauNe, $auteur) {
            $grossesse->update([
                'statut' => $issue === 'accouchement' ? Grossesse::TERMINEE : Grossesse::INTERROMPUE,
                'issue' => $issue,
                'date_issue' => $date->toDateString(),
                'notes' => trim(($grossesse->notes ? $grossesse->notes . "\n" : '') . (string) $notes) ?: null,
            ]);

            if ($issue === 'accouchement' && ! empty($nouveauNe['prenom'])) {
                $this->enregistrerNouveauNe($grossesse, $date, $nouveauNe, $auteur);
            }

            return $grossesse->fresh();
        });
    }

    /** Dossier du nouveau-né : patient créé, lien avec la mère, poids de naissance. */
    public function enregistrerNouveauNe(Grossesse $grossesse, Carbon $naissance, array $donnees, ?User $auteur = null): Patient
    {
        $grossesse->loadMissing('patient');
        $mere = $grossesse->patient;

        $bebe = Patient::create([
            'first_name' => mb_substr(trim($donnees['prenom']), 0, 50),
            'last_name' => $mere->last_name,
            'gender' => in_array($donnees['sexe'] ?? null, ['Homme', 'Femme'], true) ? $donnees['sexe'] : 'Homme',
            'birth_date' => $naissance->toDateString(),
            'relative_name' => $mere->full_name,
            'description' => 'Né(e) à la clinique — suivi de grossesse n° ' . $grossesse->id,
        ]);

        if ($etablissement = EtablissementContext::current()) {
            $etablissement->patients()->syncWithoutDetaching([$bebe->id]);
        }

        // « La mère est la mere de ce patient » : même sémantique que l'écran des liens familiaux.
        RelationFamiliale::firstOrCreate([
            'patient_id' => $bebe->id,
            'personne_liee_id' => $mere->id,
            'type_relation' => TypeRelationFamiliale::Mere->value,
        ]);

        $mesures = array_filter([
            'poids_kg' => $donnees['poids_kg'] ?? null,
            'taille_cm' => $donnees['taille_cm'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        if ($mesures) {
            Constante::create($mesures + [
                'patient_id' => $bebe->id,
                'mesure_par' => $auteur?->id,
                'mesure_le' => $naissance->copy()->setTimeFrom(now()),
            ]);
        }

        return $bebe;
    }

    /**
     * Rattache la consultation au suivi en cours, UNIQUEMENT s'il s'agit d'un
     * acte de maternité : une consultation pour paludisme ne doit pas valider
     * une CPN au calendrier.
     */
    public function rattacher(Consultation $consultation): ?Grossesse
    {
        if ($consultation->grossesse_id) {
            return $consultation->grossesse;
        }

        $grossesse = $consultation->patient ? $this->enCours($consultation->patient) : null;

        if ($grossesse && $this->estActeDeMaternite($consultation)) {
            $consultation->update(['grossesse_id' => $grossesse->id]);
        }

        return $grossesse;
    }

    /** Motif de rendez-vous ou acte facturé classé « maternité » au catalogue. */
    public function estActeDeMaternite(Consultation $consultation): bool
    {
        $familles = app(FamillesActes::class);
        $maternite = FamilleActe::Maternite;

        $consultation->loadMissing('visite.motifRdv', 'services', 'packages');

        $serviceMotif = $consultation->visite?->motifRdv?->service_id;

        if ($serviceMotif && $familles->pour('service', (int) $serviceMotif) === $maternite) {
            return true;
        }

        foreach ($consultation->services as $service) {
            if ($familles->pour('service', $service->id) === $maternite) {
                return true;
            }
        }

        foreach ($consultation->packages as $package) {
            if ($familles->pour('package', $package->id) === $maternite) {
                return true;
            }
        }

        return false;
    }

    /** Poids et tension relevés à chaque visite, pour la courbe de suivi. */
    public function mesures(Grossesse $grossesse): array
    {
        return DB::table('constantes')
            ->where('patient_id', $grossesse->patient_id)
            ->whereDate('mesure_le', '>=', $grossesse->ddr)
            ->when($grossesse->date_issue, fn ($q) => $q->whereDate('mesure_le', '<=', $grossesse->date_issue))
            ->orderBy('mesure_le')
            ->get(['mesure_le', 'poids_kg', 'tension_systolique', 'tension_diastolique'])
            ->map(function ($ligne) use ($grossesse) {
                $date = Carbon::parse($ligne->mesure_le);

                return [
                    'date' => $date,
                    'terme' => $grossesse->termeLisible($date),
                    'poids' => $ligne->poids_kg !== null ? (float) $ligne->poids_kg : null,
                    'tension' => $ligne->tension_systolique ? $ligne->tension_systolique . '/' . $ligne->tension_diastolique : null,
                ];
            })->all();
    }
}
