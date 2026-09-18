<?php

namespace App\Console\Commands\Demo;

use App\Enums\Labo\TypeResultat;
use App\Models\Chambre;
use App\Models\ComptePatient;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\InsuranceCompany;
use App\Models\Labo\LaboDemande;
use App\Models\Labo\LaboDemandeExamen;
use App\Models\Labo\LaboExamen;
use App\Models\Module;
use App\Models\Patient;
use App\Models\Role;
use App\Models\Service;
use App\Models\Test;
use App\Models\User;
use App\Services\Labo\CatalogueImportService;
use App\Services\Labo\CompteRenduService;
use App\Services\Labo\DemandeService;
use App\Services\Labo\PrelevementService;
use App\Services\Labo\ResultatService;
use App\Services\Labo\ValidationService;
use App\Support\Labo\SelecteurValeurReference;
use Database\Seeders\Labo\LaboCatalogueModeleSeeder;
use Database\Seeders\Labo\LaboPermissionsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Établissement de démonstration complet, pour la recette.
 *
 *   php artisan hali:demo clinique-demo-a --nom="Clinique Démo Kaloum"
 *   php artisan hali:demo clinique-demo-b --nom="Clinique Démo Ratoma"
 *
 * Deux établissements = de quoi tester le cloisonnement (Priorité 1).
 * Relançable : les éléments existants sont réutilisés, les demandes labo
 * sont recréées à chaque exécution (option --sans-labo pour les ignorer).
 *
 * Refuse de tourner en production sans --force.
 */
class GenererDemo extends Command
{
    protected $signature = 'hali:demo
        {slug=clinique-demo : identifiant de l\'établissement de démo}
        {--nom= : nom affiché}
        {--sans-labo : ne pas créer de demandes d\'analyses}
        {--force : autoriser en production}';

    /**
     * Ancien nom, conservé : il figure dans les tâches cron déjà installées
     * chez les clients. À retirer quand tous les serveurs seront à jour.
     */
    protected $aliases = ['aprosafe:demo'];

    protected $description = 'Crée un établissement de démonstration complet : comptes, patients, parcours, caisse, assurance, laboratoire et réseau';

    public const MOT_DE_PASSE = 'Demo@2026';

    private Etablissement $etab;
    private array $comptes = [];
    private array $patients = [];
    private Service $acteConsultation;
    private Service $acteMaternite;
    private \App\Models\Medicament $medicament;
    private \App\Models\MotifRdv $motif;

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Environnement de production : relancez avec --force si c\'est vraiment voulu.');

            return self::FAILURE;
        }

        $slug = Str::slug($this->argument('slug'));
        $nom = $this->option('nom') ?: 'Clinique ' . Str::title(str_replace('-', ' ', $slug));

        $this->etab = Etablissement::firstOrCreate(
            ['slug' => $slug],
            ['nom' => $nom, 'type' => 'clinique', 'statut' => 'essai', 'adresse' => 'Conakry', 'contact' => '620 00 00 00', 'email' => "contact@{$slug}.demo"]
        );
        $this->info("🏥 {$this->etab->nom} (#{$this->etab->id})");

        $this->activerModules();
        $this->creerComptes();

        // Toute la suite s'exécute « comme » l'administrateur de la clinique :
        // le cloisonnement et la journalisation fonctionnent comme dans l'appli.
        Auth::guard('web')->setUser($this->comptes['admin']['user'] ?? reset($this->comptes)['user']);

        $this->creerReferentiels();
        $this->creerPatients();

        $this->circuitParcours();
        $this->circuitAssurance();
        $this->circuitCaisse();

        if (! $this->option('sans-labo') && class_exists(LaboDemande::class)) {
            $this->circuitLabo();
            $this->circuitReseau();
        }

        $this->afficherComptes();

