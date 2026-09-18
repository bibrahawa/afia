<?php

namespace App\Console\Commands\Labo;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Labo\LaboExamen;
use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use App\Services\Labo\CatalogueImportService;
use App\Support\Marque;
use Database\Seeders\Labo\LaboCatalogueModeleSeeder;
use Database\Seeders\Labo\LaboPermissionsSeeder;
use Database\Seeders\Labo\LaboReseauPermissionsSeeder;
use Database\Seeders\Rapports\RapportsPermissionsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Mise en service d'un LABORATOIRE indépendant, en une commande.
 *
 *   php artisan hali:ouvrir-laboratoire labo-kaloum --nom="Laboratoire Central Kaloum"
 *   php artisan hali:ouvrir-laboratoire labo-kaloum --avec-assurance --telephone=628164422
 *
 * Un laboratoire n'a pas besoin des rendez-vous, des consultations ni de
 * l'hospitalisation : SEUL le module laboratoire est activé, et son menu s'y
 * limite. L'assurance n'est ouverte que s'il facture lui-même des assurés.
 *
 * Relançable sans risque : tout est créé « si absent ».
 */
class OuvrirLaboratoire extends Command
{
    protected $signature = 'hali:ouvrir-laboratoire
        {slug : identifiant court du laboratoire, ex. labo-kaloum}
        {--nom= : raison sociale affichée sur les comptes rendus}
        {--adresse= : adresse postale}
        {--telephone= : téléphone affiché sur les documents}
        {--email= : adresse de contact}
        {--statut=essai : essai ou actif}
        {--prefixe= : préfixe des factures, ex. LKA-}
        {--avec-assurance : le laboratoire facture lui-même des patients assurés}
        {--sans-catalogue : ne pas importer le catalogue modèle}
        {--mot-de-passe= : mot de passe initial des comptes créés}
        {--force : ne pas demander de confirmation}';

    protected $description = 'Ouvre un laboratoire indépendant : établissement, module, catalogue, comptes et identifiants à remettre au client';

    /** Rôles du laboratoire, dans l'ordre du circuit. */
    private const EQUIPE = [
        'gerant' => ['Gérant', 'admin', 'Admin'],
        'accueil' => ['Accueil', 'Accueil laboratoire', 'Reception'],
        'preleveur' => ['Préleveur', 'Préleveur', 'Nurse'],
        'technicien' => ['Technicien', 'Technicien de laboratoire', 'Laboratory'],
        'biologiste' => ['Biologiste', 'Biologiste', 'Laboratory'],
        'comptable' => ['Comptable', 'comptable', 'Accountant'],
    ];

    private array $comptes = [];

    public function handle(CatalogueImportService $import): int
    {
        $slug = Str::slug($this->argument('slug'));
        $nom = $this->option('nom') ?: 'Laboratoire ' . Str::title(str_replace('-', ' ', $slug));
        $motDePasse = $this->option('mot-de-passe') ?: 'Labo@' . now()->year;

        $this->info(Marque::nom() . ' — ouverture d\'un laboratoire');
        $this->line('  Nom  : ' . $nom);
        $this->line('  Slug : ' . $slug);
        $this->line('  Modules : laboratoire' . ($this->option('avec-assurance') ? ' + assurance' : ' (sans rendez-vous, consultation ni hospitalisation)'));

        if (! $this->option('force') && ! $this->confirm('Continuer ?', true)) {
            return self::SUCCESS;
        }

        $etablissement = $this->creerEtablissement($slug, $nom);
        $this->activerModules($etablissement);
        $this->semerPermissions();
        $this->creerEquipe($etablissement, $motDePasse);

        if (! $this->option('sans-catalogue')) {
            $this->importerCatalogue($etablissement, $import);
        }

        $this->afficherRemise($etablissement, $motDePasse);

        return self::SUCCESS;
    }

    // ------------------------------------------------------------------

    private function creerEtablissement(string $slug, string $nom): Etablissement
    {
        $etablissement = Etablissement::withoutGlobalScopes()->firstOrCreate(
            ['slug' => $slug],
            [
                'nom' => $nom,
                'type' => 'laboratoire',
                'statut' => in_array($this->option('statut'), ['essai', 'actif'], true) ? $this->option('statut') : 'essai',
                'adresse' => $this->option('adresse') ?: 'Conakry',
                'contact' => $this->option('telephone'),
                'email' => $this->option('email') ?: "contact@{$slug}.gn",
                // Les numéros portent les initiales du laboratoire : un patient
                // qui présente une facture doit savoir d'où elle vient.
                'prefixe_facture' => $this->option('prefixe') ?: Str::upper(Str::substr(str_replace('-', '', $slug), 0, 3)) . '-',
                'prefixe_patient' => 'PAT-',
            ]
        );

        $this->line($etablissement->wasRecentlyCreated
            ? '  ✔ Établissement créé (#' . $etablissement->id . ')'
            : '  • Établissement déjà existant (#' . $etablissement->id . '), réutilisé');

        return $etablissement;
    }

    private function activerModules(Etablissement $etablissement): void
    {
        $codes = ['laboratoire'];

        if ($this->option('avec-assurance')) {
            $codes[] = 'assurance';
        }

        foreach ($codes as $code) {
            $module = Module::where('code', $code)->first();

            if (! $module) {
                $this->warn("  ⚠ Module « {$code} » absent : lancez EtablissementFoundationSeeder.");
                continue;
            }

            $etablissement->modules()->syncWithoutDetaching([
                $module->id => ['est_actif' => true, 'active_depuis' => now(), 'desactive_le' => null],
            ]);
        }

        // Fermeture explicite de ce qui ne concerne pas un laboratoire : si
        // l'établissement a déjà servi, ses menus doivent se réduire.
        foreach (['rdv', 'consultation', 'hospitalisation'] as $code) {
            if ($module = Module::where('code', $code)->first()) {
                $etablissement->modules()->syncWithoutDetaching([
                    $module->id => ['est_actif' => false, 'desactive_le' => now()],
                ]);
            }
        }

        $this->line('  ✔ Modules réglés pour un laboratoire');
    }

