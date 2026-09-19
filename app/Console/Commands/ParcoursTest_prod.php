<?php

namespace App\Console\Commands;

use App\Enums\Parcours\StatutVisite;
use App\Models\Assurance\ConventionFamille;
use App\Models\Consultation;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\InsuranceCompany;
use App\Models\Labo\LaboDemande;
use App\Models\Labo\LaboDemandeExamen;
use App\Models\Labo\LaboExamen;
use App\Models\Medicament;
use App\Models\MotifRdv;
use App\Models\Parcours\Grossesse;
use App\Models\Parcours\Visite;
use App\Models\Patient;
use App\Models\PatientInsurance;
use App\Models\Service as ActeService;
use App\Models\User;
use App\Services\AppointmentBookingService;
use App\Services\Labo\CompteRenduService;
use App\Services\Labo\DemandeService as LaboDemandeService;
use App\Services\Labo\FacturationPartenaireService;
use App\Services\Labo\LaboReseauService;
use App\Services\Labo\PartenariatService;
use App\Services\Labo\PrelevementService;
use App\Services\Labo\ResultatService;
use App\Services\Labo\ValidationService;
use App\Services\Parcours\AccueilService;
use App\Services\Parcours\ConsultationRapideService;
use App\Services\Parcours\GrossesseService;
use App\Services\PaymentService;
use App\Services\SmsService;
use App\Support\ContexteTemporaire;
use App\Support\Facturation\SoldeTransaction;
use App\Support\Marque;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Essai de bout en bout du parcours : rendez-vous, accueil, consultation,
 * caisse, assurance, grossesse, laboratoire et réseau inter-établissements.
 *
 *   php artisan hali:parcours-test
 *   php artisan hali:parcours-test --cas=rdv --cas=labo
 *   php artisan hali:parcours-test --garder        (conserve les données créées)
 *
 * PAR DÉFAUT, RIEN N'EST CONSERVÉ : tout se déroule dans une transaction
 * annulée à la fin. Les SMS ne partent jamais, le service d'envoi est
 * remplacé par un double qui compte les messages.
 */
class ParcoursTest_prod extends Command
{
    protected $signature = 'hali:parcours-test
        {--cas=* : cas à jouer (rdv, consultation, caisse, assurance, urgence, depart, absence, grossesse, labo, reseau)}
        {--garder : conserve les données créées au lieu de tout annuler}
        {--force : autorise l\'exécution en environnement de production}';

    protected $description = 'Joue un parcours complet, du rendez-vous au laboratoire, et vérifie les règles métier';

    private const CAS = ['rdv', 'consultation', 'caisse', 'assurance', 'urgence', 'depart', 'absence', 'grossesse', 'labo', 'reseau'];

    /** @var array<int, array{cas: string, libelle: string, ok: bool, detail: string}> */
    private array $resultats = [];

    private Etablissement $clinique;
    private Etablissement $laboratoire;
    private User $utilisateur;
    private User $biologiste;
    private Employee $medecin;
    private Department $departement;
    private MotifRdv $motif;
    private ActeService $acteConsultation;
    private ActeService $acteMaternite;
    private Medicament $medicament;
    private int $smsSimules = 0;

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Environnement de production : relancez avec --force si c\'est vraiment ce que vous voulez.');