        return self::SUCCESS;
    }

    // ------------------------------------------------------------------

    private function activerModules(): void
    {
        foreach (['rdv', 'consultation', 'hospitalisation', 'assurance', 'laboratoire'] as $code) {
            if ($module = Module::where('code', $code)->first()) {
                $this->etab->modules()->syncWithoutDetaching([$module->id => ['est_actif' => true, 'active_depuis' => now()]]);
            }
        }

        // Permissions des modules livrés après le laboratoire : sans elles, les
        // comptes de démonstration voient des menus vides.
        foreach ([
            \Database\Seeders\Assurance\AssurancePermissionsSeeder::class,
            \Database\Seeders\Parcours\ParcoursPermissionsSeeder::class,
            \Database\Seeders\Labo\LaboReseauPermissionsSeeder::class,
            \Database\Seeders\Rapports\RapportsPermissionsSeeder::class,
        ] as $seeder) {
            if (class_exists($seeder)) {
                $this->callSilent('db:seed', ['--class' => $seeder, '--force' => true]);
            }
        }

        if (class_exists(LaboDemande::class)) {
            $this->callSilent('db:seed', ['--class' => LaboPermissionsSeeder::class, '--force' => true]);
            if (! LaboExamen::withoutGlobalScopes()->whereNull('etablissement_id')->exists()) {
                $this->callSilent('db:seed', ['--class' => LaboCatalogueModeleSeeder::class, '--force' => true]);
            }
            app(CatalogueImportService::class)->importer($this->etab->id);

            // Prix de démonstration (le catalogue modèle est à 0).
            LaboExamen::withoutGlobalScopes()->where('etablissement_id', $this->etab->id)->where('prix', 0)->get()
                ->each(fn ($e) => $e->forceFill(['prix' => [25000, 35000, 50000, 75000, 120000][crc32($e->code) % 5]])->saveQuietly());
        }
        $this->line('  ✔ Modules activés, catalogue labo importé avec prix de démo');
    }

    private function creerComptes(): void
    {
        $departement = Department::withoutGlobalScopes()->firstOrCreate(
            ['name' => 'MEDECINE GENERALE', 'etablissement_id' => $this->etab->id]
        );

        $profils = [
            'admin' => ['Admin', 'Diallo', 'Admin', 'admin'],
            'secretaire' => ['Fatoumata', 'Camara', 'Secretary', 'secretaire'],
            'medecin' => ['Dr Alpha', 'Barry', 'Doctor', 'medecin'],
            'comptable' => ['Kadiatou', 'Sylla', 'Accountant', 'comptable'],
            'accueil_labo' => ['Mariama', 'Bah', 'Reception', 'Accueil laboratoire'],
            'preleveur' => ['Ousmane', 'Keita', 'Nurse', 'Préleveur'],
            'technicien' => ['Aïssatou', 'Soumah', 'Laboratory', 'Technicien de laboratoire'],
            'biologiste' => ['Dr Mamadou', 'Condé', 'Laboratory', 'Biologiste'],
        ];

        $base = sprintf('62%05d', crc32($this->etab->slug) % 100000);
        $i = 0;

        foreach ($profils as $cle => [$prenom, $nom, $type, $role]) {
            $telephone = $base . str_pad((string) (++$i), 2, '0', STR_PAD_LEFT);

            $user = User::firstOrCreate(
                ['phone' => $telephone],
                ['name' => "{$prenom} {$nom}", 'email' => "{$cle}@{$this->etab->slug}.demo", 'password' => Hash::make(self::MOT_DE_PASSE)]
            );
            $user->forceFill(['etablissement_id' => $this->etab->id, 'login_attempts' => 0, 'locked_until' => null])->save();

            if (Role::where('name', $role)->exists()) {
                $user->syncRoles([$role]);
            } else {
                $this->warn("  ⚠ Rôle « {$role} » inexistant (lancez PermissionSeeder) — compte {$cle} sans rôle");
            }

            Employee::withoutGlobalScopes()->updateOrCreate(
                ['user_id' => $user->id],
                ['etablissement_id' => $this->etab->id, 'first_name' => $prenom, 'last_name' => $nom, 'address' => 'Conakry',
                 'type' => $type, 'department_id' => $departement->id, 'speciality' => $departement->name, 'is_active' => true]
            );

            $this->comptes[$cle] = ['user' => $user, 'role' => $role];
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $this->line('  ✔ ' . count($this->comptes) . ' comptes du personnel');
    }

    private function creerReferentiels(): void
    {
        $departement = Department::first();

        foreach (['101' => 150000, '102' => 150000, '201' => 300000] as $numero => $prix) {
            Chambre::firstOrCreate(['numero' => $numero], ['prix_par_jour' => $prix, 'type' => $prix > 200000 ? 'VIP' : 'Standard']);
        }
        Service::firstOrCreate(['name' => 'Consultation générale'], ['amount' => 100000, 'department_id' => $departement?->id]);
        Test::firstOrCreate(['name' => 'Glycémie capillaire'], ['report_type' => 'numerique', 'amount' => 15000]);
        InsuranceCompany::firstOrCreate(['code' => 'NSIA'], ['name' => 'NSIA Assurances', 'default_coverage_percentage' => 80]);

        // Actes classés par famille : c'est la famille qui décide de la prise
        // en charge par l'assurance.
        $this->acteConsultation = Service::firstOrCreate(
            ['name' => 'Consultation générale'],
            ['amount' => 100000, 'department_id' => $departement?->id]
        );
        $this->acteConsultation->forceFill(['famille_acte' => 'consultation'])->saveQuietly();

        $this->acteMaternite = Service::firstOrCreate(
            ['name' => 'Consultation prénatale'],
            ['amount' => 80000, 'department_id' => $departement?->id]
        );
        $this->acteMaternite->forceFill(['famille_acte' => 'maternite'])->saveQuietly();

        $this->medicament = \App\Models\Medicament::firstOrCreate(
            ['nom' => 'Artéméther-luméfantrine'],
            ['forme' => 'comprimé', 'dosage' => '20/120 mg', 'frequence' => '2 fois par jour', 'duree' => '3 jours', 'amount' => 25000]
        );

        $this->motif = \App\Models\MotifRdv::firstOrCreate(
            ['code' => 'consultation'],
            ['department_id' => $departement?->id, 'nom' => 'Consultation', 'duree_minutes_defaut' => 20,
             'service_id' => $this->acteConsultation->id, 'actif' => true, 'ordre_affichage' => 1]
        );

        \App\Models\MotifRdv::firstOrCreate(
            ['code' => 'cpn'],
            ['department_id' => $departement?->id, 'nom' => 'Consultation prénatale', 'duree_minutes_defaut' => 25,
             'service_id' => $this->acteMaternite->id, 'actif' => true, 'ordre_affichage' => 2]
        );

        $this->line('  ✔ Chambres, actes (consultation, CPN), médicament, motifs de rendez-vous, assurance NSIA');
    }

    private function creerPatients(): void
    {
        // Une patiente par circuit : un patient déjà dans la file du jour ne
        // peut pas y être remis (verrou anti-double arrivée).
        $fiches = [
            'femme' => ['Aminata', 'Touré', 'Femme', now()->subYears(32)->format('Y-m-d')],
            'homme' => ['Mamadou', 'Sow', 'Homme', now()->subYears(58)->format('Y-m-d')],
            'enfant' => ['Ibrahima', 'Bangoura', 'Homme', now()->subYears(3)->format('Y-m-d')],
            'sans_age' => ['Sékou', 'Kourouma', 'Homme', null],
            'enceinte' => ['Kadiatou', 'Diallo', 'Femme', now()->subYears(27)->format('Y-m-d')],
            'assuree' => ['Fatoumata', 'Baldé', 'Femme', now()->subYears(41)->format('Y-m-d')],
        ];

        $base = sprintf('66%05d', crc32($this->etab->slug) % 100000);
        $i = 0;

        foreach ($fiches as $cle => [$prenom, $nom, $genre, $naissance]) {
            $telephone = $base . str_pad((string) (++$i), 2, '0', STR_PAD_LEFT);

            $patient = DB::transaction(function () use ($telephone, $prenom, $nom, $genre, $naissance) {
                $compte = ComptePatient::firstOrCreate(['telephone' => $telephone], ['statut' => 'actif']);
                $patient = $compte->patients()->wherePivot('role', 'titulaire')->first();

                if (! $patient) {
                    $patient = Patient::create([
                        'first_name' => $prenom, 'last_name' => $nom, 'gender' => $genre,
                        'birth_date' => $naissance, 'age' => $naissance ? now()->diffInYears($naissance, true) : null,
                    ]);
                    $compte->patients()->attach($patient->id, ['role' => 'titulaire']);
                }

                $this->etab->patients()->syncWithoutDetaching([$patient->id => ['premiere_visite_le' => now(), 'derniere_visite_le' => now()]]);

                return $patient;
            });

            $this->patients[$cle] = $patient;
        }

        $this->line('  ✔ 6 patients : femme 32 ans, homme 58 ans, enfant 3 ans, homme sans âge, femme enceinte 27 ans, femme assurée 41 ans');
    }

    // ------------------------------------------------------------------ Parcours

    /** File d'attente du jour, consultations, grossesse et documents. */
    private function circuitParcours(): void
    {
        $medecinUser = $this->comptes['medecin']['user'] ?? Auth::user();
        $medecin = Employee::where('user_id', $medecinUser->id)->first();

        if (! $medecin) {
            $this->warn('  ⚠ Pas de médecin : circuit parcours ignoré.');

            return;
        }

        $this->newLine();
        $this->info('🚶 Parcours patient');

        $accueil = app(\App\Services\Parcours\AccueilService::class);

        $this->etape('1. En attente, constantes prises', function () use ($accueil, $medecin) {
            $visite = $accueil->arriveeSansRendezVous($this->patients['femme'], $medecin,
                ['service_id' => $this->acteConsultation->id, 'motif' => 'Fièvre depuis 3 jours'], Auth::user());

            $accueil->enregistrerConstantes($visite, [
                'temperature' => 38.7, 'tension_systolique' => 120, 'tension_diastolique' => 80,
                'pouls' => 92, 'poids_kg' => 62,
            ], Auth::user());

            return $visite;
        });

        $this->etape('2. URGENCE en tête de file', fn () => $accueil->arriveeSansRendezVous(
            $this->patients['homme'], $medecin,
            ['service_id' => $this->acteConsultation->id, 'motif' => 'Douleur thoracique', 'urgence' => true],
            Auth::user()
        ));

        $this->etape('3. Consultation terminée avec ordonnance et prochain rendez-vous', function () use ($accueil, $medecin) {
            $visite = $accueil->arriveeSansRendezVous($this->patients['enfant'], $medecin,
                ['service_id' => $this->acteConsultation->id, 'motif' => 'Toux'], Auth::user());

            $accueil->appeler($visite->fresh(), $medecin);

            app(\App\Services\Parcours\ConsultationRapideService::class)->enregistrer($visite->consultation->fresh(), [
                'action' => 'terminer',
                'diagnostic' => 'Infection respiratoire haute',
                'signes' => ['Toux', 'Fièvre'],
                'actes' => [
                    'services' => [['id' => $this->acteConsultation->id]],
                    'medicaments' => [[
                        'id' => $this->medicament->id, 'quantite' => 1,
                        'dose' => '1 cuillère', 'frequence' => '2 fois par jour', 'duree' => '5 jours',
                    ]],
                ],
                'prochain_rdv_jours' => 7,
                'prochain_rdv_motif_id' => $this->motif->id,
            ], Auth::user());

            return $visite->fresh();
        });

        $this->etape('4. Reparti sans consulter (facture annulée)', function () use ($accueil, $medecin) {
            $visite = $accueil->arriveeSansRendezVous($this->patients['sans_age'], $medecin,
                ['service_id' => $this->acteConsultation->id, 'motif' => 'Contrôle'], Auth::user());

            $accueil->marquerPartie($visite->fresh(), 'Attente trop longue');

            return $visite->fresh();
        });

        $this->etape('5. Grossesse suivie, CPN rattachée', function () use ($accueil, $medecin) {
            $grossesse = app(\App\Services\Parcours\GrossesseService::class)->ouvrir(
                $this->patients['enceinte'], now()->subWeeks(28), ['medecin_id' => $medecin->id, 'gestite' => 2, 'parite' => 1], Auth::user()
            );

            $cpn = $accueil->arriveeSansRendezVous($this->patients['enceinte'], $medecin,
                ['service_id' => $this->acteMaternite->id, 'motif' => 'CPN'], Auth::user());

            app(\App\Services\Parcours\GrossesseService::class)->rattacher($cpn->consultation);
            $accueil->appeler($cpn->fresh(), $medecin);
            $cpn->consultation->update(['diagnostic' => 'Grossesse évolutive, RAS']);
            $accueil->terminer($cpn->fresh());

            return $grossesse;
        });

        $this->etape('6. Certificat médical et arrêt de travail', function () {
            $consultation = \App\Models\Consultation::whereNotNull('diagnostic')->latest('id')->first();
            $documents = app(\App\Services\Parcours\DocumentMedicalService::class);

            $documents->creer($this->patients['homme'], \App\Enums\Parcours\TypeDocumentMedical::CertificatMedical, [
                'contenu' => $documents->proposerTexte(\App\Enums\Parcours\TypeDocumentMedical::CertificatMedical,
                    $this->patients['homme'], ['motif' => 'un état de santé compatible avec la reprise du travail']),
            ], $consultation, Auth::user());

            return $documents->creer($this->patients['homme'], \App\Enums\Parcours\TypeDocumentMedical::ArretTravail, [
                'contenu' => $documents->proposerTexte(\App\Enums\Parcours\TypeDocumentMedical::ArretTravail,
                    $this->patients['homme'], ['jours' => 3, 'date_debut' => today()->toDateString(), 'motif' => 'paludisme simple']),
                'date_debut' => today()->toDateString(),
                'jours' => 3,
            ], $consultation, Auth::user());
        });
    }

    // ------------------------------------------------------------------ Assurance

    /** Patiente assurée, facture partagée, bordereau envoyé et règlement reçu. */
    private function circuitAssurance(): void
    {
        $this->newLine();
        $this->info('🛡  Assurance');

        $organisme = InsuranceCompany::firstOrCreate(['code' => 'NSIA'], ['name' => 'NSIA Assurances', 'type' => 'assureur']);

        $this->etape('1. Convention : consultations et maternité couvertes', function () use ($organisme) {
            foreach (['consultation' => 0, 'maternite' => 5, 'laboratoire' => 10] as $famille => $remise) {
                \App\Models\Assurance\ConventionFamille::updateOrCreate(
                    ['insurance_company_id' => $organisme->id, 'famille_acte' => $famille],
                    ['remise_pourcentage' => $remise, 'valid_from' => now()->subYear()->toDateString(), 'actif' => true]
                );
            }

            return $organisme;
        });

        $this->etape('2. Patiente couverte à 80 %', fn () => \App\Models\PatientInsurance::firstOrCreate(
            ['patient_id' => $this->patients['assuree']->id, 'insurance_company_id' => $organisme->id],
            [
                'policy_number' => 'NSIA-DEMO-001', 'coverage_percentage' => 80,
                'annual_limit' => 5000000, 'start_date' => now()->subMonths(6), 'end_date' => now()->addMonths(6),
                'status' => 'active',
            ]
        ));

        $reclamation = null;

        $this->etape('3. Consultation facturée : part assureur et part patiente', function () use (&$reclamation) {
            $medecinUser = $this->comptes['medecin']['user'] ?? Auth::user();
            $medecin = Employee::where('user_id', $medecinUser->id)->first();

            $visite = app(\App\Services\Parcours\AccueilService::class)->arriveeSansRendezVous(
                $this->patients['assuree'], $medecin,
                ['service_id' => $this->acteConsultation->id, 'motif' => 'Suivi (assurée)'], Auth::user()
            );

            $facture = $visite->consultation->transaction()->first()?->invoice()->first();
            $reclamation = \App\Models\InsuranceClaim::where('invoice_id', $facture?->id)->first();

            return $facture;
        });

        if (! $reclamation) {
            $this->line('  <fg=yellow>Pas de réclamation générée : bordereau et règlement ignorés.</>');

            return;
        }

        $this->etape('4. Bordereau du mois envoyé à l\'assureur', function () use ($organisme) {
            $bordereau = app(\App\Services\Assurance\BordereauService::class)->creer(
                $organisme, now()->startOfMonth(), now()->endOfMonth(), 'Bordereau de démonstration', Auth::id()
            );

            return app(\App\Services\Assurance\BordereauService::class)->envoyer($bordereau, now());
        });

        $this->etape('5. Réponse de l\'assureur puis règlement partiel', function () use ($organisme, $reclamation) {
            $reclamation = $reclamation->fresh('lignes');

            $reponses = $reclamation->lignes->mapWithKeys(fn ($ligne) => [
                $ligne->id => ['montant_accepte' => round((float) $ligne->montant_reclame * 0.9), 'motif_rejet' => 'Tarif conventionné'],
            ])->all();

            app(\App\Services\Assurance\ReclamationService::class)->enregistrerReponse($reclamation, $reponses, 'Règlement partiel de démonstration');

            return app(\App\Services\Assurance\ReglementAssuranceService::class)->regler($organisme, (float) $reclamation->fresh()->approved_amount, [], [
                'mode' => 'virement', 'reference' => 'VIR-DEMO-001', 'recu_le' => today()->toDateString(),
            ]);
        });
    }

    // ------------------------------------------------------------------ Caisse

    /** Encaissements de démonstration : soldé, partiel, impayé. */
    private function circuitCaisse(): void
    {
        $this->newLine();
        $this->info('💰 Caisse');

        $transactions = \App\Models\Transaction::where('status', '!=', 'cancel')->latest('id')->take(3)->get();

        if ($transactions->isEmpty()) {
            $this->line('  <fg=yellow>Aucune facture à encaisser.</>');

            return;
        }

        $paiements = app(\App\Services\PaymentService::class);

        $this->etape('1. Facture soldée en espèces', function () use ($paiements, $transactions) {
            $transaction = $transactions->first();
            $solde = \App\Support\Facturation\SoldeTransaction::pour($transaction);

            return $solde->resteDuPatient() > 0
                ? $paiements->payPatientTransaction($transaction, $solde->resteDuPatient(), 'CASH')
                : null;
        });

        $this->etape('2. Paiement partiel (reste dû visible en caisse)', function () use ($paiements, $transactions) {
            $transaction = $transactions->get(1);

            if (! $transaction) {
                return null;
            }

            $solde = \App\Support\Facturation\SoldeTransaction::pour($transaction);

            return $solde->resteDuPatient() > 1
                ? $paiements->payPatientTransaction($transaction, round($solde->resteDuPatient() / 2), 'MOBILE_MONEY')
                : null;
        });

        $this->line('  ✔ Une facture soldée, une partielle, le reste impayé pour l\'écran des restes à payer');
    }

    // ------------------------------------------------------------------ Réseau

    /** Partenariat avec une autre clinique de démonstration, si elle existe. */
    private function circuitReseau(): void
    {
        if (! class_exists(\App\Models\Labo\LaboPartenariat::class)) {
            return;
        }

        $clinique = Etablissement::where('id', '!=', $this->etab->id)->where('type', 'clinique')->first();

        if (! $clinique) {
            $this->newLine();
            $this->line('  <fg=yellow>Réseau : lancez la commande sur un second établissement (slug différent) pour voir le circuit inter-établissements.</>');

            return;
        }

        $this->newLine();
        $this->info('🔗 Laboratoire en réseau avec ' . $clinique->nom);

        // Un examen à prix zéro est refusé à l'envoi (lot 4e) : on s'assure que
        // le catalogue de ce laboratoire est tarifé.
        LaboExamen::withoutGlobalScopes()
            ->where('etablissement_id', $this->etab->id)
            ->where('actif', true)
            ->where('prix', '<=', 0)
            ->get()
            ->each(fn ($examen) => $examen->forceFill(['prix' => 25000])->saveQuietly());

        $bio = $this->comptes['biologiste']['user'] ?? Auth::user();
        $medecinDistant = User::where('etablissement_id', $clinique->id)->first();

        if (! $medecinDistant) {
            $this->line('  <fg=yellow>Aucun compte dans l\'autre établissement : circuit réseau ignoré.</>');

            return;
        }

        $this->etape('1. Partenariat proposé puis accepté', function () use ($clinique, $bio, $medecinDistant) {
            Auth::guard('web')->setUser($bio);

            $partenariat = \App\Models\Labo\LaboPartenariat::where('clinique_id', $clinique->id)->first()
                ?? app(\App\Services\Labo\PartenariatService::class)->creer($clinique, [
                    'mode_facturation_defaut' => 'partenaire', 'clinique_facture_patient' => true,
                    'remise_pourcentage' => 10, 'delai_paiement_jours' => 30,
                    'contact_nom' => 'Comptabilité', 'contact_telephone' => '620000000',
                ], $bio);

            if ($partenariat->estPropose()) {
                Auth::guard('web')->setUser($medecinDistant);
                app(\App\Services\Labo\LaboReseauService::class)->accepterProposition($partenariat->id, $medecinDistant);
            }

            return $partenariat->fresh();
        });

        $this->etape('2. Demande envoyée par la clinique partenaire', function () use ($clinique, $medecinDistant, $bio) {
            Auth::guard('web')->setUser($medecinDistant);

            $reseau = app(\App\Services\Labo\LaboReseauService::class);
            $partenariat = $reseau->partenariat($reseau->partenaires()->firstOrFail()->id);

            $patient = $clinique->patients()->first()
                ?? Patient::create(['first_name' => 'Patient', 'last_name' => 'Partenaire', 'gender' => 'Homme', 'birth_date' => now()->subYears(40)->toDateString()]);
            $clinique->patients()->syncWithoutDetaching([$patient->id]);

            // On prend l'examen tel que la CLINIQUE le voit : c'est la seule
            // liste dont l'envoi accepte les identifiants (catalogue du
            // partenaire, actif, tarifé).
            $ligne = $reseau->catalogue($partenariat)->firstWhere('prix', '>', 0)
                ?? $reseau->catalogue($partenariat)->first();

            if (! $ligne) {
                throw new \RuntimeException('Le catalogue du laboratoire est vide ou entièrement à zéro.');
            }

            $demande = $reseau->envoyer($partenariat, $patient, [
                'examens' => [$ligne['id']],
                'renseignements_cliniques' => 'Bilan de démonstration',
                'consentement_partage' => true,
            ], $medecinDistant);

            Auth::guard('web')->setUser($bio);

            return $demande;
        });

        $this->etape('3. Relevé envoyé à la clinique partenaire', function () use ($clinique, $bio) {
            Auth::guard('web')->setUser($bio);

            $facturation = app(\App\Services\Labo\FacturationPartenaireService::class);

            // Le partenariat du laboratoire courant avec CETTE clinique, pas le
            // premier venu : une plateforme en compte plusieurs.
            $partenariat = \App\Models\Labo\LaboPartenariat::where('clinique_id', $clinique->id)->firstOrFail();

            $releve = $facturation->preparerReleve($partenariat, now()->startOfMonth(), now()->endOfMonth(), Auth::user());

            return $facturation->envoyerReleve($releve);
        });
    }

    // ------------------------------------------------------------------ Labo

    private function circuitLabo(): void
    {
        $bio = $this->comptes['biologiste']['user'] ?? Auth::user();
        Auth::guard('web')->setUser($bio);

        $this->newLine();
        $this->info('🧪 Circuit laboratoire');

        $f = $this->patients['femme'];
        $h = $this->patients['homme'];

        $this->etape('1. Enregistrée (à prélever)', fn () => $this->demande($f, ['NFS', 'VS', 'GLY']));

        $this->etape('2. Prélevée, pas encore reçue', function () use ($h) {
            $d = $this->demande($h, ['GLY', 'CREA']);
            $d->echantillons->each(fn ($e) => app(PrelevementService::class)->marquerPreleve($e, Auth::user()));

            return $d;
        });

        $this->etape('3. Reçue, à saisir à la paillasse', fn () => $this->recevoir($this->demande($f, ['LIPIDES'])));

        $this->etape('4. URGENTE — valeur CRITIQUE non signalée (Hb 5,0)', function () use ($h) {
            $d = $this->recevoir($this->demande($h, ['NFS'], urgence: true));
            $this->saisir($this->ligne($d, 'NFS'), ['HB' => '5,0']);

            return $d;
        });

        $this->etape('5. Validée techniquement, attend le biologiste', function () use ($f) {
            $d = $this->recevoir($this->demande($f, ['GLY', 'UREE']));
            foreach ($d->examens as $l) {
                $this->saisir($l);
                app(ValidationService::class)->validerTechnique($l->fresh(), Auth::user());
            }

            return $d;
        });

        $this->etape('6. Validée biologiquement, à publier', function () use ($h) {
            $d = $this->recevoir($this->demande($h, ['LIPIDES']));
            $this->valider($d);

            return $d;
        });

        $this->etape('7. Publiée (compte rendu v1)', function () use ($f) {
            $d = $this->recevoir($this->demande($f, ['NFS', 'GLY']));
            $this->valider($d);
            app(CompteRenduService::class)->publier($d->fresh(), Auth::user(), notifierPatient: false);

            return $d;
        });

        $this->etape('8. RECTIFIÉE (v1 puis v2 rectificatif)', function () use ($h) {
            $d = $this->recevoir($this->demande($h, ['GLY']));
            $this->valider($d, ['GLY' => '0,95']);
            app(CompteRenduService::class)->publier($d->fresh(), Auth::user(), notifierPatient: false);

            $l = $this->ligne($d, 'GLY');
            app(ValidationService::class)->rouvrirPourRectification($l, 'Erreur de retranscription : 1,95 et non 0,95', Auth::user());
            $this->saisir($l->fresh(), ['GLY' => '1,95']);
            app(ValidationService::class)->validerTechniqueEtBiologique($l->fresh(), Auth::user());
            app(CompteRenduService::class)->publier($d->fresh(), Auth::user(), notifierPatient: false);

            return $d;
        });

        $this->etape('9. Enfant 3 ans — normes « non définies »', function () {
            $d = $this->recevoir($this->demande($this->patients['enfant'], ['NFS']));
            $this->saisir($this->ligne($d, 'NFS'));

            return $d;
        });

        $this->etape('10. Échantillon REJETÉ (hémolysé) → re-prélèvement', function () {
            $d = $this->demande($this->patients['sans_age'], ['GLY']);
            $e = $d->echantillons->first();
            app(PrelevementService::class)->marquerPreleve($e, Auth::user());
            app(PrelevementService::class)->rejeter($e->fresh(), 'hemolyse', 'Tube hémolysé à la réception', Auth::user());

            return $d;
        });

        $this->etape('11. Facturée, NON réglée (résultats retenus)', fn () => $this->demande($f, ['VS'], facturer: true));
    }

    /**
     * Une étape de démonstration. Le retour sert seulement à l'affichage :
     * un numéro s'il existe (demande, document, relevé), sinon l'identifiant,
     * et rien du tout si l'étape n'avait rien à créer.
     */
    private function etape(string $libelle, callable $action): void
    {
        try {
            $resultat = $action();

            $reference = match (true) {
                is_object($resultat) && isset($resultat->numero) => $resultat->numero,
                is_object($resultat) && isset($resultat->id) => '#' . $resultat->id,
                default => '',
            };

            $this->line(trim("  ✔ {$libelle} → {$reference}", ' →'));
        } catch (\Throwable $e) {
            $this->warn("  ✖ {$libelle} : " . $e->getMessage());
            report($e);
        }
    }

    private function demande(Patient $patient, array $codes, bool $urgence = false, bool $facturer = false): LaboDemande
    {
        return app(DemandeService::class)->creer([
            'patient_id' => $patient->id,
            'origine' => 'interne',
            'prescripteur_employee_id' => Employee::where('type', 'Doctor')->value('id'),
            'renseignements_cliniques' => 'Données de démonstration',
            'urgence' => $urgence,
            'a_jeun_confirme' => true,
            'mode_facturation' => $facturer ? 'labo' : 'gratuit',
            'resultats_retenus_si_impaye' => true,
            'examens' => LaboExamen::whereIn('code', $codes)->pluck('id')->all(),
        ], Auth::user());
    }

    private function recevoir(LaboDemande $demande): LaboDemande
    {
        foreach ($demande->echantillons as $e) {
            app(PrelevementService::class)->marquerPreleveEtRecu($e, Auth::user());
        }

        return $demande->fresh(['examens', 'echantillons']);
    }

    private function ligne(LaboDemande $demande, string $code): LaboDemandeExamen
    {
        return $demande->examens()->whereHas('examen', fn ($q) => $q->where('code', $code))->firstOrFail();
    }

    private function valider(LaboDemande $demande, array $valeurs = []): void
    {
        foreach ($demande->fresh()->examens as $l) {
            $this->saisir($l, $valeurs);
            app(ValidationService::class)->validerTechniqueEtBiologique($l->fresh(), Auth::user());
        }
    }

    /** Remplit chaque paramètre avec une valeur normale plausible, sauf valeurs imposées (par code). */
    private function saisir(LaboDemandeExamen $ligne, array $imposees = []): void
    {
        $ligne->loadMissing('examen.parametres.valeursReference', 'demande.patient');
        $patient = $ligne->demande->patient;
        $sexe = $patient->gender === 'Femme' ? 'F' : 'M';
        $saisies = [];

        foreach ($ligne->examen->parametres as $p) {
            if ($p->type_resultat === TypeResultat::CALCULE) {
                continue;
            }
            if (array_key_exists($p->code, $imposees)) {
                $saisies[$p->id] = $imposees[$p->code];
                continue;
            }

            $plage = SelecteurValeurReference::choisir($p->valeursReference, $sexe, null) ?? $p->valeursReference->first();

            $saisies[$p->id] = match (true) {
                $p->type_resultat === TypeResultat::NUMERIQUE => $this->valeurNormale($plage),
                $p->type_resultat === TypeResultat::TEXTE => 'RAS',
                default => $plage?->valeur_attendue ?: ($p->options[0] ?? 'Négatif'),
            };
        }

        app(ResultatService::class)->enregistrer($ligne, $saisies, Auth::user(), 'demo');
    }

    private function valeurNormale($plage): string
    {
        $v = match (true) {
            $plage?->min !== null && $plage?->max !== null => ($plage->min + $plage->max) / 2,
            $plage?->max !== null => $plage->max * 0.7,
            $plage?->min !== null => $plage->min * 1.3,
            default => 1,
        };

        return str_replace('.', ',', (string) round($v, 2));
    }

    private function afficherComptes(): void
    {
        $this->newLine();
        $this->info('🔑 Comptes (connexion par TÉLÉPHONE) — mot de passe : ' . self::MOT_DE_PASSE);
        $this->table(['Profil', 'Téléphone', 'Rôle'], collect($this->comptes)->map(fn ($c, $k) => [$k, $c['user']->phone, $c['role']])->values()->all());

        $this->newLine();
        $this->info('📋 Ce qui vous attend dans l\'application');
        $this->line('  • Accueil du jour : un patient en attente avec ses constantes, une urgence en tête, un reparti');
        $this->line('  • Ma file d\'attente : une consultation déjà terminée, avec ordonnance et prochain rendez-vous');
        $this->line('  • Grossesses suivies : un suivi en cours avec sa première CPN rattachée');
        $this->line('  • Documents du patient : un certificat médical et un arrêt de travail numérotés');
        $this->line('  • Caisse : une facture soldée, une partielle, une impayée');
        $this->line('  • Assurances : convention, patiente couverte à 80 %, bordereau envoyé, règlement partiel');
        $this->line('  • Laboratoire : neuf demandes couvrant tous les états du circuit');
        $this->line('  • Rapports : activité, recettes, impayés, créances — tous alimentés');
        $this->line('  • Réseau : relancez la commande avec un autre slug pour voir le circuit entre deux établissements');
    }
}
