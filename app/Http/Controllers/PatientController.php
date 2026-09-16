<?php

namespace App\Http\Controllers;

use App\Models\ComptePatient;
use App\Models\Etablissement;
use App\Services\ConsultationService;
use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class PatientController extends Controller
{
    protected $consultationItem;

    public function __construct(ConsultationService $consultationItem)
    {
        $this->consultationItem = $consultationItem;
    }

    /**
     * CORRIGÉ — la recherche par téléphone passait par `whereHas('user')`,
     * l'ancien lien staff. Un patient créé via la prise de rdv (qui n'a
     * jamais de `user_id`, seulement un `ComptePatient`) n'apparaissait
     * jamais dans cette recherche.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');

        $patients = Patient::when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('identifiant_national_sante', 'like', "%{$search}%")
                    ->orWhereHas('comptesPatients', function ($q2) use ($search) {
                        $q2->where('telephone', 'like', "%{$search}%");
                    })
                    ->orWhere('id', 'like', "%{$search}%");
                });
            })
            ->paginate(20)
            ->withQueryString();

        return view('patients.index', compact('patients', 'search'));
    }

    public function getPatientActes($transactionId)
    {
        try {
            $transaction = Transaction::find($transactionId);
            $actes = $this->consultationItem->getActes($transaction);

            return response()->json(['success' => true, 'actes' => $actes]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des actes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * CORRIGÉ EN PROFONDEUR — créait un `User` staff complet (rôle Spatie
     * "patient", mot de passe en clair identique "12345678" pour tout le
     * monde) à chaque patient. Deux problèmes réels : (1) un patient créé
     * ici n'avait aucun moyen de se connecter au portail (ComptePatient
     * inexistant), (2) le même téléphone utilisé plus tard dans la prise
     * de rdv publique aurait créé un DOUBLON complet — l'ancien mécanisme
     * et le nouveau ne se voyaient pas entre eux. Aligné maintenant sur le
     * même système d'identité que le reste de la plateforme.
     *
     * C'est la création classique depuis la fiche "Nouveau patient" du
     * personnel (accueil), authentifiée — à distinguer de storeRapide()
     * et storeRapideStaff() ci-dessous, utilisées depuis les parcours de
     * prise de rendez-vous (public et staff).
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|regex:/^[0-9]{9}$/',
            'gender' => 'required|in:Homme,Femme',
        ]);

        $data = $request->except(['phone', '_token']);
        $data['country'] = $data['country'] ?? 'Guinee';
        $data['state'] = $data['state'] ?? 'Conakry';

        $patient = DB::transaction(function () use ($data, $request) {
            $compte = ComptePatient::firstOrCreate(
                ['telephone' => $request->phone],
                ['statut' => 'actif']
            );

            $patientExistant = $compte->patients()->wherePivot('role', 'titulaire')->first();

            if ($patientExistant) {
                // Un dossier existe déjà pour ce numéro — on ne duplique
                // jamais un patient sur la base du seul téléphone.
                return $patientExistant;
            }

            $patient = Patient::create($data);
            $compte->patients()->attach($patient->id, ['role' => 'titulaire']);

            if ($etablissement = \App\Support\EtablissementContext::current()) {
                $etablissement->patients()->attach($patient->id, [
                    'premiere_visite_le' => now(), 'derniere_visite_le' => now(),
                ]);
            }

            return $patient;
        });

        if ($patient->wasRecentlyCreated === false) {
            return back()->with('error', "Un patient existe déjà avec ce numéro de téléphone : {$patient->getFullName()} ({$patient->identifiant_national_sante}).");
        }

        return back()->with('success', 'Patient enregistré avec succès.');
    }

    /*
    |--------------------------------------------------------------------------
    | PUBLIC — création minimale depuis le parcours de prise de rdv sans
    | authentification. Distincte de store() ci-dessus : champs réduits au
    | strict nécessaire, protégée par honeypot + vérification OTP du
    | téléphone (voir AppointmentController::verifierCodeRdv), jamais par
    | une session staff.
    |--------------------------------------------------------------------------
    */
    public function storeRapide(Etablissement $etablissement, Request $request)
    {
        // Champ piège : un humain ne le remplit jamais (invisible en CSS
        // dans le formulaire), un robot qui soumet tous les champs si.
        // Réponse volontairement peu informative, pour ne rien apprendre
        // à un bot sur la raison du rejet.
        if (filled($request->input('site_web'))) {
            return response()->json(['error' => 'Requête invalide.'], 422);
        }

        $data = $request->validate([
            'telephone' => ['required', 'regex:/^[0-9]{9}$/'],
            'prenom' => ['required', 'string', 'max:255'],
            'nom' => ['required', 'string', 'max:255'],
            'genre' => ['required', 'in:Homme,Femme'],
        ], [
            'telephone.regex' => 'Le numéro de téléphone doit contenir exactement 9 chiffres.',
        ]);

        // RÉACTIVÉ le 16/09/2026 — nouvelle règle : l'OTP protège la
        // CRÉATION d'un dossier (cette méthode, appelée uniquement pour un
        // nouveau patient), pas la soumission du rdv en elle-même. Un
        // patient déjà connu n'a plus à revérifier son numéro à chaque
        // rendez-vous — voir AppointmentController::store(), qui lui reste
        // sans ce verrou. Le filet de sécurité pour le patient déjà connu
        // (jamais vérifié par OTP) est le SMS avec lien d'annulation envoyé
        // après coup — voir AppointmentController::envoyerConfirmationAvecOptionAnnulation().
        if ($request->session()->get('telephone_verifiee_rdv') !== $data['telephone']) {
            return response()->json(['error' => 'Numéro de téléphone non vérifié.'], 403);
        }

        $patient = DB::transaction(function () use ($data, $etablissement) {
            // Le compte a déjà été créé par verifierCodeRdv() au moment de
            // l'envoi du code — on le retrouve, on ne le recrée pas ici.
            $compte = ComptePatient::where('telephone', $data['telephone'])->firstOrFail();

            $patientExistant = $compte->patients()->wherePivot('role', 'titulaire')->first();

            $patient = $patientExistant ?: Patient::create([
                'first_name' => $data['prenom'],
                'last_name' => $data['nom'],
                'gender' => $data['genre'],
            ]);

            if (! $patientExistant) {
                $compte->patients()->attach($patient->id, ['role' => 'titulaire']);
            }

            $dejaVu = $etablissement->patients()->where('patient_id', $patient->id)->exists();
            if ($dejaVu) {
                $etablissement->patients()->updateExistingPivot($patient->id, ['derniere_visite_le' => now()]);
            } else {
                $etablissement->patients()->attach($patient->id, ['premiere_visite_le' => now(), 'derniere_visite_le' => now()]);
            }

            return $patient;
        });

        return response()->json(['patient_id' => $patient->id], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | STAFF — recherche et création rapide, pour le formulaire "Nouveau
    | rendez-vous" de la réception. Pas de vérification OTP ici : contexte
    | authentifié et déjà tracé (activity_logs), à la différence du
    | parcours public.
    |--------------------------------------------------------------------------
    */
    public function rechercheRapide(Request $request)
    {
        $terme = $request->validate(['q' => ['required', 'string', 'min:2']])['q'];

        $patients = Patient::where('first_name', 'LIKE', "%{$terme}%")
            ->orWhere('last_name', 'LIKE', "%{$terme}%")
            ->orWhere('identifiant_national_sante', 'LIKE', "%{$terme}%")
            ->orWhereHas('comptesPatients', fn ($q) => $q->where('telephone', 'LIKE', "%{$terme}%"))
            ->limit(10)
            ->get()
            ->map(fn (Patient $p) => [
                'id' => $p->id,
                'nom' => $p->getFullName(),
                'identifiant' => $p->identifiant_national_sante,
                'telephone' => $p->comptesPatients->first()?->telephone,
            ]);

        return response()->json($patients);
    }

    public function storeRapideStaff(Request $request)
    {
        $data = $request->validate([
            'telephone' => ['required', 'regex:/^[0-9]{9}$/'],
            'prenom' => ['required', 'string', 'max:255'],
            'nom' => ['required', 'string', 'max:255'],
            'genre' => ['required', 'in:Homme,Femme'],
        ], [
            'telephone.regex' => 'Le numéro de téléphone doit contenir exactement 9 chiffres.',
        ]);

        $patient = DB::transaction(function () use ($data) {
            $compte = ComptePatient::firstOrCreate(['telephone' => $data['telephone']], ['statut' => 'actif']);
            $patientExistant = $compte->patients()->wherePivot('role', 'titulaire')->first();

            $patient = $patientExistant ?: Patient::create([
                'first_name' => $data['prenom'],
                'last_name' => $data['nom'],
                'gender' => $data['genre'],
            ]);

            if (! $patientExistant) {
                $compte->patients()->attach($patient->id, ['role' => 'titulaire']);
            }

            if ($etablissement = \App\Support\EtablissementContext::current()) {
                $dejaVu = $etablissement->patients()->where('patient_id', $patient->id)->exists();
                if ($dejaVu) {
                    $etablissement->patients()->updateExistingPivot($patient->id, ['derniere_visite_le' => now()]);
                } else {
                    $etablissement->patients()->attach($patient->id, ['premiere_visite_le' => now(), 'derniere_visite_le' => now()]);
                }
            }

            return $patient;
        });

        return response()->json(['patient_id' => $patient->id, 'nom' => $patient->getFullName()], 201);
    }

    public function show($id)
    {
        $patient = Patient::with(['comptesPatients', 'antecedant'])->findOrFail($id);

        return view('patients.show', compact('patient'));
    }

    public function addFile(Request $request, $id)
    {
        if (! $request->hasFile('file')) {
            return back()->with('error', 'No file selected for upload');
        }

        $patient = Patient::findOrFail($id);

        try {
            $file = $request->file('file');
            $chemin = $file->store('patients/files/' . $patient->id, 'public');

            $patient->files()->create([
                'nom_fichier' => $request->name,
                'chemin_fichier' => $chemin,
                'used_by' => 'accueil'
            ]);

            return back()->with('success', 'Files uploaded successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Error uploading files: ' . $e->getMessage());
        }
    }

    /**
     * Inchangé dans son comportement (bascule le statut actif/inactif) —
     * seulement renommé mentalement : ce n'est pas une édition de fiche,
     * malgré le nom hérité de la route.
     */
    public function edit($id)
    {
        $patient = Patient::findOrFail($id);
        $patient->status = $patient->status ? 0 : 1;
        $patient->save();

        return back()->with('success', 'Status changed successfully.');
    }

    /**
     * CORRIGÉ — `$request->all()` autorisait la modification de n'importe
     * quel champ "fillable" du modèle, y compris `user_id` (l'ancien lien
     * staff). Restreint aux champs qu'un formulaire d'édition de fiche
     * patient a légitimement besoin de modifier.
     */
    public function update(Request $request, $id)
    {
        $patient = Patient::findOrFail($id);

        $data = $request->only([
            'first_name', 'middle_name', 'last_name', 'age', 'gender', 'birth_date',
            'country', 'state', 'district', 'location', 'occupation',
            'description', 'relative_name', 'relative_phone', 'marital_status', 'blood_group',
        ]);

        $patient->update($data);

        return back()->with('success', 'Information du patient modifiée avec succès.');
    }

    public function destroy($id)
    {
        $patient = Patient::findOrFail($id);

        if (count($patient->consultations)) {
            return back()->with('error', 'Le patient ne peut pas être supprimé...');
        }

        $patient->delete();

        return redirect()->route('patient.index')->with('success', 'Le patient est supprimé avec succès');
    }
}