            return self::FAILURE;
        }

        $cas = $this->option('cas') ?: self::CAS;
        $inconnus = array_diff($cas, self::CAS);

        if ($inconnus) {
            $this->error('Cas inconnu(s) : ' . implode(', ', $inconnus) . '. Disponibles : ' . implode(', ', self::CAS));

            return self::FAILURE;
        }

        $this->remplacerServiceSms();

        $this->info(Marque::nom() . ' — essai du parcours complet');
        $this->line('Cas joués : ' . implode(', ', $cas));
        $this->line($this->option('garder') ? 'Les données seront CONSERVÉES.' : 'Les données seront annulées à la fin.');
        $this->newLine();

        DB::beginTransaction();

        try {
            $this->preparerJeuDeDonnees();

            foreach ($cas as $unCas) {
                $this->newLine();
                $this->line('<fg=cyan>── ' . strtoupper($unCas) . ' ──</>');

                try {
                    $this->{'cas' . Str::studly($unCas)}();
                } catch (Throwable $e) {
                    $this->noter($unCas, 'Exécution du cas', false, $e->getMessage());
                }
            }
        } catch (Throwable $e) {
            DB::rollBack();
            $this->error('Préparation impossible : ' . $e->getMessage());

            return self::FAILURE;
        }

        if ($this->option('garder')) {
            DB::commit();
        } else {
            DB::rollBack();
        }

        return $this->afficherBilan();
    }

    // ------------------------------------------------------------------ Cas

    private function casRdv(): void
    {
        $patient = $this->patient('Fanta');

        $rdv = app(AppointmentBookingService::class)->planifierParMedecin(
            $this->medecin->id,
            $patient->id,
            now()->addDay()->setTime(9, 0),
            $this->motif->dureePour($this->medecin),
            'Essai automatisé',
            $this->motif->id
        );

        $this->noter('rdv', 'Rendez-vous créé avec son motif', $rdv->motif_rdv_id === $this->motif->id, 'n° ' . $rdv->id);

        $visite = app(AccueilService::class)->arriveeDepuisRendezVous($rdv, [], $this->utilisateur);
        $transaction = $visite->consultation->transaction()->first();

        $this->noter('rdv', 'Arrivée : visite et consultation ouvertes', $visite->consultation !== null);
        $this->noter('rdv', 'Acte du motif facturé automatiquement',
            $transaction && (float) $transaction->total === (float) $this->acteConsultation->amount,
            $transaction ? number_format((float) $transaction->total, 0, ',', ' ') . ' GNF' : 'aucune facture');

        $this->noter('rdv', 'Deuxième arrivée refusée', $this->refuse(
            fn () => app(AccueilService::class)->arriveeDepuisRendezVous($rdv->fresh(), [], $this->utilisateur)
        ));
    }

    private function casConsultation(): void
    {
        $visite = $this->recevoir('Ousmane');

        app(AccueilService::class)->enregistrerConstantes($visite, [
            'temperature' => 38.5, 'tension_systolique' => 130, 'tension_diastolique' => 80, 'poids_kg' => 68,
        ], $this->utilisateur);

        $this->noter('consultation', 'Constantes enregistrées et anomalie détectée',
            $visite->fresh('derniereConstante')->derniereConstante?->alertes() !== []);

        app(AccueilService::class)->appeler($visite->fresh(), $this->medecin);

        $resultat = app(ConsultationRapideService::class)->enregistrer($visite->consultation->fresh(), [
            'action' => 'terminer',
            'diagnostic' => 'Paludisme simple',
            'signes' => ['Fièvre', 'Céphalées'],
            'actes' => [
                'services' => [['id' => $this->acteConsultation->id]],
                'medicaments' => [['id' => $this->medicament->id, 'quantite' => 2, 'dose' => '1 cp', 'frequence' => '2 fois par jour', 'duree' => '3 jours']],
            ],
            'prochain_rdv_jours' => 7,
            'prochain_rdv_motif_id' => $this->motif->id,
        ], $this->utilisateur);

        $consultation = $resultat['consultation'];

        $this->noter('consultation', 'Consultation terminée avec diagnostic', $consultation->statut === Consultation::TERMINEE);
        $this->noter('consultation', 'Posologie prescrite conservée',
            $consultation->medicaments->first()?->pivot->dose === '1 cp');
        $this->noter('consultation', 'Visite clôturée', $visite->fresh()->statut === StatutVisite::Terminee);
        $this->noter('consultation', 'Prochain rendez-vous planifié', $consultation->prochain_rdv !== null,
            $resultat['avertissements'] ? implode(' ', $resultat['avertissements']) : '');

        $this->noter('consultation', 'Refus de terminer sans diagnostic', $this->refuse(function () {
            $autre = $this->recevoir('Sans diagnostic');
            app(ConsultationRapideService::class)->enregistrer($autre->consultation, ['action' => 'terminer', 'diagnostic' => ''], $this->utilisateur);
        }));
    }

    private function casCaisse(): void
    {
        $visite = $this->recevoir('Mariam');
        $transaction = $visite->consultation->transaction()->first();
        $montant = (float) $transaction->total;

        app(PaymentService::class)->payPatientTransaction($transaction, $montant / 2, 'CASH');
        $solde = SoldeTransaction::pour($transaction->fresh());

        $this->noter('caisse', 'Paiement partiel : reste dû exact',
            (float) $solde->resteDuPatient() === round($montant / 2, 2),
            number_format($solde->resteDuPatient(), 0, ',', ' ') . ' GNF restants');

        $paiement = app(PaymentService::class)->payPatientTransaction($transaction->fresh(), $montant / 2, 'CASH');
        $this->noter('caisse', 'Solde complet', (float) SoldeTransaction::pour($transaction->fresh())->resteDuPatient() === 0.0);

        $this->noter('caisse', 'Encaissement au-delà du dû refusé', $this->refuse(
            fn () => app(PaymentService::class)->payPatientTransaction($transaction->fresh(), 1000, 'CASH')
        ));

        app(\App\Services\Facturation\AnnulationPaiementService::class)->annuler($paiement, 'Essai automatisé', $this->utilisateur);
        $this->noter('caisse', 'Annulation : le montant redevient dû',
            (float) SoldeTransaction::pour($transaction->fresh())->resteDuPatient() === round($montant / 2, 2));
    }

    private function casAssurance(): void
    {
        $patient = $this->patient('Assurée');

        $organisme = InsuranceCompany::firstOrCreate(
            ['code' => 'TEST-ASSUR'],
            ['name' => 'Assureur d\'essai', 'type' => 'assureur']
        );

        ConventionFamille::updateOrCreate(
            ['insurance_company_id' => $organisme->id, 'famille_acte' => 'consultation'],
            ['remise_pourcentage' => 0, 'valid_from' => now()->subYear()->toDateString(), 'actif' => true]
        );

        PatientInsurance::create([
            'patient_id' => $patient->id,
            'insurance_company_id' => $organisme->id,
            'policy_number' => 'ESSAI-' . Str::upper(Str::random(5)),
            'coverage_percentage' => 80,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonths(6),
            'status' => 'active',
        ]);

        $visite = app(AccueilService::class)->arriveeSansRendezVous(
            $patient, $this->medecin, ['service_id' => $this->acteConsultation->id, 'motif' => 'Essai assurance'], $this->utilisateur
        );

        $facture = $visite->consultation->transaction()->first()?->invoice()->first();
        $attendu = round((float) $this->acteConsultation->amount * 0.8);

        $this->noter('assurance', 'Part assurance calculée à 80 %',
            $facture && (float) $facture->insurance_amount === (float) $attendu,
            $facture ? number_format((float) $facture->insurance_amount, 0, ',', ' ') . ' / ' . number_format($attendu, 0, ',', ' ') : 'pas de facture');

        $this->noter('assurance', 'Part patient = reste',
            $facture && round((float) $facture->patient_amount + (float) $facture->insurance_amount) === round((float) $facture->total_amount));

        $this->noter('assurance', 'Réclamation créée pour l\'assureur',
            \App\Models\InsuranceClaim::where('invoice_id', $facture?->id)->exists());
    }

    private function casUrgence(): void
    {
        $premier = $this->recevoir('Arrivé en premier');
        $urgent = app(AccueilService::class)->arriveeSansRendezVous(
            $this->patient('Urgence'), $this->medecin,
            ['service_id' => $this->acteConsultation->id, 'urgence' => true, 'motif' => 'Urgence'],
            $this->utilisateur
        );

        $file = Visite::duJour()->where('medecin_id', $this->medecin->id)->actives()->ordreFile()->pluck('id')->all();

        $this->noter('urgence', 'L\'urgence passe en tête', ($file[0] ?? null) === $urgent->id);

        app(AccueilService::class)->placerEnTete($premier->fresh());
        $file = Visite::duJour()->where('medecin_id', $this->medecin->id)->actives()->ordreFile()->pluck('id')->all();

        $this->noter('urgence', 'L\'accueil peut forcer un autre passage', ($file[0] ?? null) === $premier->id);
    }

    private function casDepart(): void
    {
        $visite = $this->recevoir('Reparti');
        $transaction = $visite->consultation->transaction()->first();

        app(AccueilService::class)->marquerPartie($visite, 'Attente trop longue');

        $this->noter('depart', 'Visite clôturée « parti »', $visite->fresh()->statut === StatutVisite::Partie);
        $this->noter('depart', 'Facture annulée faute d\'encaissement', $transaction->fresh()->status === 'cancel');

        $autre = $this->recevoir('Reparti après paiement');
        $transactionPayee = $autre->consultation->transaction()->first();
        app(PaymentService::class)->payPatientTransaction($transactionPayee, (float) $transactionPayee->total, 'CASH');

        $this->noter('depart', 'Départ refusé si un encaissement existe', $this->refuse(
            fn () => app(AccueilService::class)->marquerPartie($autre->fresh(), 'Essai')
        ));
    }

    private function casAbsence(): void
    {
        $rdv = app(AppointmentBookingService::class)->planifierParMedecin(
            $this->medecin->id, $this->patient('Absent')->id, now()->addDays(2)->setTime(10, 0),
            15, 'Essai absence', $this->motif->id
        );

        app(AccueilService::class)->marquerAbsent($rdv);

        $this->noter('absence', 'Rendez-vous marqué non honoré', $rdv->fresh()->status === 'no_show');
    }

    private function casGrossesse(): void
    {
        $patiente = $this->patient('Enceinte', 'Femme');
        $grossesse = app(GrossesseService::class)->ouvrir($patiente, now()->subWeeks(30), ['medecin_id' => $this->medecin->id], $this->utilisateur);

        $this->noter('grossesse', 'Terme et date d\'accouchement calculés',
            $grossesse->terme() !== null && $grossesse->dpa->isFuture(),
            $grossesse->termeLisible() . ', DPA ' . $grossesse->dpa->format('d/m/Y'));

        $visite = app(AccueilService::class)->arriveeSansRendezVous(
            $patiente, $this->medecin, ['service_id' => $this->acteMaternite->id, 'motif' => 'CPN'], $this->utilisateur
        );
        app(GrossesseService::class)->rattacher($visite->consultation);

        $this->noter('grossesse', 'La consultation de maternité rejoint le suivi',
            (int) $visite->consultation->fresh()->grossesse_id === $grossesse->id);

        $ordinaire = app(AccueilService::class)->arriveeSansRendezVous(
            $this->patient('Autre patiente', 'Femme'), $this->medecin,
            ['service_id' => $this->acteConsultation->id, 'motif' => 'Fièvre'], $this->utilisateur
        );
        app(GrossesseService::class)->rattacher($ordinaire->consultation);

        $this->noter('grossesse', 'Une consultation ordinaire ne valide pas une CPN',
            $ordinaire->consultation->fresh()->grossesse_id === null);

        $calendrier = collect($grossesse->fresh()->calendrier());
        $this->noter('grossesse', 'Calendrier des 8 contacts OMS', $calendrier->count() === 8,
            'prochaine : ' . ($grossesse->fresh()->prochainContact()['semaines'] ?? '—') . ' SA');
    }

    private function casLabo(): void
    {
        if (! $this->moduleLaboActif($this->clinique)) {
            $this->noter('labo', 'Module laboratoire actif pour la clinique', false, 'module inactif : cas ignoré');

            return;
        }

        $examen = LaboExamen::where('actif', true)->where('prix', '>', 0)->first()
            ?? LaboExamen::where('actif', true)->first();

        if (! $examen) {
            $this->noter('labo', 'Catalogue d\'examens disponible', false, 'catalogue vide : lancez labo:activer');

            return;
        }

        $demande = app(LaboDemandeService::class)->creer([
            'patient_id' => $this->patient('Analyses')->id,
            'origine' => 'spontanee',
            'mode_facturation' => 'gratuit',
            'examens' => [$examen->id],
        ], $this->utilisateur);

        $this->noter('labo', 'Demande créée et numérotée', str_starts_with((string) $demande->numero, 'LAB-'), $demande->numero);

        foreach ($demande->echantillons as $echantillon) {
            app(PrelevementService::class)->marquerPreleveEtRecu($echantillon, $this->utilisateur);
        }

        $ligne = $demande->examens()->first();
        $this->noter('labo', 'Échantillon prélevé puis réceptionné', $ligne->fresh()->statut->permetSaisie());

        $parametres = $ligne->examen->parametres;
        $saisies = [];

        foreach ($parametres as $parametre) {
            if ($parametre->type_resultat->value === 'numerique') {
                $saisies[$parametre->id] = 1;
            }
        }

        if ($saisies) {
            app(ResultatService::class)->enregistrer($ligne->fresh(), $saisies, $this->utilisateur);
            $this->noter('labo', 'Résultats saisis', $ligne->fresh()->resultats()->count() > 0);
        } else {
            $this->noter('labo', 'Résultats saisis', false, 'examen sans paramètre numérique : saisie ignorée');
        }

        app(ValidationService::class)->validerTechniqueEtBiologique($ligne->fresh(), $this->biologiste, 'Essai automatisé');
        $this->noter('labo', 'Double validation technique et biologique',
            $ligne->fresh()->valide_biologique_le !== null);

        $compteRendu = app(CompteRenduService::class)->publier($demande->fresh(), $this->biologiste, false);
        $this->noter('labo', 'Compte rendu publié', $compteRendu->version === 1, 'version ' . $compteRendu->version);
        $this->noter('labo', 'Demande passée en publiée', $demande->fresh()->premiere_publication_le !== null);
    }

    private function casReseau(): void
    {
        if (! $this->moduleLaboActif($this->laboratoire)) {
            $this->noter('reseau', 'Laboratoire partenaire disponible', false, 'module laboratoire inactif : cas ignoré');

            return;
        }

        $examen = ContexteTemporaire::pour($this->laboratoire, fn () => LaboExamen::where('actif', true)->first());

        if (! $examen) {
            $this->noter('reseau', 'Catalogue du laboratoire partenaire', false, 'catalogue vide');

            return;
        }

        ContexteTemporaire::pour($this->laboratoire, function () use ($examen) {
            LaboExamen::whereKey($examen->id)->update(['prix' => 20000]);
        });

        $partenariat = ContexteTemporaire::pour($this->laboratoire, fn () => app(PartenariatService::class)->creer(
            $this->clinique,
            ['mode_facturation_defaut' => 'partenaire', 'remise_pourcentage' => 10, 'delai_paiement_jours' => 30],
            $this->biologiste
        ));

        $this->noter('reseau', 'Partenariat proposé, en attente de la clinique', $partenariat->estPropose());

        $reseau = app(LaboReseauService::class);
        $this->noter('reseau', 'Envoi refusé avant acceptation', $this->refuse(fn () => $reseau->partenariat($partenariat->id)));

        $accepte = $reseau->accepterProposition($partenariat->id, $this->utilisateur);
        $this->noter('reseau', 'Partenariat accepté par la clinique', $accepte->estActif());

        $catalogue = $reseau->catalogue($reseau->partenariat($partenariat->id));
        $ligneCatalogue = $catalogue->firstWhere('id', $examen->id);

        $this->noter('reseau', 'Catalogue au prix négocié (−10 %)',
            $ligneCatalogue && (float) $ligneCatalogue['prix'] === 18000.0,
            $ligneCatalogue ? number_format((float) $ligneCatalogue['prix'], 0, ',', ' ') . ' GNF' : '—');

        $demande = $reseau->envoyer($reseau->partenariat($partenariat->id), $this->patient('Envoyée au partenaire'), [
            'examens' => [$examen->id],
            'renseignements_cliniques' => 'Essai automatisé',
            'consentement_partage' => true,
        ], $this->utilisateur);

        $this->noter('reseau', 'Demande créée chez le laboratoire, clinique prescriptrice',
            (int) $demande->etablissement_id === $this->laboratoire->id
            && (int) $demande->etablissement_prescripteur_id === $this->clinique->id);

        $this->noter('reseau', 'Consentement du patient tracé',
            LaboDemande::withoutGlobalScopes()->find($demande->id)?->consentement_partage_le !== null);

        $creance = \App\Models\Labo\LaboCreancePartenaire::withoutGlobalScopes()->where('demande_id', $demande->id)->first();
        $this->noter('reseau', 'Créance enregistrée au prix négocié',
            $creance && (float) $creance->montant === 18000.0,
            $creance ? number_format((float) $creance->montant, 0, ',', ' ') . ' GNF' : 'aucune créance');

        ContexteTemporaire::pour($this->laboratoire, function () use ($partenariat) {
            $facturation = app(FacturationPartenaireService::class);
            $releve = $facturation->preparerReleve($partenariat->fresh(), today()->startOfMonth(), today()->endOfMonth(), $this->biologiste);
            $facturation->envoyerReleve($releve);

            $this->noter('reseau', 'Relevé préparé puis envoyé (montants figés)',
                $releve->fresh()->estEnvoye(), $releve->numero . ' — ' . number_format((float) $releve->fresh()->montant_total, 0, ',', ' ') . ' GNF');

            $facturation->enregistrerReglement($partenariat->fresh(), [
                'montant' => (float) $releve->fresh()->montant_total, 'mode' => 'virement', 'recu_le' => today()->toDateString(),
            ], [], $this->biologiste);

            $this->noter('reseau', 'Règlement imputé : relevé soldé',
                $releve->fresh()->statut === \App\Models\Labo\LaboRelevePartenaire::SOLDE);
        });

        $this->noter('reseau', 'La clinique voit le relevé reçu', $reseau->relevesRecus()->count() === 1);
    }

    // ------------------------------------------------------------------ Jeu de données

    private function preparerJeuDeDonnees(): void
    {
        $this->clinique = Etablissement::where('type', 'clinique')->where('statut', 'actif')->firstOrFail();
        $this->laboratoire = Etablissement::where('id', '!=', $this->clinique->id)
            ->whereIn('type', ['laboratoire', 'clinique'])->first() ?? $this->clinique;

        $this->utilisateur = User::where('etablissement_id', $this->clinique->id)->firstOrFail();
        Auth::login($this->utilisateur);

        $this->biologiste = User::where('etablissement_id', $this->laboratoire->id)->first() ?? $this->utilisateur;

        $this->medecin = Employee::where('type', 'Doctor')->where('is_active', true)->firstOrFail();
        $this->departement = Department::findOrFail($this->medecin->department_id);

        $this->acteConsultation = ActeService::firstOrCreate(
            ['name' => 'Consultation (essai)'],
            ['amount' => 100000, 'department_id' => $this->departement->id, 'famille_acte' => 'consultation']
        );

        $this->acteMaternite = ActeService::firstOrCreate(
            ['name' => 'Consultation prénatale (essai)'],
            ['amount' => 80000, 'department_id' => $this->departement->id, 'famille_acte' => 'maternite']
        );

        $this->medicament = Medicament::firstOrCreate(
            ['nom' => 'Médicament (essai)'],
            ['forme' => 'comprimé', 'dosage' => '500 mg', 'frequence' => '2 fois par jour', 'duree' => '3 jours', 'amount' => 25000]
        );

        $this->motif = MotifRdv::firstOrCreate(
            ['code' => 'essai-consultation'],
            [
                'department_id' => $this->departement->id, 'nom' => 'Consultation (essai)',
                'duree_minutes_defaut' => 20, 'service_id' => $this->acteConsultation->id, 'actif' => true,
            ]
        );

        $this->line('  Clinique   : ' . $this->clinique->nom);
        $this->line('  Laboratoire: ' . $this->laboratoire->nom);
        $this->line('  Médecin    : Dr ' . $this->medecin->full_name);
        $this->line('  Utilisateur: ' . $this->utilisateur->name);
    }

    private function patient(string $prenom, string $sexe = 'Femme'): Patient
    {
        $patient = Patient::create([
            'first_name' => $prenom,
            'last_name' => 'Essai ' . Str::upper(Str::random(4)),
            'gender' => $sexe,
            'birth_date' => now()->subYears(30)->toDateString(),
        ]);

        $this->clinique->patients()->syncWithoutDetaching([$patient->id]);

        return $patient;
    }

    private function recevoir(string $prenom): Visite
    {
        return app(AccueilService::class)->arriveeSansRendezVous(
            $this->patient($prenom),
            $this->medecin,
            ['service_id' => $this->acteConsultation->id, 'motif' => 'Essai automatisé'],
            $this->utilisateur
        );
    }

    private function moduleLaboActif(Etablissement $etablissement): bool
    {
        return $etablissement->modules()
            ->where('code', 'laboratoire')
            ->wherePivot('est_actif', true)
            ->exists();
    }

    /** Un traitement qui DOIT être refusé : renvoie vrai si une exception est levée. */
    private function refuse(callable $traitement): bool
    {
        try {
            $traitement();

            return false;
        } catch (Throwable) {
            return true;
        }
    }

    private function remplacerServiceSms(): void
    {
        $compteur = function (): void {
            $this->smsSimules++;
        };

        app()->instance(SmsService::class, new class($compteur) extends SmsService {
            public function __construct(private $compteur)
            {
            }

            public function sendSms(string $phoneNumber, string $message, array|string|null $contexte = []): array
            {
                ($this->compteur)();

                return ['success' => true, 'message_id' => 'essai'];
            }
        });
    }

    private function noter(string $cas, string $libelle, bool $ok, string $detail = ''): void
    {
        $this->resultats[] = compact('cas', 'libelle', 'ok', 'detail');

        $this->line(sprintf(
            '  %s %s%s',
            $ok ? '<fg=green>✓</>' : '<fg=red>✗</>',
            $libelle,
            $detail !== '' ? ' <fg=gray>(' . $detail . ')</>' : ''
        ));
    }

    private function afficherBilan(): int
    {
        $echecs = array_filter($this->resultats, fn ($r) => ! $r['ok']);

        $this->newLine();
        $this->line('<fg=cyan>── BILAN ──</>');
        $this->line(sprintf('  %d contrôle(s), %d réussi(s), %d en échec.',
            count($this->resultats), count($this->resultats) - count($echecs), count($echecs)));
        $this->line('  ' . $this->smsSimules . ' SMS simulé(s), aucun envoi réel.');
        $this->line('  ' . ($this->option('garder') ? 'Données conservées.' : 'Données annulées : la base est intacte.'));

        if ($echecs) {
            $this->newLine();
            $this->table(['Cas', 'Contrôle', 'Détail'], array_map(
                fn ($r) => [$r['cas'], $r['libelle'], $r['detail']],
                $echecs
            ));

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Parcours complet : tout est conforme.');

        return self::SUCCESS;
    }
}