    private function semerPermissions(): void
    {
        foreach ([LaboPermissionsSeeder::class, LaboReseauPermissionsSeeder::class, RapportsPermissionsSeeder::class] as $seeder) {
            if (class_exists($seeder)) {
                $this->callSilent('db:seed', ['--class' => $seeder, '--force' => true]);
            }
        }

        if ($this->option('avec-assurance') && class_exists(\Database\Seeders\Assurance\AssurancePermissionsSeeder::class)) {
            $this->callSilent('db:seed', ['--class' => \Database\Seeders\Assurance\AssurancePermissionsSeeder::class, '--force' => true]);
        }

        $this->line('  ✔ Permissions du laboratoire, du réseau et des rapports');
    }

    private function creerEquipe(Etablissement $etablissement, string $motDePasse): void
    {
        $departement = Department::withoutGlobalScopes()->firstOrCreate(
            ['name' => 'LABORATOIRE', 'etablissement_id' => $etablissement->id]
        );

        // Numéros provisoires, dérivés du slug : le client remplacera chacun
        // par le vrai numéro de la personne à la première connexion.
        $base = sprintf('62%05d', crc32($etablissement->slug) % 100000);
        $rang = 0;

        foreach (self::EQUIPE as $cle => [$prenom, $role, $type]) {
            $telephone = $base . str_pad((string) (++$rang), 2, '0', STR_PAD_LEFT);

            $user = User::firstOrCreate(
                ['phone' => $telephone],
                [
                    'name' => $prenom . ' ' . $etablissement->nom,
                    'email' => "{$cle}@{$etablissement->slug}.gn",
                    'password' => Hash::make($motDePasse),
                ]
            );

            $user->forceFill(['etablissement_id' => $etablissement->id, 'login_attempts' => 0, 'locked_until' => null])->save();

            if (Role::where('name', $role)->exists()) {
                $user->syncRoles([$role]);
            } else {
                $this->warn("  ⚠ Rôle « {$role} » inexistant : compte {$cle} sans rôle (lancez PermissionSeeder).");
            }

            Employee::withoutGlobalScopes()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'etablissement_id' => $etablissement->id, 'first_name' => $prenom, 'last_name' => 'Laboratoire',
                    'address' => $etablissement->adresse ?: 'Conakry', 'type' => $type,
                    'department_id' => $departement->id, 'speciality' => 'Biologie médicale', 'is_active' => true,
                ]
            );

            $this->comptes[$cle] = ['role' => $role, 'telephone' => $telephone];
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $this->line('  ✔ ' . count($this->comptes) . ' comptes créés');
    }

    private function importerCatalogue(Etablissement $etablissement, CatalogueImportService $import): void
    {
        if (! LaboExamen::withoutGlobalScopes()->whereNull('etablissement_id')->exists()) {
            $this->callSilent('db:seed', ['--class' => LaboCatalogueModeleSeeder::class, '--force' => true]);
        }

        DB::transaction(fn () => $import->importer($etablissement->id));

        $total = LaboExamen::withoutGlobalScopes()->where('etablissement_id', $etablissement->id)->count();
        $sansPrix = LaboExamen::withoutGlobalScopes()->where('etablissement_id', $etablissement->id)->where('prix', '<=', 0)->count();

        $this->line("  ✔ Catalogue importé : {$total} examens");

        if ($sansPrix > 0) {
            $this->warn("  ⚠ {$sansPrix} examens sans prix : tant qu'ils sont à zéro, ils ne facturent rien et les cliniques partenaires ne peuvent pas les commander.");
        }
    }

    private function afficherRemise(Etablissement $etablissement, string $motDePasse): void
    {
        $this->newLine();
        $this->info('🔑 Identifiants à remettre au laboratoire — connexion par TÉLÉPHONE');
        $this->line('   Mot de passe initial : ' . $motDePasse . '  (à changer à la première connexion)');

        $this->table(
            ['Poste', 'Téléphone', 'Rôle'],
            collect($this->comptes)->map(fn ($c, $cle) => [Str::title($cle), $c['telephone'], $c['role']])->values()->all()
        );

        $this->newLine();
        $this->info('📋 Ce qui reste à faire avec le client, dans l\'ordre');
        $this->line('  1. Remplacer les numéros provisoires par ceux de son personnel.');
        $this->line('  2. TARIFER le catalogue : un examen à zéro ne facture rien et bloque les demandes du réseau.');
        $this->line('  3. Vérifier les valeurs de référence des examens qu\'il pratique (adulte, enfant, grossesse).');
        $this->line('  4. Renseigner logo, adresse et téléphone : ils s\'impriment sur les comptes rendus.');
        $this->line('  5. Ouvrir ses premières cliniques partenaires depuis « Cliniques partenaires ».');
        $this->line('  6. Passer le statut de « essai » à « actif » à la signature du contrat.');

        $this->newLine();
        $this->line('Menus visibles : tableau de bord, demandes, prélèvements, réception, paillasse,');
        $this->line('validation, déclarations, catalogue, cliniques partenaires, créances, rapports.');
        $this->line('Volontairement absents : rendez-vous, consultations, hospitalisation.');
    }
}
