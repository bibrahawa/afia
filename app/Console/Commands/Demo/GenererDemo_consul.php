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
class GenererDemo_consul extends Command
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

    protected $description = 'Crée un établissement de démonstration (comptes par rôle, patients, circuit labo complet)';

    public const MOT_DE_PASSE = 'Demo@2026';

    private Etablissement $etab;
    private array $comptes = [];
    private array $patients = [];

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

        if (! $this->option('sans-labo') && class_exists(LaboDemande::class)) {
            $this->circuitLabo();
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

        $this->line('  ✔ Chambres 101/102/201, service, test, assurance NSIA (mêmes codes dans chaque clinique de démo)');
    }

    private function creerPatients(): void
    {
        $fiches = [
            'femme' => ['Aminata', 'Touré', 'Femme', now()->subYears(32)->format('Y-m-d')],
            'homme' => ['Mamadou', 'Sow', 'Homme', now()->subYears(58)->format('Y-m-d')],
            'enfant' => ['Ibrahima', 'Bangoura', 'Homme', now()->subYears(3)->format('Y-m-d')],
            'sans_age' => ['Sékou', 'Kourouma', 'Homme', null],
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

        $this->line('  ✔ 4 patients : femme 32 ans, homme 58 ans, enfant 3 ans, homme sans âge');
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

    private function etape(string $libelle, callable $action): void
    {
        try {
            $d = $action();
            $this->line("  ✔ {$libelle} → {$d->numero}");
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
    }
}
