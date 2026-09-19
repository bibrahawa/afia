<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ComptePatient;
use App\Models\Patient;
use App\Support\EtablissementContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Comptes du portail patient (connexion par code SMS au téléphone du compte).
 *
 * CORRIGÉ (lot E2) — faille de confidentialité :
 *  - la liste montrait TOUS les comptes de la plateforme (téléphones, e-mails,
 *    noms des dossiers de toutes les cliniques) à tout soignant ;
 *  - n'importe quel dossier de n'importe quelle clinique pouvait être rattaché
 *    à un compte (y compris à son propre téléphone), ce qui donnait accès au
 *    dossier complet via le portail en contournant le consentement ;
 *  - n'importe quel compte pouvait être modifié (téléphone = prise de contrôle)
 *    ou supprimé.
 *
 * Désormais : une clinique ne voit et ne gère que les comptes liés à SES patients,
 * ne rattache que SES patients, atteste avoir vérifié l'identité pour toute action
 * qui donne un accès, et chaque action est inscrite au journal d'activité.
 */
class ComptePatientController extends Controller
{
    public function getIndex(Request $request)
    {
        $recherche = trim((string) $request->query('q'));
        $chiffres = preg_replace('/\D/', '', $recherche);

        $comptes = ComptePatient::query()
            ->whereHas('patients', fn ($q) => $q->suivisParEtablissement())
            ->when($recherche !== '', function ($q) use ($recherche, $chiffres) {
                $q->where(function ($q) use ($recherche, $chiffres) {
                    if (strlen($chiffres) >= 3) {
                        $q->orWhere('telephone', 'like', "%{$chiffres}%");
                    }
                    $q->orWhere('email', 'like', "%{$recherche}%")
                      ->orWhereHas('patients', fn ($p) => $p->suivisParEtablissement()->where(fn ($p2) => $p2
                          ->where('first_name', 'like', "%{$recherche}%")
                          ->orWhere('last_name', 'like', "%{$recherche}%")
                          ->orWhere('identifiant_national_sante', 'like', "%{$recherche}%")));
                });
            })
            ->with('patients')
            ->latest()
            ->paginate(30)
            ->withQueryString();

        // Dossiers d'AUTRES cliniques liés à ces comptes : comptés, jamais nommés.
        $idsClinique = Patient::suivisParEtablissement()
            ->whereIn('id', $comptes->getCollection()->flatMap(fn ($c) => $c->patients->pluck('id')))
            ->pluck('id')->flip();

        return view('comptes_patients.index', compact('comptes', 'idsClinique', 'recherche'));
    }

    /** Crée le compte (ou reprend celui de ce numéro) ET rattache un premier dossier de la clinique. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'telephone' => ['required', 'regex:/^[0-9]{9}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'identifiant_national_sante' => ['required', 'string', 'max:30'],
            'role' => ['required', 'in:titulaire,tuteur'],
            'identite_verifiee' => ['accepted'],
        ], $this->messages());

        $patient = $this->patientDeLaClinique($data['identifiant_national_sante']);
        if ($erreur = $this->refusTutelle($patient, $data['role'])) {
            return back()->withInput()->with('error', $erreur);
        }

        $compte = DB::transaction(function () use ($data, $patient) {
            $compte = ComptePatient::firstOrCreate(['telephone' => $data['telephone']], ['statut' => 'actif']);
            if (! empty($data['email']) && ! $compte->email && ! ComptePatient::where('email', $data['email'])->exists()) {
                $compte->update(['email' => $data['email']]);
            }
            $compte->patients()->syncWithoutDetaching([$patient->id => ['role' => $data['role']]]);

            return $compte;
        });

        $this->journaliser('compte_patient.rattacher', $compte, "Dossier {$patient->identifiant_national_sante} rattaché ({$data['role']})", ['patient_id' => $patient->id]);

        return back()->with('success', $compte->wasRecentlyCreated
            ? "Compte créé pour le {$compte->telephone}, avec le dossier de {$patient->getFullName()}."
            : "Ce numéro avait déjà un compte : le dossier de {$patient->getFullName()} y a été rattaché.");
    }

    public function update(Request $request, ComptePatient $comptePatient)
    {
        $this->autoriser($comptePatient);

        $data = $request->validate([
            'telephone' => ['required', 'regex:/^[0-9]{9}$/', Rule::unique('comptes_patients', 'telephone')->ignore($comptePatient->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('comptes_patients', 'email')->ignore($comptePatient->id)],
            'statut' => ['required', 'in:actif,suspendu'],
            'identite_verifiee' => ['nullable', 'boolean'],
        ], $this->messages());

        // Changer le téléphone = changer qui reçoit le code de connexion : identité vérifiée obligatoire.
        $nouveauTelephone = $data['telephone'] !== $comptePatient->telephone;
        if ($nouveauTelephone && ! $request->boolean('identite_verifiee')) {
            return back()->withInput()->with('error', 'Changer le téléphone donne l\'accès au portail à ce nouveau numéro : cochez « J\'ai vérifié l\'identité » après contrôle d\'une pièce d\'identité.');
        }

        $avant = $comptePatient->only(['telephone', 'email', 'statut']);
        $comptePatient->update(collect($data)->only(['telephone', 'email', 'statut'])->all());

        $this->journaliser('compte_patient.modifier', $comptePatient, 'Compte portail modifié', ['avant' => $avant, 'apres' => $comptePatient->only(['telephone', 'email', 'statut'])]);

        return back()->with('success', 'Compte mis à jour.');
    }

    public function delete(ComptePatient $comptePatient)
    {
        $this->autoriser($comptePatient);

        // Un compte qui sert aussi à des dossiers d'autres cliniques ne se supprime pas d'ici.
        $autres = $comptePatient->patients()->count() - $comptePatient->patients()->suivisParEtablissement()->count();
        if ($autres > 0 && ! EtablissementContext::estAdministrateurPlateforme()) {
            return back()->with('error', "Ce compte donne aussi accès à {$autres} dossier(s) suivi(s) dans une autre clinique : détachez seulement vos dossiers.");
        }

        $this->journaliser('compte_patient.supprimer', $comptePatient, 'Compte portail supprimé', ['telephone' => $comptePatient->telephone]);
        // Les dossiers patients sont conservés (seul le lien compte_patient disparaît).
        $comptePatient->delete();

        return back()->with('success', 'Compte supprimé (les dossiers patients sont conservés).');
    }

    /** Rattache un dossier de la clinique à ce compte (ex. l'enfant d'une mère déjà inscrite). */
    public function attacherPatient(Request $request, ComptePatient $comptePatient)
    {
        $this->autoriser($comptePatient);

        $data = $request->validate([
            'identifiant_national_sante' => ['required', 'string', 'max:30'],
            'role' => ['required', 'in:titulaire,tuteur'],
            'identite_verifiee' => ['accepted'],
        ], $this->messages());

        $patient = $this->patientDeLaClinique($data['identifiant_national_sante']);
        if ($erreur = $this->refusTutelle($patient, $data['role'])) {
            return back()->with('error', $erreur);
        }

        $comptePatient->patients()->syncWithoutDetaching([$patient->id => ['role' => $data['role']]]);
        $this->journaliser('compte_patient.rattacher', $comptePatient, "Dossier {$patient->identifiant_national_sante} rattaché ({$data['role']})", ['patient_id' => $patient->id]);

        return back()->with('success', "Dossier de {$patient->getFullName()} rattaché à ce compte.");
    }

