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

    public function create()
    {
        $departments = Department::select('id', 'name')->orderBy('name')->get();

        return view('employees.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $donnees = $this->valider($request);
        $donnees['first_name'] = $this->sansTitre($donnees['first_name']);
        $donnees['is_active'] = true;

        Employee::create($donnees);

        return redirect()->route('employee.index')->with('success', 'Employé ajouté.');
    }

    public function show($id)
    {
        $employee = Employee::with('department')->findOrFail($id);

        return view('employees.profile', ['employee' => $employee, 'estMonProfil' => auth()->user()?->employee?->id === $employee->id]);
    }

    public function edit($id)
    {
        $employee = Employee::findOrFail($id);
        $departments = Department::orderBy('name')->get();

        return view('employees.edit', compact('employee', 'departments'));
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

        $request->user()->forceFill(['password' => \Illuminate\Support\Facades\Hash::make($donnees['password'])])->save();

        return back()->with('success', 'Mot de passe modifié.');
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
