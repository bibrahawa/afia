<?php

namespace App\Console\Commands;

use App\Models\Etablissement;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Diagnostic et réparation d'un compte qui n'arrive plus à se connecter.
 *
 *   php artisan aprosafe:compte 622099672
 *   php artisan aprosafe:compte 622099672 --debloquer
 *   php artisan aprosafe:compte 622099672 --mot-de-passe="Nouveau@2026"
 *   php artisan aprosafe:compte 622099672 --etablissement=aprosafe
 *   php artisan aprosafe:compte 622099672 --role=admin --role=Biologiste
 */
class DiagnosticCompte extends Command
{
    protected $signature = 'aprosafe:compte
        {telephone : numéro de connexion (9 chiffres)}
        {--debloquer : remet à zéro tentatives, verrouillage et limitation de débit}
        {--mot-de-passe= : définit un nouveau mot de passe}
        {--etablissement= : rattache le compte à l\'établissement (slug)}
        {--role=* : ajoute un ou plusieurs rôles}
        {--ip= : IP bloquée par la limitation (sinon utilisez cache:clear)}';

    protected $description = 'Diagnostique et répare un compte utilisateur (connexion impossible)';

    public function handle(): int
    {
        $telephone = $this->argument('telephone');
        $user = User::where('phone', $telephone)->first();

        if (! $user) {
            $this->error("Aucun compte avec le téléphone {$telephone}. La connexion se fait par TÉLÉPHONE (9 chiffres), pas par e-mail.");
            $proches = User::where('phone', 'like', '%' . substr($telephone, -4))->limit(5)->get(['id', 'name', 'phone', 'email']);
            if ($proches->isNotEmpty()) {
                $this->table(['id', 'nom', 'téléphone', 'email'], $proches->toArray());
            }

            return self::FAILURE;
        }

        $this->afficher($user, $telephone);

        if ($this->option('debloquer')) {
            $user->forceFill(['login_attempts' => 0, 'locked_until' => null])->save();
            RateLimiter::clear('login.user.' . $telephone);
            if ($ip = $this->option('ip')) {
                RateLimiter::clear('login.ip.' . $ip);
            }
            $this->info('✔ Compte débloqué' . ($this->option('ip') ? ' (IP comprise)' : ' — si « Trop de tentatives depuis cette adresse IP » persiste : php artisan cache:clear'));
        }

        if ($mdp = $this->option('mot-de-passe')) {
            $user->forceFill(['password' => Hash::make($mdp), 'login_attempts' => 0, 'locked_until' => null])->save();
            RateLimiter::clear('login.user.' . $telephone);
            $this->info('✔ Mot de passe redéfini');
        }

        if ($slug = $this->option('etablissement')) {
            $etab = Etablissement::where('slug', $slug)->first();
            if (! $etab) {
                $this->error("Établissement « {$slug} » introuvable.");

                return self::FAILURE;
            }
            $user->forceFill(['etablissement_id' => $etab->id])->save();
            $this->info("✔ Rattaché à {$etab->nom}");
        }

        foreach ($this->option('role') as $role) {
            $user->assignRole($role);
            $this->info("✔ Rôle « {$role} » ajouté");
        }

        if ($this->option('debloquer') || $this->option('mot-de-passe') || $this->option('etablissement') || $this->option('role')) {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
            $this->newLine();
            $this->afficher($user->fresh(), $telephone);
        }

        return self::SUCCESS;
    }

    private function afficher(User $user, string $telephone): void
    {
        $verrouille = $user->locked_until && now()->lt($user->locked_until);
        $limite = RateLimiter::tooManyAttempts('login.user.' . $telephone, 5);
        $etab = $user->etablissement_id ? Etablissement::find($user->etablissement_id) : null;
        $roles = $user->getRoleNames();

        $this->table(['Contrôle', 'Valeur', 'État'], [
            ['Compte', "#{$user->id} {$user->name} <{$user->email}>", '✔'],
            ['Statut', (string) ($user->status ?? '—'), in_array($user->status, [null, '', 'active', 'actif', 1, '1'], true) ? '✔' : '⚠ vérifier'],
            ['Tentatives échouées', (string) ($user->login_attempts ?? 0), ($user->login_attempts ?? 0) >= 5 ? '⚠ prochain échec = verrouillage' : '✔'],
            ['Verrouillé jusqu\'à', (string) ($user->locked_until ?? '—'), $verrouille ? '✖ VERROUILLÉ → --debloquer' : '✔'],
            ['Limitation de débit (compte)', $limite ? 'atteinte' : 'non', $limite ? '✖ → --debloquer' : '✔'],
            ['Établissement', $etab ? "{$etab->nom} ({$etab->slug})" : 'AUCUN', $etab ? '✔' : ($roles->contains('super-admin') ? '✔ admin plateforme' : '✖ écrans vides / 403 labo → --etablissement=aprosafe')],
            ['Rôles', $roles->implode(', ') ?: 'aucun', $roles->isEmpty() ? '✖ aucun menu → --role=admin' : '✔'],
            ['Module labo', $etab ? ($etab->aModule('laboratoire') ? 'actif' : 'inactif') : '—', ''],
            ['Dernière connexion', (string) ($user->last_login_at ?? '—'), ''],
        ]);
    }
}