    public function detacherPatient(ComptePatient $comptePatient, Patient $patient)
    {
        $this->autoriser($comptePatient);
        abort_unless(Patient::suivisParEtablissement()->whereKey($patient->id)->exists(), 404);

        $comptePatient->patients()->detach($patient->id);
        $this->journaliser('compte_patient.detacher', $comptePatient, "Dossier {$patient->identifiant_national_sante} détaché", ['patient_id' => $patient->id]);

        return back()->with('success', 'Dossier détaché du compte.');
    }

    // ------------------------------------------------------------------

    /** Un compte n'est visible et modifiable que s'il est lié à au moins un patient de la clinique. */
    private function autoriser(ComptePatient $compte): void
    {
        abort_unless(
            EtablissementContext::estAdministrateurPlateforme() || $compte->patients()->suivisParEtablissement()->exists(),
            404
        );
    }

    private function patientDeLaClinique(string $identifiant): Patient
    {
        $patient = Patient::suivisParEtablissement()->where('identifiant_national_sante', trim($identifiant))->first();

        if (! $patient) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'identifiant_national_sante' => 'Aucun patient suivi par votre clinique ne porte cet identifiant.',
            ]);
        }

        return $patient;
    }

    /**
     * La tutelle donne un accès complet au dossier : réservée aux mineurs. Un adulte
     * qui veut qu'un proche gère son dossier passe par la demande de consentement.
     */
    private function refusTutelle(Patient $patient, string $role): ?string
    {
        return $role === 'tuteur' && $patient->estMineur() === false
            ? "{$patient->getFullName()} est enregistré(e) comme majeur(e) : la tutelle ne s'applique qu'aux mineurs. Pour un proche adulte, utilisez la demande de consentement."
            : null;
    }

    private function journaliser(string $action, ComptePatient $compte, string $description, array $proprietes = []): void
    {
        try {
            ActivityLog::create([
                'etablissement_id' => EtablissementContext::id(),
                'causer_type' => \App\Models\User::class,
                'causer_id' => auth()->id(),
                'subject_type' => ComptePatient::class,
                'subject_id' => $compte->id,
                'action' => $action,
                'description' => $description,
                'proprietes' => $proprietes,
                'ip_address' => request()->ip(),
            ]);
        } catch (\Throwable) {
            // Le journal ne doit jamais empêcher l'action.
        }
    }

    private function messages(): array
    {
        return [
            'telephone.required' => 'Indiquez le téléphone qui recevra le code de connexion.',
            'telephone.regex' => 'Le téléphone doit contenir 9 chiffres.',
            'telephone.unique' => 'Ce numéro est déjà utilisé par un autre compte.',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée par un autre compte.',
            'identifiant_national_sante.required' => 'Indiquez l\'identifiant santé du dossier (sur la carte du patient).',
            'identite_verifiee.accepted' => 'Cochez « J\'ai vérifié l\'identité » : ce compte donnera accès au dossier médical.',
        ];
    }
}
