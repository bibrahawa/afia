<?php

namespace App\Http\Controllers;

use App\Enums\PorteeAcces;
use App\Models\Consentement;
use App\Models\DemandeAcces;
use App\Models\Patient;
use App\Services\AccesDossierSanteService;
use App\Support\EtablissementContext;
use Illuminate\Http\Request;

class ConsentementController extends Controller
{
    public function __construct(protected AccesDossierSanteService $acces)
    {
    }

    /**
     * Historique des demandes et consentements pour un patient — la vue
     * "lecture" de ce sous-système. Pas de création/édition manuelle ici :
     * une DemandeAcces naît d'un clic "Demander l'accès" (méthode demander()
     * ci-dessous), un Consentement naît d'une confirmation patient — ce
     * sont des transitions d'état, pas des formulaires CRUD classiques.
     */
    public function historique(Patient $patient)
    {
        $demandes = $patient->demandesAcces()->latest()->get();
        $consentements = $patient->consentementsAccordes()->latest()->get();

        return view('patients._consentements', compact('patient', 'demandes', 'consentements'));
    }


    /**
     * Déclenché côté personnel soignant, ex. depuis la fiche patient,
     * bouton "Demander l'historique complet". Middleware
     * `permission:consentement.demander` à ajouter dans routes/web.php.
     */
    public function demander(Request $request, Patient $patient)
    {
        $valide = $request->validate([
            'portees' => ['required', 'array', 'min:1'],
            'portees.*' => ['string'],
            'motif' => ['nullable', 'string', 'max:500'],
        ]);

        $portees = array_map(fn ($p) => PorteeAcces::from($p), $valide['portees']);

        $demande = $this->acces->demanderAcces(
            $patient,
            $request->user(),
            $portees,
            $valide['motif'] ?? null
        );

        // CORRIGÉ (faille de consentement) — le code envoyé au patient s'affichait ici au
        // soignant (« Code de secours : … ») : il pouvait le saisir lui-même via
        // « Confirmer l'accès » et s'accorder le dossier sans l'accord du patient.
        // Le code n'est connu que du patient ; c'est lui qui le communique s'il accepte.
        return back()->with('success', 'Demande envoyée au patient par SMS (valable 30 min). S\'il accepte, il ouvrira le lien reçu ou vous communiquera le code à saisir.');
    }

    /**
     * Page publique ouverte via le lien signé reçu par SMS — AUCUNE
     * authentification requise, la signature de l'URL fait foi. Ne jamais
     * transformer cette route en simple `Route::get` non signée.
     */
    public function afficherConfirmation(Request $request, DemandeAcces $demande)
    {
        abort_unless($request->hasValidSignature(), 403, 'Lien expiré ou invalide.');

        if ($demande->statut !== 'en_attente') {
            return view('consentement.deja-traitee', compact('demande'));
        }

        return view('consentement.confirmer', [
            'demande' => $demande,
            'portees' => $demande->portees(),
        ]);
    }

    public function confirmer(Request $request, DemandeAcces $demande)
    {
        abort_unless($request->hasValidSignature(), 403, 'Lien expiré ou invalide.');
        abort_if($demande->statut !== 'en_attente', 409, 'Cette demande a déjà été traitée.');
        abort_if($demande->expire_le?->isPast(), 410, 'Cette demande a expiré.');

        $consentement = $this->acces->confirmerDemande($demande);

        return view('consentement.confirmee', compact('consentement'));
    }

    public function refuser(Request $request, DemandeAcces $demande)
    {
        abort_unless($request->hasValidSignature(), 403, 'Lien expiré ou invalide.');

        $demande->update(['statut' => 'refusee']);

        return view('consentement.refusee');
    }

    /**
     * Alternative au lien signé quand le patient n'a que le code oral :
     * un membre du personnel saisit le code communiqué à l'accueil.
     */
    public function confirmerParCode(Request $request, Patient $patient, \App\Services\OtpService $otp)
    {
        $valide = $request->validate(['code' => ['required', 'string', 'max:10']]);

        // CORRIGÉ — aucune limite d'essais : un code à 6 chiffres valable 30 minutes pouvait
        // être trouvé par essais automatisés. 5 essais par patient et par soignant / 30 min.
        $cle = 'consentement-code:' . $patient->id . ':' . $request->user()->id;
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($cle, 5)) {
            return back()->with('error', 'Trop de codes erronés. Demandez au patient d\'ouvrir le lien reçu par SMS, ou réessayez dans ' . ceil(\Illuminate\Support\Facades\RateLimiter::availableIn($cle) / 60) . ' minutes.');
        }

        // Le code est haché en base — on ne peut plus filtrer par égalité
        // directe en SQL. Le nombre de demandes en_attente pour un même
        // patient reste toujours faible (quelques unités au plus), donc
        // vérifier chacune en mémoire n'a aucun coût réel.
        $demande = DemandeAcces::where('patient_id', $patient->id)
            ->where('statut', 'en_attente')
            ->where('expire_le', '>', now())
            ->get()
            ->first(fn (DemandeAcces $d) => $otp->verifier($valide['code'], $d->code_confirmation));

        if (! $demande) {
            \Illuminate\Support\Facades\RateLimiter::hit($cle, 1800);

            return back()->with('error', 'Code invalide ou expiré.');
        }
        \Illuminate\Support\Facades\RateLimiter::clear($cle);

        $this->acces->confirmerDemande($demande);

        return back()->with('success', 'Accès confirmé par le patient.');
    }

    /**
     * Le patient (ou le staff à sa demande explicite) peut révoquer à tout
     * moment un accès déjà accordé — le contrôle reste entre ses mains
     * après coup, pas seulement au moment de l'octroi.
     */
    public function revoquer(Consentement $consentement)
    {
        $this->acces->revoquer($consentement);

        return back()->with('success', 'Accès révoqué.');
    }
}
