<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelAppointmentRequest;
use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\Employee;
use App\Models\MotifRdv;
use App\Models\Patient;
use App\Models\User;
use App\Services\AppointmentBookingService;
use App\Services\AppointmentStatusService;
use App\Services\DisponibiliteService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
     * pas ceux d'un seul praticien — c'est ce qui manquait concrètement
     * (cette méthode pointait vers une vue `appointments.index` qui
     * n'existait pas du tout dans le projet). Filtrable par médecin et par
     * recherche libre (patient, téléphone, motif), par défaut sur la
     * journée en cours.
     */
    public function index(Request $request, \App\Services\AppointmentQueryService $queryService)
    {
        $date = $request->input('date', Carbon::today()->toDateString());

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

    public function staffDatesDisponibles(Request $request, \App\Services\DisponibiliteService $disponibilite)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'motif_rdv_id' => ['required', 'exists:motifs_rdv,id'],
        ]);

        $medecin = Employee::findOrFail($data['employee_id']);
        $motif = MotifRdv::findOrFail($data['motif_rdv_id']);

        $jours = collect(range(0, 13))
            ->map(fn ($i) => Carbon::today()->addDays($i))
            ->filter(fn (Carbon $d) => $disponibilite->creneauxDisponibles($medecin, $d, $motif)->isNotEmpty())
            ->map(fn (Carbon $d) => $d->toDateString())
            ->values();

        return response()->json($jours);
    }

    public function staffCreneaux(Request $request, \App\Services\DisponibiliteService $disponibilite)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'motif_rdv_id' => ['required', 'exists:motifs_rdv,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $medecin = Employee::findOrFail($data['employee_id']);
        $motif = MotifRdv::findOrFail($data['motif_rdv_id']);

        $creneaux = $disponibilite->creneauxDisponibles($medecin, Carbon::parse($data['date']), $motif);

        return response()->json($creneaux->map(fn ($c) => ['debut' => $c['debut']->format('H:i')]));
    }

    public function store(\App\Models\Etablissement $etablissement, StoreAppointmentRequest $request, AppointmentBookingService $bookingService, AppointmentStatusService $statusService)
    {
        // Défense en profondeur, même principe qu'ailleurs dans ce
        // contrôleur : vérifie explicitement que le médecin et le motif
        // soumis appartiennent bien à l'établissement de l'URL, plutôt que
        // de dépendre uniquement du global scope pour ce point d'entrée
        // qui écrit des données (création d'un rendez-vous).
        $employeeValide = Employee::where('etablissement_id', $etablissement->id)
            ->where('id', $request->input('employee_id'))->exists();
        $motifValide = MotifRdv::where('etablissement_id', $etablissement->id)
            ->where('id', $request->input('motif_rdv_id'))->exists();

        if (! $employeeValide || ! $motifValide) {
            return response()->json(['error' => 'Requête invalide pour cet établissement.'], 422);
        }

        // Pas de vérification OTP ici, par choix assumé (pas un oubli) :
        // l'OTP protège désormais la CRÉATION d'un dossier patient (voir
        // PatientController::storeRapide()), pas chaque soumission de rdv.
        // Un patient déjà connu ne revérifie plus son numéro à chaque
        // rendez-vous. Le risque résiduel accepté : quelqu'un connaissant
        // le numéro d'un patient déjà enregistré peut réserver en son nom
        // sans preuve de possession. Compensé par le lien d'annulation
        // intégré au SMS de confirmation (voir SendAppointmentReminderJob)
        // — une détection après coup, pas une prévention, mais qui
        // referme la fenêtre d'exposition rapidement et sans aucune
        // friction pour le patient légitime.

        try {
            $appointment = $bookingService->book($request->validated());
            $statusService->confirm($appointment);

            // Le SMS de confirmation (avec le lien d'annulation intégré)
            // part déjà tout seul via AppointmentObserver ->
            // HandleAppointmentStatusChange -> SendAppointmentReminderJob,
            // déclenché par confirm() ci-dessus. Ne PAS en renvoyer un
            // deuxième ici — c'était le doublon signalé.

            return response()->json([
                'message' => 'Rendez-vous créé avec succès.',
                'appointment' => [
                    'id' => $appointment->id,
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
     * Page ouverte depuis le SMS — AUCUNE authentification requise, la
     * signature de l'URL fait foi (même principe que le lien de
     * confirmation de consentement). Une seule route, GET et POST
     * confondus (même URL signée que celle envoyée par SMS) : GET affiche
     * la page de confirmation, POST annule réellement. Les séparer sur
     * deux URLs aurait cassé la signature du lien envoyé par SMS quand le
     * formulaire soumet vers la même adresse.
     */
    public function gererAnnulationNonReconnue(Request $request, \App\Models\Etablissement $etablissement, Appointment $appointment, AppointmentStatusService $statusService)
    {
        abort_unless($request->hasValidSignature(), 403, 'Ce lien a expiré ou est invalide.');
        abort_unless($appointment->employee->etablissement_id === $etablissement->id, 404);

        if ($request->isMethod('post')) {
            if (in_array($appointment->status, ['pending', 'confirmed'])) {
                $statusService->cancel($appointment, "Annulé via le lien SMS — rendez-vous non reconnu par le patient");
            }

            return view('appointments.annulation-confirmee');
        }

        return view('appointments.annulation-non-reconnue', compact('appointment'));
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

            return back()->with('success', 'Rendez-vous reprogrammé. Le patient doit être prévenu du nouvel horaire.');
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
     * Jours ayant au moins un créneau libre, sur une fenêtre donnée —
     * alimente le sélecteur de date de l'interface (bande de jours plutôt
     * qu'un calendrier mensuel complet, voir le choix de design).
     */
    public function getAvailableDates(\App\Models\Etablissement $etablissement, Request $request, DisponibiliteService $disponibilite)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'motif_rdv_id' => ['required', 'exists:motifs_rdv,id'],
            'jours' => ['nullable', 'integer', 'min:1', 'max:60'],
        ]);

        $medecin = Employee::where('etablissement_id', $etablissement->id)->findOrFail($data['employee_id']);
        $motif = MotifRdv::where('etablissement_id', $etablissement->id)->findOrFail($data['motif_rdv_id']);
        $nbJours = $data['jours'] ?? 14;

        // Mis en cache brièvement : ce calcul boucle sur jusqu'à 60 jours
        // de l'algorithme de disponibilité. À l'échelle visée (200
        // cliniques, ~50 rdv/jour/médecin), sans cache, ce point devient à
        // la fois un coût réel en heures de pointe et une cible facile de
        // sur-sollicitation (répéter l'appel force un recalcul complet à
        // chaque fois). 60 secondes suffit : un rendez-vous qui vient
        // d'être pris par quelqu'un d'autre reste visible au pire une
        // minute de plus, sans risque réel de double réservation — la
        // vérification faisant foi reste celle, non cachée, d'
        // AppointmentBookingService au moment de la réservation.
        $cle = "dispo:dates:{$medecin->id}:{$motif->id}:{$nbJours}:" . now()->format('Y-m-d');
        $joursDisponibles = \Illuminate\Support\Facades\Cache::remember($cle, 60, function () use ($medecin, $motif, $disponibilite, $nbJours) {
            return collect(range(0, $nbJours - 1))
                ->map(fn ($i) => Carbon::today()->addDays($i))
                ->filter(fn (Carbon $date) => $disponibilite->creneauxDisponibles($medecin, $date, $motif)->isNotEmpty())
                ->map(fn (Carbon $date) => $date->toDateString())
                ->values();
        });

        return response()->json($joursDisponibles);
    }

    /**
     * CORRIGÉ — cette méthode vérifiait l'ancien lien Patient::user_id
     * (le modèle `User` du personnel), un mécanisme distinct et
     * maintenant obsolète depuis l'introduction de `ComptePatient` au
     * Pilier B. Les deux ne se recoupaient pas : un patient créé via
     * `storeRapide()` (ComptePatient) n'aurait jamais été retrouvé ici.
     * Un seul système d'identité patient désormais : ComptePatient.
     */
    public function checkPatient(Request $request)
    {
        $phone = preg_replace('/\D/', '', $request->phone ?? '');

        validator(
            ['phone' => $phone],
            ['phone' => ['required', 'regex:/^[0-9]{9}$/']],
            ['phone.regex' => 'Le numéro de téléphone doit contenir exactement 9 chiffres.']
        )->validate();

        $compte = \App\Models\ComptePatient::where('telephone', $phone)->first();
        $patient = $compte?->patients()->wherePivot('role', 'titulaire')->first();

        if (!$compte || !$patient) {
            return response()->json(['exists' => false, 'is_patient' => false]);
        }

        return response()->json([
            'exists' => true,
            'is_patient' => true,
            'patient' => [
                'id' => $patient->id,
                'name' => $patient->getFullName(),
                'phone' => $compte->telephone,
                'first_name' => $patient->first_name,
                'last_name' => $patient->last_name,
            ]
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | VÉRIFICATION DU NUMÉRO — obligatoire uniquement pour la création d'un
    | nouveau dossier patient (storeRapide()) désormais, pas pour chaque
    | soumission de rdv (store()) — voir les commentaires respectifs.
    |--------------------------------------------------------------------------
    */

    public function envoyerCodeRdv(Request $request, \App\Services\SmsService $sms, \App\Services\OtpService $otp)
    {
        $data = $request->validate(['telephone' => ['required', 'regex:/^[0-9]{9}$/']]);

        // Ancré sur le numéro, pas seulement sur l'IP — voir l'analyse
        // jointe sur pourquoi un throttle IP seul est insuffisant ici.
        $cle = 'otp-rdv:' . $data['telephone'];
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($cle, 3)) {
            return response()->json(['error' => 'Trop de tentatives pour ce numéro. Réessayez dans quelques minutes.'], 429);
        }
        \Illuminate\Support\Facades\RateLimiter::hit($cle, 600);

        // On crée (ou réutilise) le compte MAINTENANT, avant même de savoir
        // si c'est un nouveau patient — c'est ce qui permet de vérifier la
        // possession du numéro avant de créer le moindre dossier médical
        // en son nom.
        $compte = \App\Models\ComptePatient::firstOrCreate(
            ['telephone' => $data['telephone']],
            ['statut' => 'actif']
        );

        $code = $otp->generer();
        $compte->update([
            'code_otp' => $otp->hacher($code),
            'otp_expire_le' => now()->addMinutes(5),
        ]);

        $sms->sendSms($compte->telephone, "Votre code pour confirmer le rendez-vous : {$code} (valable 5 minutes).");

        return response()->json(['message' => 'Code envoyé.']);
    }

    public function verifierCodeRdv(Request $request, \App\Services\OtpService $otp)
    {
        $data = $request->validate([
            'telephone' => ['required', 'regex:/^[0-9]{9}$/'],
            'code' => ['required', 'string'],
        ]);

        $cle = 'otp-rdv-verif:' . $data['telephone'];
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($cle, 5)) {
            return response()->json(['error' => 'Trop de tentatives. Redemandez un code.'], 429);
        }

        $compte = \App\Models\ComptePatient::where('telephone', $data['telephone'])->first();

        if (! $compte || ! $compte->otp_expire_le || $compte->otp_expire_le->isPast()
            || ! $otp->verifier($data['code'], $compte->code_otp)) {
            \Illuminate\Support\Facades\RateLimiter::hit($cle, 600);
            return response()->json(['error' => 'Code invalide ou expiré.'], 422);
        }

        \Illuminate\Support\Facades\RateLimiter::clear($cle);

        $compte->update([
            'code_otp' => null,
            'otp_expire_le' => null,
            'telephone_verifie_le' => $compte->telephone_verifie_le ?: now(),
        ]);

        // Le VRAI verrou : sans cette clé de session, storeRapide()
        // n'acceptera pas ce numéro pour créer un dossier — voir cette
        // méthode dans PatientController.
        $request->session()->put('telephone_verifiee_rdv', $compte->telephone);

        return response()->json(['message' => 'Numéro vérifié.']);
    }
}
