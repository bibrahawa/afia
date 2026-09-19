<?php

namespace App\Services\Personnel;

use App\Models\Employee;
use App\Models\User;
use App\Services\SmsService;
use App\Support\EtablissementContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Lot E1 — Accès à Hali d'un membre du personnel (compte de connexion lié à sa fiche).
 *
 * Un seul endroit pour créer, modifier, réinitialiser ou suspendre un accès :
 * la fiche employé. Le mot de passe provisoire est envoyé par SMS et doit être
 * changé à la première connexion (middleware VerifierCompteActif).
 */
class AccesPersonnelService
{
    /** Rôles jamais attribuables depuis une clinique. */
    private const ROLES_INTERDITS = [EtablissementContext::ROLE_PLATEFORME, 'patient'];

    public function __construct(private SmsService $sms)
    {
    }

    /**
     * Rôles que $acteur peut donner : on ne donne que ce que l'on possède
     * (même règle que l'attribution des permissions). L'administrateur
     * plateforme peut tout donner, sauf son propre rôle.
     */
    public function rolesAttribuables(?User $acteur): Collection
    {
        $roles = Role::with('permissions')->whereNotIn('name', self::ROLES_INTERDITS)->orderBy('name')->get();

        if (! $acteur || EtablissementContext::estAdministrateurPlateforme($acteur)) {
            return $roles;
        }

        $miennes = $acteur->getAllPermissions()->pluck('name');

        return $roles->filter(fn (Role $r) => $r->permissions->pluck('name')->diff($miennes)->isEmpty())->values();
    }

    public function peutAttribuer(?User $acteur, string $role): bool
    {
        return $this->rolesAttribuables($acteur)->contains('name', $role);
    }

    /** Crée le compte, le relie à la fiche, envoie le mot de passe provisoire. Retourne [User, mot de passe, SMS parti ?]. */
    public function creer(Employee $employe, array $donnees, ?User $acteur): array
    {
        abort_if($employe->user_id, 422, 'Cet employé a déjà un accès.');
        abort_unless($this->peutAttribuer($acteur, $donnees['role']), 403, 'Vous ne pouvez pas attribuer ce rôle.');

        $motDePasse = $this->genererMotDePasse();

        $utilisateur = DB::transaction(function () use ($employe, $donnees, $motDePasse) {
            $utilisateur = User::create([
                'name' => $this->nomCompte($employe),
                'phone' => $donnees['telephone'],
                'email' => $donnees['email'] ?: null,
                'password' => Hash::make($motDePasse),
                'status' => true,
                'doit_changer_mot_de_passe' => true,
            ]);
            // Hors $fillable volontairement : jamais choisi par un formulaire.
            $utilisateur->forceFill(['etablissement_id' => $employe->etablissement_id ?? EtablissementContext::id()])->save();
            $utilisateur->assignRole($donnees['role']);

            $employe->forceFill(['user_id' => $utilisateur->id])->save();

            return $utilisateur;
        });

        $parti = ($donnees['envoyer_sms'] ?? true) ? $this->envoyerIdentifiants($utilisateur, $motDePasse, 'creation') : false;

        return [$utilisateur, $motDePasse, $parti];
    }

    public function modifier(User $utilisateur, Employee $employe, array $donnees, ?User $acteur): void
    {
        $roleActuel = $utilisateur->getRoleNames()->first();
        if ($donnees['role'] !== $roleActuel) {
            abort_unless($this->peutAttribuer($acteur, $donnees['role']), 403, 'Vous ne pouvez pas attribuer ce rôle.');
        }

        DB::transaction(function () use ($utilisateur, $employe, $donnees, $roleActuel) {
            $utilisateur->update([
                'name' => $this->nomCompte($employe),
                'phone' => $donnees['telephone'],
                'email' => $donnees['email'] ?: null,
            ]);
            if ($donnees['role'] !== $roleActuel) {
                $utilisateur->syncRoles([$donnees['role']]);
            }
        });
    }

    /** Nouveau mot de passe provisoire, envoyé par SMS. Retourne [mot de passe, SMS parti ?]. */
    public function reinitialiser(User $utilisateur): array
    {
        $motDePasse = $this->genererMotDePasse();
        $utilisateur->forceFill([
            'password' => Hash::make($motDePasse),
            'doit_changer_mot_de_passe' => true,
            'login_attempts' => 0,
            'locked_until' => null,
        ])->save();

        return [$motDePasse, $this->envoyerIdentifiants($utilisateur, $motDePasse, 'reinitialisation')];
    }

    /** Nom affiché en haut à droite : « Dr » une seule fois pour un médecin. */
    public function nomCompte(Employee $employe): string
    {
        return trim(($employe->type === 'Doctor' ? 'Dr ' : '') . $employe->first_name . ' ' . $employe->last_name);
    }

    /**
     * Mot de passe provisoire lisible et facile à dicter au téléphone : « kxp-4827 ».
     * Lettres sans ambiguïté (pas de l / o / i), chiffres aléatoires.
     */
    public function genererMotDePasse(): string
    {
        $lettres = 'abcdefghjkmnpqrstuvwxyz';
        $mot = '';
        for ($i = 0; $i < 3; $i++) {
            $mot .= $lettres[random_int(0, strlen($lettres) - 1)];
        }

        return $mot . '-' . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    private function envoyerIdentifiants(User $utilisateur, string $motDePasse, string $motif): bool
    {
        $clinique = \Illuminate\Support\Str::ascii(EtablissementContext::current()?->nom ?? \App\Support\Marque::nom());
        $message = ($motif === 'creation' ? "{$clinique} : votre acces Hali est pret." : "{$clinique} : votre mot de passe Hali a ete reinitialise.")
            . " Identifiant : {$utilisateur->phone}. Mot de passe provisoire : {$motDePasse} (a changer a la 1re connexion). " . url('/login');

        $resultat = $this->sms->sendSms($utilisateur->phone, $message, [
            'type' => 'acces_personnel',
            'sujet' => $utilisateur,
            'secret' => $motDePasse,
            'envoye_par' => auth()->id(),
        ]);

        return (bool) ($resultat['success'] ?? false);
    }
}
