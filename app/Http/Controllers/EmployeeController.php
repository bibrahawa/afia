<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Department;
use App\Models\EmployeeAvailability;
use App\Models\EmployeeLeave;
use App\Models\AppointmentSlot;
use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmployeeController extends Controller
{
    public function __construct()
    {
        // $this->middleware('auth');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $employees = Employee::with(['department', 'user'])->orderByDesc('is_active')->orderBy('first_name')->get();
        $departments = Department::select('id', 'name')->orderBy('name')->get();

        return view('employees.index', compact('employees', 'departments'));
    }

    /**
     * Mon profil (utilisateur connecté).
     * CORRIGÉ — la page affichait des données d'exemple (« Lueilwitz, Wisoky and Leuschke »,
     * « k.anderson@example.com », « Country : USA ») et des liens vers Twitter.
     */
    public function profile()
    {
        $employee = auth()->user()?->employee?->load('department');

        return view('employees.profile', ['employee' => $employee, 'estMonProfil' => true]);
    }

    public function create(\App\Services\Personnel\AccesPersonnelService $acces)
    {
        $departments = Department::select('id', 'name')->orderBy('name')->get();
        $roles = auth()->user()?->can('users.create') ? $acces->rolesAttribuables(auth()->user()) : collect();

        return view('employees.create', compact('departments', 'roles'));
    }

    /**
     * Lot E1 — La fiche ET, si demandé, son accès à Hali en une seule étape
     * (auparavant : écran Personnel, puis écran Utilisateurs, avec deux saisies
     * du nom et des types incohérents entre les deux).
     */
    public function store(Request $request, \App\Services\Personnel\AccesPersonnelService $acces)
    {
        $donnees = $this->valider($request);
        $avecAcces = $request->boolean('acces') && $request->user()?->can('users.create');
        $donneesAcces = $avecAcces ? $this->validerAcces($request) : null;

        $donnees['first_name'] = $this->sansTitre($donnees['first_name']);
        $donnees['is_active'] = true;

        // Lot R : le rôle est vérifié AVANT toute écriture, puis fiche et accès sont
        // enregistrés l'un après l'autre. Avant, tout était dans une même transaction
        // qui englobait aussi l'envoi du SMS (jusqu'à 8 s de transaction ouverte, et un
        // SMS déjà parti si l'enregistrement échouait ensuite).
        if ($donneesAcces && ! $acces->peutAttribuer($request->user(), $donneesAcces['role'])) {
            return back()->withInput()->withErrors(['role' => 'Vous ne pouvez pas attribuer ce rôle.']);
        }

        $employe = Employee::create($donnees);
        $resultatAcces = null;

        if ($donneesAcces) {
            try {
                // Compte créé dans sa propre transaction ; SMS envoyé une fois le compte enregistré.
                $resultatAcces = $acces->creer($employe, $donneesAcces, $request->user());
            } catch (\Throwable $e) {
                report($e);

                return redirect()->route('employee.edit', $employe->id)
                    ->with('error', "La fiche de {$employe->nom_affiche} est créée, mais pas son accès. Créez-le depuis sa fiche (bloc « Accès à Hali »).");
            }
        }

        $retour = redirect()->route('employee.edit', $employe->id)->with('success', $employe->nom_affiche . ' ajouté(e) au personnel.');

        return $donneesAcces ? $this->retourAcces($retour, $resultatAcces ?? null) : $retour;
    }

    // ------------------------------------------------------------ Accès (lot E1)

    public function creerAcces(Request $request, $id, \App\Services\Personnel\AccesPersonnelService $acces)
    {
        $employe = Employee::findOrFail($id);
        if ($employe->user_id) {
            return back()->with('error', 'Cette personne a déjà un accès.');
        }

        $resultat = $acces->creer($employe, $this->validerAcces($request), $request->user());

        return $this->retourAcces(back()->with('success', 'Accès créé pour ' . $employe->nom_affiche . '.'), $resultat);
    }

    public function modifierAcces(Request $request, $id, \App\Services\Personnel\AccesPersonnelService $acces)
    {
        $employe = Employee::with('user')->findOrFail($id);
        abort_unless($employe->user, 404);

        $acces->modifier($employe->user, $employe, $this->validerAcces($request, $employe->user->id), $request->user());

        return back()->with('success', 'Accès de ' . $employe->nom_affiche . ' mis à jour.');
    }

    public function reinitialiserAcces($id, \App\Services\Personnel\AccesPersonnelService $acces)
    {
        $employe = Employee::with('user')->findOrFail($id);
        abort_unless($employe->user, 404);

        [$motDePasse, $parti] = $acces->reinitialiser($employe->user);

        return $this->retourAcces(back()->with('success', 'Mot de passe de ' . $employe->nom_affiche . ' réinitialisé.'), [$employe->user, $motDePasse, $parti]);
    }

    public function basculerAcces($id)
    {
        $employe = Employee::with('user')->findOrFail($id);
        abort_unless($employe->user, 404);

        if ($employe->user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas suspendre votre propre accès.');
        }

        $actif = ! ($employe->user->status === null || (bool) $employe->user->status);
        $employe->user->forceFill(['status' => $actif])->save();

        return back()->with('success', $actif
            ? 'Accès de ' . $employe->nom_affiche . ' réactivé.'
            : 'Accès de ' . $employe->nom_affiche . ' suspendu : sa session est fermée immédiatement.');
    }

    /**
     * Si le SMS n'est pas parti (numéro erroné, service indisponible), le mot de
     * passe provisoire est affiché UNE fois à l'écran pour être transmis de vive voix.
     */
    private function retourAcces($redirection, ?array $resultat)
    {
        if (! $resultat) {
            return $redirection;
        }
        [$utilisateur, $motDePasse, $parti] = $resultat;

        return $parti
            ? $redirection->with('info', "Identifiants envoyés par SMS au {$utilisateur->phone}.")
            : $redirection->with('mot_de_passe_provisoire', ['telephone' => $utilisateur->phone, 'mot_de_passe' => $motDePasse]);
    }

    private function validerAcces(Request $request, ?int $ignorerUtilisateur = null): array
    {
        $donnees = $request->validate([
            'telephone' => ['required', 'regex:/^[0-9]{9}$/', \Illuminate\Validation\Rule::unique('users', 'phone')->ignore($ignorerUtilisateur)],
            'email' => ['nullable', 'email', 'max:255', \Illuminate\Validation\Rule::unique('users', 'email')->ignore($ignorerUtilisateur)],
            'role' => ['required', 'string', 'exists:roles,name'],
            'envoyer_sms' => ['nullable', 'boolean'],
        ], [
            'telephone.required' => 'Indiquez le téléphone qui servira d\'identifiant.',
            'telephone.regex' => 'Le téléphone doit contenir 9 chiffres (ex. 622000000).',
            'telephone.unique' => 'Ce numéro est déjà utilisé par un autre compte.',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée par un autre compte.',
            'role.required' => 'Choisissez le rôle (ce que la personne pourra faire).',
        ]);
        $donnees['envoyer_sms'] = $request->boolean('envoyer_sms', true);

        return $donnees;
    }

    public function show($id)
    {
        $employee = Employee::with('department')->findOrFail($id);

        return view('employees.profile', ['employee' => $employee, 'estMonProfil' => auth()->user()?->employee?->id === $employee->id]);
    }

    public function edit($id, \App\Services\Personnel\AccesPersonnelService $acces)
    {
        $employee = Employee::with('user.roles')->findOrFail($id);
        $departments = Department::orderBy('name')->get();
        $roles = $acces->rolesAttribuables(auth()->user());

        return view('employees.edit', compact('employee', 'departments', 'roles'));
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);
        $donnees = $this->valider($request);

        // CORRIGÉ — « DR. » était ajouté au prénom à CHAQUE enregistrement
        // (« DR.DR.DR. Alpha ») : le prénom est gardé seul, nom_affiche ajoute le titre.
        $donnees['first_name'] = $this->sansTitre($donnees['first_name']);
        $donnees['is_active'] = $request->boolean('is_active');

        $employee->update($donnees);

        return redirect()->route('employee.index')->with('success', 'Fiche de ' . $employee->nom_affiche . ' mise à jour.');
    }

    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);

        // CORRIGÉ — testait une relation « doctor » inexistante (erreur à chaque suppression).
        // Un soignant qui a déjà reçu des patients garde son historique : on le désactive.
        $aUnHistorique = $employee->appointments()->exists()
            || \App\Models\Consultation::where('medecin_id', $employee->id)->exists()
            || $employee->user_id;

        if ($aUnHistorique) {
            return back()->with('error', "{$employee->nom_affiche} a un compte ou un historique de soins : il ne peut pas être supprimé. Désactivez sa fiche à la place.");
        }

        $employee->delete();

        return redirect()->route('employee.index')->with('success', 'Employé supprimé.');
    }

    /** Changement de mot de passe depuis « Mon profil ». */
    public function motDePasse(Request $request)
    {
        $donnees = $request->validate([
            'mot_de_passe_actuel' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:mot_de_passe_actuel'],
        ], [
            'mot_de_passe_actuel.current_password' => 'Le mot de passe actuel est incorrect.',
            'password.min' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'Les deux saisies du nouveau mot de passe ne correspondent pas.',
            'password.different' => 'Choisissez un mot de passe différent de l\'actuel.',
        ]);

        $etaitProvisoire = (bool) $request->user()->doit_changer_mot_de_passe;
        $request->user()->forceFill([
            'password' => \Illuminate\Support\Facades\Hash::make($donnees['password']),
            'doit_changer_mot_de_passe' => false,
        ])->save();

        // Mot de passe provisoire remplacé : l'application est de nouveau accessible.
        return $etaitProvisoire
            ? redirect()->to(\App\Support\PageAccueil::url($request->user()))->with('success', 'Mot de passe enregistré. Bienvenue !')
            : back()->with('success', 'Mot de passe modifié.');
    }

    private function valider(Request $request): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'type' => ['required', 'in:' . implode(',', array_keys(Employee::TYPES))],
            'department_id' => ['required', 'exists_etablissement:departments,id'],
            'speciality' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'education' => ['nullable', 'string', 'max:2000'],
            'certificate' => ['nullable', 'string', 'max:2000'],
            'description' => ['nullable', 'string', 'max:2000'],
        ], [
            'first_name.required' => 'Indiquez le prénom.',
            'last_name.required' => 'Indiquez le nom.',
            'department_id.required' => 'Choisissez le département.',
        ]);
    }

    private function sansTitre(string $prenom): string
    {
        // « Dr », « Dr. », « DR.DR. », « Docteur » en tête ; jamais « Drissa ».
        return trim(preg_replace('/^\s*(?:(?:docteur|dr)\s*\.\s*|(?:docteur|dr)\s+)+/iu', '', $prenom));
    }

    public function dashboard()
    {
        $professional = Employee::where('email', Auth::user()->email)->first();

        if (!$professional) {
            abort(403, 'Accès non autorisé');
        }

        $todayAppointments = Appointment::where('employee_id', $professional->id)
            ->today()
            ->with('patient')
            ->orderBy('appointment_time')
            ->get();

        $upcomingAppointments = Appointment::where('employee_id', $professional->id)
            ->upcoming()
            ->where('appointment_date', '>', now()->toDateString())
            ->take(10)
            ->with('patient')
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get();

        return view('professional.dashboard', compact('professional', 'todayAppointments', 'upcomingAppointments'));
    }

    public function availability()
    {
        $availabilities = EmployeeAvailability::where('employee_id', Auth::user()->employee->id)
                                                ->orderBy('day_of_week')
                                                ->orderBy('start_time')
                                                ->get();

        return view('employees.appointment.create', compact('availabilities'));
    }

    public function storeAvailability(Request $request)
    {
        $request->validate([
            'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'slot_duration' => 'required|integer|min:15|max:120'
        ]);

        $professional = Employee::where('email', Auth::user()->email)->first();

        $availability = EmployeeAvailability::create([
            'employee_id' => $professional->id,
            'day_of_week' => $request->day_of_week,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'slot_duration' => $request->slot_duration,
            'is_active' => true
        ]);

        // Générer les slots pour les 3 prochains mois
        $this->generateSlotsForAvailability($availability);

        return response()->json(['message' => 'Disponibilité ajoutée avec succès']);
    }

    public function updateAvailability(Request $request, ProfessionalAvailability $availability)
    {
        $professional = Employee::where('email', Auth::user()->email)->first();

        if ($availability->employee_id !== $professional->id) {
            abort(403);
        }

        $request->validate([
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'slot_duration' => 'required|integer|min:15|max:120',
            'is_active' => 'boolean'
        ]);

        $availability->update($request->all());

        // Régénérer les slots si nécessaire
        if ($request->has('start_time') || $request->has('end_time') || $request->has('slot_duration')) {
            $this->regenerateSlotsForAvailability($availability);
        }

        return response()->json(['message' => 'Disponibilité mise à jour avec succès']);
    }

    public function deleteAvailability(ProfessionalAvailability $availability)
    {
        $professional = Employee::where('email', Auth::user()->email)->first();

        if ($availability->employee_id !== $professional->id) {
            abort(403);
        }

        $availability->delete();

        return response()->json(['message' => 'Disponibilité supprimée avec succès']);
    }

    public function leaves()
    {
        $professional = Employee::where('email', Auth::user()->email)->first();

        $leaves = ProfessionalLeave::where('employee_id', $professional->id)
            ->orderBy('start_date', 'desc')
            ->get();

        return view('professional.leaves', compact('professional', 'leaves'));
    }

    public function storeLeave(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:500',
            'type' => 'required|in:vacation,sick,conference,other'
        ]);

        $professional = Employee::where('email', Auth::user()->email)->first();

        $leave = ProfessionalLeave::create([
            'employee_id' => $professional->id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'reason' => $request->reason,
            'type' => $request->type
        ]);

        // Marquer les slots comme indisponibles pendant la période de congé
        $this->markSlotsUnavailableForLeave($leave);

        return response()->json(['message' => 'Congé ajouté avec succès']);
    }

    public function appointments()
    {
        $professional = Employee::where('email', Auth::user()->email)->first();

        $appointments = Appointment::where('employee_id', $professional->id)
            ->with('patient')
            ->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc')
            ->paginate(20);

        return view('professional.appointments', compact('professional', 'appointments'));
    }

    public function confirmAppointment(Appointment $appointment)
    {
        $professional = Employee::where('email', Auth::user()->email)->first();

        if ($appointment->employee_id !== $professional->id) {
            abort(403);
        }

        $appointment->update([
            'status' => 'confirmed',
            'confirmed_at' => now()
        ]);

        return response()->json(['message' => 'Rendez-vous confirmé avec succès']);
    }

    public function completeAppointment(Appointment $appointment, Request $request)
    {
        $professional = Employee::where('email', Auth::user()->email)->first();

        if ($appointment->employee_id !== $professional->id) {
            abort(403);
        }

        $request->validate([
            'notes' => 'nullable|string|max:2000'
        ]);

        $appointment->update([
            'status' => 'completed',
            'notes' => $request->notes
        ]);

        return response()->json(['message' => 'Rendez-vous marqué comme terminé']);
    }

    private function generateSlotsForAvailability(ProfessionalAvailability $availability)
    {
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addMonths(3);

        $dayOfWeekMap = [
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
            'sunday' => 0
        ];

        $targetDayOfWeek = $dayOfWeekMap[$availability->day_of_week];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            if ($date->dayOfWeek === $targetDayOfWeek) {
                $this->generateSlotsForDate($availability, $date);
            }
        }
    }

    private function generateSlotsForDate(ProfessionalAvailability $availability, Carbon $date)
    {
        $startTime = Carbon::parse($availability->start_time);
        $endTime = Carbon::parse($availability->end_time);
        $slotDuration = $availability->slot_duration;

        $currentTime = $startTime->copy();

        while ($currentTime->lt($endTime)) {
            AppointmentSlot::updateOrCreate([
                'employee_id' => $availability->employee_id,
                'date' => $date->toDateString(),
                'time' => $currentTime->toTimeString()
            ], [
                'is_available' => true
            ]);

            $currentTime->addMinutes($slotDuration);
        }
    }

    private function regenerateSlotsForAvailability(ProfessionalAvailability $availability)
    {
        // Supprimer les anciens slots futurs pour ce jour
        AppointmentSlot::where('employee_id', $availability->employee_id)
            ->where('date', '>=', Carbon::today())
            ->whereRaw('DAYOFWEEK(date) = ?', [
                $this->getDayOfWeekNumber($availability->day_of_week)
            ])
            ->delete();

        // Régénérer les slots
        $this->generateSlotsForAvailability($availability);
    }

    private function markSlotsUnavailableForLeave(ProfessionalLeave $leave)
    {
        AppointmentSlot::where('employee_id', $leave->employee_id)
            ->whereBetween('date', [$leave->start_date, $leave->end_date])
            ->update([
                'is_available' => false,
                'reason_unavailable' => 'Congé: ' . $leave->reason
            ]);
    }

    private function getDayOfWeekNumber($dayName)
    {
        $days = [
            'sunday' => 1,
            'monday' => 2,
            'tuesday' => 3,
            'wednesday' => 4,
            'thursday' => 5,
            'friday' => 6,
            'saturday' => 7
        ];

        return $days[$dayName] ?? 1;
    }
}
