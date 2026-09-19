<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelAppointmentRequest;
use App\Http\Requests\PrendreRdvPublicRequest;
use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Models\ComptePatient;
use App\Models\Department;
use App\Models\Employee;
use App\Models\MotifRdv;
use App\Models\Patient;
use App\Models\User;
use App\Services\AppointmentBookingService;
use App\Services\AppointmentStatusService;
use App\Services\DisponibiliteService;
use App\Support\CacheDisponibilite;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class AppointmentController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | PAGES
    |--------------------------------------------------------------------------
    */

    public function makeAppointment(\App\Models\Etablissement $etablissement)
    {
        return view('appointments.make_appointment', compact('etablissement'));
    }

    /**
     * Vue RÉCEPTION : tous les rendez-vous de la clinique (tous médecins),
     * filtrables par médecin et recherche libre, par défaut sur aujourd'hui.
     */
    public function index(Request $request, \App\Services\AppointmentQueryService $queryService)
    {
        // Une date invalide dans l'URL ne doit pas provoquer d'erreur 500.
        $date = rescue(
            fn () => Carbon::parse($request->input('date', Carbon::today()->toDateString()))->toDateString(),
            Carbon::today()->toDateString(),
            false
        );
        $request->merge(['date' => $date]);

        $appointments = $queryService->paginatedForReception($request);
        $stats = $queryService->statsForReception($date);
        $medecins = Employee::where('type', 'Doctor')->where('is_active', true)->orderBy('first_name')->get();

        return view('appointments.index', compact('appointments', 'stats', 'medecins', 'date'));
    }

    public function show(Appointment $appointment)
    {
        $appointment->load(['patient', 'employee', 'motifRdv']);

        return view('appointments.show', compact('appointment'));
    }

    /**
     * Volontairement limité à la description : changer le motif ou le
     * médecin d'un rendez-vous déjà pris changerait sa durée et donc sa
     * place dans le planning — plus sûr de faire annuler + reprendre via
     * le même moteur de disponibilité que la création, plutôt que
     * d'ajouter une logique de "déplacement" séparée et moins vérifiée.
     */
    public function update(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $appointment->update($validated);

        return redirect()->route('appointment.show', $appointment)->with('success', 'Rendez-vous mis à jour.');
    }

    /**
     * Version STAFF (accueil qui prend un rdv pour un patient au
     * téléphone ou physiquement présent) — distincte de store() ci-dessus,
     * qui exige la vérification OTP propre au parcours public. Un membre
     * du personnel authentifié est déjà responsabilisé (voir l'audit log,
     * activity_logs), la vérification par SMS du patient n'a pas de sens
     * dans ce contexte.
     */
    public function storeParStaff(StoreAppointmentRequest $request, AppointmentBookingService $bookingService, AppointmentStatusService $statusService)
    {
        try {
            $appointment = $bookingService->book($request->validated());
            $statusService->confirm($appointment);

            return response()->json(['message' => 'Rendez-vous créé avec succès.'], 201);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('Erreur création rendez-vous (staff)', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur lors de la création du rendez-vous.'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ENDPOINTS STAFF — pour le formulaire "Nouveau rendez-vous" côté
    | réception. Mêmes données que les endpoints publics, mais sans passer
    | par {etablissement} dans l'URL : le personnel est authentifié, le
    | global scope (BelongsToEtablissement) filtre déjà automatiquement sur
    | son établissement via Auth::user()->etablissement_id. Pas d'étape
    | OTP non plus — un membre du personnel authentifié est responsabilisé
    | autrement (audit log), la vérification par SMS du patient n'a pas de
    | sens dans ce contexte, voir storeParStaff() ci-dessus.
    |--------------------------------------------------------------------------
    */

    public function staffMotifs()
    {
        $departments = Department::with(['motifsRdv' => fn ($q) => $q->where('actif', true)->orderBy('ordre_affichage')])
            ->orderBy('name')
            ->get()
            ->filter(fn ($d) => $d->motifsRdv->isNotEmpty())
            ->values();

        return response()->json($departments);
    }

    public function staffMedecins(MotifRdv $motifRdv)
    {
        $medecins = Employee::where('department_id', $motifRdv->department_id)
            ->where('type', 'Doctor')
            ->where('is_active', true)
            ->get()
            ->filter(fn (Employee $m) => $m->peutPratiquerMotif($motifRdv))
            ->map(fn (Employee $m) => ['id' => $m->id, 'nom' => 'Dr. ' . $m->full_name])
            ->values();

        return response()->json($medecins);
    }

    public function staffDatesDisponibles(Request $request, DisponibiliteService $disponibilite)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists_etablissement:employees,id'],
            'motif_rdv_id' => ['required', 'exists_etablissement:motifs_rdv,id'],
        ]);

        $medecin = Employee::findOrFail($data['employee_id']);
        $motif = MotifRdv::findOrFail($data['motif_rdv_id']);

        return response()->json($disponibilite->joursDisponibles($medecin, Carbon::today(), 14, $motif));
    }

    public function staffCreneaux(Request $request, \App\Services\DisponibiliteService $disponibilite)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists_etablissement:employees,id'],
            'motif_rdv_id' => ['required', 'exists_etablissement:motifs_rdv,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $medecin = Employee::findOrFail($data['employee_id']);
        $motif = MotifRdv::findOrFail($data['motif_rdv_id']);

        $creneaux = $disponibilite->creneauxDisponibles($medecin, Carbon::parse($data['date']), $motif);

        return response()->json($creneaux->map(fn ($c) => ['debut' => $c['debut']->format('H:i')]));
    }

    /**
     * Prise de rdv PUBLIQUE.
     *
     * CORRIGÉ 21/09/2026 — le client n'envoie plus de patient_id : le dossier
     * est retrouvé côté serveur à partir du téléphone (compte titulaire).
     * Un nouveau patient a été créé juste avant par storeRapide(), après
     * vérification OTP du numéro ; un patient déjà connu réserve sans OTP
     * (choix assumé, compensé par le lien d'annulation du SMS de confirmation).
     */
    public function store(\App\Models\Etablissement $etablissement, PrendreRdvPublicRequest $request, AppointmentBookingService $bookingService, AppointmentStatusService $statusService)
    {
        $data = $request->validated();

        // Défense en profondeur : médecin et motif de l'établissement de l'URL.
        $employeeValide = Employee::where('etablissement_id', $etablissement->id)
            ->where('id', $data['employee_id'])->exists();
        $motifValide = MotifRdv::where('etablissement_id', $etablissement->id)
            ->where('id', $data['motif_rdv_id'])->exists();

        if (! $employeeValide || ! $motifValide) {
            return response()->json(['error' => 'Requête invalide pour cet établissement.'], 422);
        }

        $patient = $this->patientTitulaire($data['telephone']);

        if (! $patient) {
            return response()->json(['error' => 'Aucun dossier patient pour ce numéro. Merci de recommencer la saisie de vos informations.'], 422);
        }

        unset($data['telephone']);
        $data['patient_id'] = $patient->id;

        try {
            $appointment = $bookingService->book($data);
            $statusService->confirm($appointment);

            // Le patient devient « suivi » par cet établissement : la réception
            // doit pouvoir le retrouver par son nom. Pas de date de visite :
            // réserver n'est pas venir.
            $etablissement->patients()->syncWithoutDetaching([$patient->id]);

            // Le SMS de confirmation (avec lien d'annulation) part via
            // AppointmentObserver → HandleAppointmentStatusChange. Ne pas en
            // renvoyer un second ici.

            return response()->json([
                'message' => 'Rendez-vous créé avec succès.',
                'appointment' => [
                    'date' => $appointment->appointment_date->format('d/m/Y'),
                    'time' => $appointment->appointment_time->format('H:i'),
                    'doctor' => 'Dr. ' . $appointment->employee->first_name . ' ' . $appointment->employee->last_name,
                    'motif' => $appointment->motifRdv->nom,
                    'duree_minutes' => $appointment->duree_minutes,
                    'status' => $appointment->status,
                ]
            ], 201);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('Erreur création rendez-vous', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur lors de la création du rendez-vous.'], 500);
        }
    }

    /**
     * Page ouverte depuis le SMS — aucune authentification, la signature de
     * l'URL fait foi. GET affiche la confirmation, POST annule réellement
     * (même URL signée que celle envoyée par SMS).
     */
    public function gererAnnulationNonReconnue(Request $request, \App\Models\Etablissement $etablissement, Appointment $appointment, AppointmentStatusService $statusService)
    {
        abort_unless($request->hasValidSignature(), 403, 'Ce lien a expiré ou est invalide.');
        abort_unless((int) $appointment->etablissement_id === (int) $etablissement->id, 404);

        if ($request->isMethod('post')) {
            if (in_array($appointment->status, ['pending', 'confirmed'])) {
                $statusService->cancel($appointment, $request->query('motif') === 'empeche'
                    ? 'Patient empêché — créneau libéré via le rappel SMS'
                    : 'Annulé via le lien SMS — rendez-vous non reconnu par le patient');
            }

            return view('appointments.annulation-confirmee', ['empeche' => $request->query('motif') === 'empeche', 'appointment' => $appointment]);
        }

        return view('appointments.annulation-non-reconnue', ['appointment' => $appointment, 'empeche' => $request->query('motif') === 'empeche']);
    }

    /**
     * CORRIGÉ — cette méthode avait perdu sa propre déclaration lors d'une
     * précédente édition (le bloc try/catch se retrouvait orphelin juste
     * après gererAnnulationNonReconnue(), sans "public function cancel(...)"
     * au-dessus) — erreur de syntaxe PHP. Restaurée ici.
     */
    public function cancel(CancelAppointmentRequest $request, Appointment $appointment, AppointmentStatusService $statusService)
    {
        try {
            $statusService->cancel($appointment, $request->input('reason'));

            return back()->with('success', 'Rendez-vous annulé avec succès.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Erreur annulation rendez-vous', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', "Erreur lors de l'annulation du rendez-vous.");
        }
    }

    /**
     * Reprogrammation (même médecin, même motif, nouvelle date/heure). Le
     * formulaire d'accompagnement (staffDatesDisponibles/staffCreneaux,
     * déjà existants) fournit les créneaux valides.
     */
    public function reprogrammer(Request $request, Appointment $appointment, AppointmentBookingService $bookingService)
    {
        $data = $request->validate([
            'appointment_date' => ['required', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required', 'date_format:H:i'],
        ]);

        try {
            $bookingService->reprogrammer($appointment, $data['appointment_date'], $data['appointment_time']);

            return back()->with('success', 'Rendez-vous reprogrammé. Le patient est prévenu par SMS du nouvel horaire.');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Erreur reprogrammation rendez-vous', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Erreur lors de la reprogrammation du rendez-vous.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ENDPOINTS PUBLICS — consommés par la page de prise de rdv (aucun
    | utilisateur authentifié à ce stade, voir routes/rdv-public.php)
    |--------------------------------------------------------------------------
    */

    /**
     * Motifs actifs, groupés par département — c'est l'écran d'accueil du
     * parcours patient : on choisit directement "ce qu'on vient faire",
     * le département reste un regroupement visuel, pas une étape à part.
     */
    public function getMotifs(\App\Models\Etablissement $etablissement)
    {
        // Filtre explicite en plus du global scope (défense en
        // profondeur) : sur une page publique sans utilisateur
        // authentifié, ne jamais dépendre d'un seul mécanisme d'isolation.
        $departments = Department::where('etablissement_id', $etablissement->id)
            ->with(['motifsRdv' => fn ($q) => $q->where('actif', true)->orderBy('ordre_affichage')])
            ->orderBy('name')
            ->get()
            ->filter(fn ($d) => $d->motifsRdv->isNotEmpty())
            ->values();

        return response()->json($departments);
    }

    /**
     * Médecins compatibles avec un motif donné (département + règle de
     * compatibilité de Employee::peutPratiquerMotif()).
     */
    public function getProfessionalsForMotif(\App\Models\Etablissement $etablissement, Request $request, MotifRdv $motifRdv)
    {
        abort_unless($motifRdv->etablissement_id === $etablissement->id, 404);

        $medecins = Employee::where('department_id', $motifRdv->department_id)
            ->where('etablissement_id', $etablissement->id)
            ->where('type', 'Doctor')
            ->where('is_active', true)
            ->get()
            ->filter(fn (Employee $m) => $m->peutPratiquerMotif($motifRdv))
            ->map(fn (Employee $m) => [
                'id' => $m->id,
                'nom' => 'Dr. ' . $m->full_name,
                'speciality' => $m->speciality,
            ])
            ->values();

        return response()->json($medecins);
    }

    /**
     * Jours ayant au moins un créneau libre — alimente la bande de jours.
     *
     * Cache 60 s, clé VERSIONNÉE par médecin (CacheDisponibilite) : toute
     * réservation, annulation, congé ou pause invalide immédiatement le cache
     * de ce médecin. La vérification qui fait foi reste celle, non cachée,
     * d'AppointmentBookingService au moment de réserver.
     */
    public function getAvailableDates(\App\Models\Etablissement $etablissement, Request $request, DisponibiliteService $disponibilite)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists_etablissement:employees,id'],
            'motif_rdv_id' => ['required', 'exists_etablissement:motifs_rdv,id'],
            'jours' => ['nullable', 'integer', 'min:1', 'max:60'],
        ]);

        $medecin = Employee::where('etablissement_id', $etablissement->id)->findOrFail($data['employee_id']);
        $motif = MotifRdv::where('etablissement_id', $etablissement->id)->findOrFail($data['motif_rdv_id']);
        $nbJours = (int) ($data['jours'] ?? 14);

        $cle = CacheDisponibilite::prefixe($medecin->id) . ":dates:{$motif->id}:{$nbJours}:" . now()->format('Y-m-d');

        $joursDisponibles = Cache::remember($cle, 60, fn () => $disponibilite
            ->joursDisponibles($medecin, Carbon::today(), $nbJours, $motif)
            ->all());

        return response()->json(array_values($joursDisponibles));
    }

    /**
     * CORRIGÉ 21/09/2026 — ne renvoie plus NI le nom NI l'identifiant du
     * patient. Sans vérification de possession du numéro, ces informations
     * permettaient de savoir qu'une personne est suivie (donnée de santé) et
     * de réserver en son nom. On indique seulement si un dossier existe, pour
     * orienter le parcours (connu → récapitulatif ; nouveau → formulaire + OTP).
     */
    public function checkPatient(Request $request)
    {
        $phone = preg_replace('/\D/', '', $request->phone ?? '');

        validator(
            ['phone' => $phone],
            ['phone' => ['required', 'regex:/^[0-9]{9}$/']],
            ['phone.regex' => 'Le numéro de téléphone doit contenir exactement 9 chiffres.']
        )->validate();

        // Freine l'énumération de numéros par un script.
        $cle = 'rdv-verifier-patient:' . $request->ip();
        if (RateLimiter::tooManyAttempts($cle, 20)) {
            return response()->json(['error' => 'Trop de recherches. Réessayez dans quelques minutes.'], 429);
        }
        RateLimiter::hit($cle, 600);

        $existe = (bool) $this->patientTitulaire($phone);

        return response()->json(['exists' => $existe, 'is_patient' => $existe]);
    }

    /*
    |--------------------------------------------------------------------------
    | VÉRIFICATION DU NUMÉRO — obligatoire uniquement pour la création d'un
    | nouveau dossier patient (storeRapide()) désormais, pas pour chaque
    | soumission de rdv (store()) — voir les commentaires respectifs.
    |--------------------------------------------------------------------------
    */

    /**
     * CORRIGÉ 21/09/2026 :
     *  - le code est stocké en CACHE, plus dans comptes_patients.code_otp :
     *    demander un code de rdv écrasait le code de connexion au portail ;
     *  - aucun ComptePatient n'est créé tant que le numéro n'est pas vérifié
     *    (avant, chaque numéro essayé créait un compte) ;
     *  - limite par IP en plus de la limite par numéro : sans elle, un script
     *    pouvait envoyer un SMS à des milliers de numéros différents et vider
     *    le crédit SMS.
     */
    public function envoyerCodeRdv(Request $request, \App\Services\SmsService $sms, \App\Services\OtpService $otp)
    {
        $data = $request->validate(['telephone' => ['required', 'regex:/^[0-9]{9}$/']]);

        $cleNumero = 'otp-rdv:' . $data['telephone'];
        $cleIp = 'otp-rdv-ip:' . $request->ip();

        if (RateLimiter::tooManyAttempts($cleNumero, 3) || RateLimiter::tooManyAttempts($cleIp, 10)) {
            return response()->json(['error' => 'Trop de demandes de code. Réessayez dans quelques minutes.'], 429);
        }
        RateLimiter::hit($cleNumero, 600);
        RateLimiter::hit($cleIp, 3600);

        $code = $otp->generer();
        Cache::put($this->cleOtpRdv($data['telephone']), $otp->hacher($code), now()->addMinutes(5));

        // Page publique d'une clinique : son expéditeur, son journal. Code masqué au journal.
        $sms->sendSms($data['telephone'], "Votre code pour confirmer le rendez-vous : {$code} (valable 5 minutes).", ['type' => 'code_rdv', 'masquer' => true]);

        return response()->json(['message' => 'Code envoyé.']);
    }

    public function verifierCodeRdv(Request $request, \App\Services\OtpService $otp)
    {
        $data = $request->validate([
            'telephone' => ['required', 'regex:/^[0-9]{9}$/'],
            'code' => ['required', 'string', 'max:10'],
        ]);

        $cle = 'otp-rdv-verif:' . $data['telephone'];
        if (RateLimiter::tooManyAttempts($cle, 5)) {
            return response()->json(['error' => 'Trop de tentatives. Redemandez un code.'], 429);
        }

        $hash = Cache::get($this->cleOtpRdv($data['telephone']));

        if (! $otp->verifier($data['code'], $hash)) {
            RateLimiter::hit($cle, 600);
            return response()->json(['error' => 'Code invalide ou expiré.'], 422);
        }

        RateLimiter::clear($cle);
        Cache::forget($this->cleOtpRdv($data['telephone']));

        // Le compte n'est créé QU'APRÈS preuve de possession du numéro.
        $compte = ComptePatient::firstOrCreate(
            ['telephone' => $data['telephone']],
            ['statut' => 'actif']
        );

        if (! $compte->telephone_verifie_le) {
            $compte->update(['telephone_verifie_le' => now()]);
        }

        // Le VRAI verrou : sans cette clé de session, storeRapide() refuse de
        // créer un dossier pour ce numéro.
        $request->session()->put('telephone_verifiee_rdv', $compte->telephone);

        return response()->json(['message' => 'Numéro vérifié.']);
    }

    protected function cleOtpRdv(string $telephone): string
    {
        return 'otp-rdv:code:' . $telephone;
    }

    /** Dossier du titulaire du compte associé à ce téléphone, s'il existe. */
    protected function patientTitulaire(string $telephone): ?Patient
    {
        return ComptePatient::where('telephone', $telephone)->first()
            ?->patients()->wherePivot('role', 'titulaire')->first();
    }
}
