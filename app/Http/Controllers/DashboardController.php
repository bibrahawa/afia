<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Patient;
use App\Models\Consultation;
use App\Models\Transaction;
use App\Models\EmployeeAvailability;
use App\Models\EmployeeLeave;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Chambre;
use App\Models\Hospitalisation;
use App\Models\Invoice;
use App\Models\InsuranceClaim;
use App\Models\InvoiceItem;
use App\Models\Medicament;
use App\Jobs\ProcessDoctorUnavailabilityJob;

use Carbon\Carbon;

class DashboardController extends Controller
{

    public function index (): View
    {
        return view('appointments.rdv.rdv');
    }
    /**
     * Affiche le dashboard principal
     */
    public function admin(): View
    {
        // Dans votre DashboardController
        $rdv_today = Appointment::whereDate('created_at', today())->count();
        $hospitalisations_active = Hospitalisation::where('statut', 'active')->count();
        $chambres_libres = Chambre::where('statut', 'libre')->count();
        $patients_assures = Patient::with('hasActiveInsurance')->count();
        $factures_impayees = Invoice::where('insurance_status', 'pending')->count();
        $montant_impaye = Invoice::where('insurance_status', 'pending')->sum('insurance_amount');
        $medicaments_stock_faible = Medicament::count();
        $reclamations_en_attente = InsuranceClaim::where('status', 'draft')->count();
        $rdv_aujourdhui = Appointment::with(['patient', 'employee'])->whereDate('created_at', today())->get();

        $total_patient = Patient::count();
        $patientes = Patient::latest()->limit(5)->get();
        $consultations = Consultation::latest()->limit(10)->get();
        $transactions = Transaction::latest()->limit(10)->get();

        return view('dashboard', compact('total_patient', 
                                        'consultations', 
                                        'transactions', 
                                        'patientes',
                                        'rdv_aujourdhui',
                                        'rdv_today',
                                        'hospitalisations_active',
                                        'chambres_libres',
                                        'patients_assures',
                                        'factures_impayees',
                                        'montant_impaye',
                                        'medicaments_stock_faible',
                                        'reclamations_en_attente'));
                                        
    }

    /**
     * Dashboard professionnel
     */
    public function indexProfessionel(): View
    {
        $professional = auth()->user();
        return view('professional.dashboard', compact('professional'));
    }

    /**
     * Récupère les rendez-vous
     */
    public function appointments(Request $request): View
    {
        // Suppression des données de démonstration - utilisation des vraies données
        $appointments = Appointment::where('employee_id', auth()->id())
            ->where('status', '!=', 'Completed')
            ->with('patient')
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get();


        return view('appointments.appointment', compact('appointments'));
    }

    /**
     * Confirme un rendez-vous
     */
    public function confirmAppointment(int $appointmentId): RedirectResponse
    {
        try {
            $appointment = Appointment::findOrFail($appointmentId);

            // Vérification que l'employé peut modifier ce rendez-vous
            if ($appointment->employee_id !== auth()->id()) {
                return redirect()->back()->with('error', 'Vous n\'êtes pas autorisé à modifier ce rendez-vous');
            }

            $appointment->update(['status' => 'confirmed']);
            // Notifier le patient par une api sms
            /**
             * Envoie une notification SMS au patient
             */


            return redirect()->back()->with('success', 'Rendez-vous confirmé avec succès');

        } catch (\Exception $e) {
            Log::error('Erreur confirmation rendez-vous: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de la confirmation du rendez-vous');
        }
    }

    /**
     * Marque un rendez-vous comme terminé
     */
    public function completeAppointment(Request $request, int $appointmentId): RedirectResponse
    {
        try {
            $appointment = Appointment::findOrFail($appointmentId);

            // Vérification des permissions
            if ($appointment->employee_id !== auth()->id()) {
                return redirect()->back()->with('error', 'Vous n\'êtes pas autorisé à modifier ce rendez-vous');
            }

            $appointment->update(['status' => 'completed']);
            return redirect()->back()->with('success', 'Rendez-vous marqué comme terminé avec succès');

        } catch (\Exception $e) {
            Log::error('Erreur completion rendez-vous: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de la mise à jour du rendez-vous');
        }
    }

    /**
     * Récupère les disponibilités
     */
    public function availabilities(): View
    {
        $availabilities = EmployeeAvailability::where('employee_id', auth()->id())
            ->orderBy('day_of_week')
            ->get();

        return view('appointments.disponibilite', compact('availabilities'));
    }

    /**
     * Crée une nouvelle disponibilité
     */
    public function storeAvailability(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'day_of_week' => 'required|string|in:Lundi,Mardi,Mercredi,Jeudi,Vendredi,Samedi,Dimanche',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'slot_duration' => 'required|integer|min:15|max:180'
        ]);

        // Vérification des conflits de disponibilité
        $existingAvailability = EmployeeAvailability::where('employee_id', auth()->id())
            ->where('day_of_week', $validated['day_of_week'])
            ->first();

        if ($existingAvailability) {
            return redirect()->back()->with('error', 'Une disponibilité existe déjà pour ce jour');
        }

        DB::beginTransaction();
        // try {
            // Créer la disponibilité
            $availability = EmployeeAvailability::create([
                'employee_id' => auth()->id(),
                ...$validated,
                'is_active' => true
            ]);

            // Générer les créneaux pour les 8 prochaines semaines
            $this->generateAppointmentSlots($validated, 8);

            DB::commit();
            return redirect()->back()->with('success', 'Disponibilité et créneaux créés avec succès');

        // } catch (\Exception $e) {
        //     DB::rollBack();
        //     Log::error('Erreur création disponibilité: ' . $e->getMessage());
        //     return redirect()->back()->with('error', 'Erreur lors de la création de la disponibilité');
        // }
    }

    /**
     * Met à jour une disponibilité
     */
    public function updateAvailability(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:employee_availabilities,id',
            'day_of_week' => 'required|string|in:Lundi,Mardi,Mercredi,Jeudi,Vendredi,Samedi,Dimanche',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'slot_duration' => 'required|integer|min:15|max:180'
        ]);

        DB::beginTransaction();
        try {
            $availability = EmployeeAvailability::findOrFail($request->id);

            // Vérifier que l'employé peut modifier cette disponibilité
            if ($availability->employee_id !== auth()->id()) {
                return redirect()->back()->with('error', 'Vous n\'êtes pas autorisé à modifier cette disponibilité');
            }

            // Supprimer les anciens créneaux futurs
            $this->deleteExistingSlots($validated['day_of_week']);

            // Mettre à jour la disponibilité
            $availability->update($validated);

            // Générer les nouveaux créneaux
            $this->generateAppointmentSlots($validated, 8);

            DB::commit();
            return redirect()->back()->with('success', 'Disponibilité mise à jour avec succès');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur mise à jour disponibilité: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de la mise à jour de la disponibilité');
        }
    }

    /**
     * Supprime une disponibilité
     */
    public function destroyAvailability(int $availabilityId): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $availability = EmployeeAvailability::findOrFail($availabilityId);

            // Vérifier les permissions
            if ($availability->employee_id !== auth()->id()) {
                return redirect()->back()->with('error', 'Vous n\'êtes pas autorisé à supprimer cette disponibilité');
            }

            // Supprimer les créneaux futurs associés
            $this->deleteExistingSlots($availability->day_of_week);

            // Supprimer la disponibilité
            $availability->delete();

            DB::commit();
            return redirect()->back()->with('success', 'Disponibilité supprimée avec succès');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur suppression disponibilité: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de la suppression de la disponibilité');
        }
    }

    /**
     * Récupère les congés
     */
    public function leaves(): View
    {
        $leaves = EmployeeLeave::where('employee_id', auth()->id())
            ->orderBy('start_date', 'desc')
            ->get();

        return view('appointments.leaves', compact('leaves'));
    }

    /**
     * Crée une demande de congé
     */
    public function storeLeave(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:Vacance,Maladie,Conference,Autre',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:500'
        ]);

        // Vérifier les conflits avec d'autres congés
        $conflictingLeave = EmployeeLeave::where('employee_id', auth()->id())
            ->where('status', '!=', 'rejected')
            ->where(function ($query) use ($validated) {
                $query->whereBetween('start_date', [$validated['start_date'], $validated['end_date']])
                      ->orWhereBetween('end_date', [$validated['start_date'], $validated['end_date']])
                      ->orWhere(function ($q) use ($validated) {
                          $q->where('start_date', '<=', $validated['start_date'])
                            ->where('end_date', '>=', $validated['end_date']);
                      });
            })->first();

        if ($conflictingLeave) {
            return redirect()->back()->with('error', 'Un congé existe déjà pour cette période');
        }

        DB::beginTransaction();
        try {
            // Créer la demande de congé
            $leave = EmployeeLeave::create([
                'employee_id' => auth()->id(),
                ...$validated,
                'status' => 'pending'
            ]);

            // Supprimer les créneaux pendant la période de congé
            AppointmentSlot::where('employee_id', auth()->id())
                ->whereBetween('date', [$validated['start_date'], $validated['end_date']])
                ->where('is_available', true) // Ne supprimer que les créneaux disponibles
                ->delete();

            // Marquer le médecin indisponible
            $job = new ProcessDoctorUnavailabilityJob(
                        auth()->id(),
                        Carbon::parse($validated['start_date']),
                        Carbon::parse($validated['end_date']),
                        $validated['type']
                    );

            $job->handle();

            DB::commit();
            return redirect()->back()->with('success', 'Demande de congé soumise avec succès');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur création congé: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de la soumission de la demande');
        }
    }

    /**
     * Met à jour une demande de congé
     */
    public function updateLeave(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:employee_leaves,id',
            'type' => 'required|string|in:Vacance,Maladie,Conference,Autre',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:500'
        ]);

        DB::beginTransaction();
        try {
            $leave = EmployeeLeave::findOrFail($request->id);

            // Vérifier les permissions
            if ($leave->employee_id !== auth()->id()) {
                return redirect()->back()->with('error', 'Vous n\'êtes pas autorisé à modifier ce congé');
            }

            // Vérifier que le congé peut encore être modifié
            if (in_array($leave->status, ['approved', 'rejected'])) {
                return redirect()->back()->with('error', 'Ce congé ne peut plus être modifié');
            }

            // Restaurer les créneaux de rendez-vous pour la période
            $availability = EmployeeAvailability::where('employee_id', auth()->id())
                                                ->where('day_of_week',
                                                    ucfirst(Carbon::parse($leave->start_date)->locale('fr-FR')->isoFormat('dddd')))
                                                ->first();

            if ($availability) {
                $start = Carbon::createFromFormat('H:i', $availability['start_time']->format('H:i'));
                $end = Carbon::createFromFormat('H:i', $availability['end_time']->format('H:i'));
                $duration = (int)$availability['slot_duration'];

                while ($start <= $end) {
                    AppointmentSlot::create([
                        'employee_id' => auth()->id(),
                        'date' => $leave->start_date->format('Y-m-d'),
                        'time' => $start->format('H:i'),
                        'is_available' => true
                    ]);

                    $start->addMinutes($duration);
                }
            }

            $leave->update($validated);


            // Supprimer les créneaux pendant la nouvelle période
            AppointmentSlot::where('employee_id', auth()->id())
                ->whereBetween('date', [$validated['start_date'], $validated['end_date']])
                ->where('is_available', true)
                ->delete();


            DB::commit();
            return redirect()->back()->with('success', 'Demande de congé mise à jour avec succès');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur mise à jour congé: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de la mise à jour de la demande');
        }
    }

    /**
     * Supprime une demande de congé
     */
    public function destroyLeaves(Request $request): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $leave = EmployeeLeave::findOrFail($request->input('id'));

            // Vérifier les permissions
            if ($leave->employee_id !== auth()->id()) {
                return redirect()->back()->with('error', 'Vous n\'êtes pas autorisé à supprimer ce congé');
            }

            // Vérifier que le congé peut être supprimé
            if ($leave->status === 'approved' && $leave->start_date <= now()) {
                return redirect()->back()->with('error', 'Un congé déjà approuvé et commencé ne peut être supprimé');
            }

            // Restaurer les créneaux de rendez-vous pour la période
            $availability = EmployeeAvailability::where('employee_id', auth()->id())
                                                ->where('day_of_week',
                                                    ucfirst(Carbon::parse($leave->start_date)->locale('fr-FR')->isoFormat('dddd')))
                                                ->first();

            if ($availability) {
                $start = Carbon::createFromFormat('H:i', $availability['start_time']->format('H:i'));
                $end = Carbon::createFromFormat('H:i', $availability['end_time']->format('H:i'));
                $duration = (int)$availability['slot_duration'];

                while ($start <= $end) {
                    AppointmentSlot::create([
                        'employee_id' => auth()->id(),
                        'date' => $leave->start_date->format('Y-m-d'),
                        'time' => $start->format('H:i'),
                        'is_available' => true
                    ]);

                    $start->addMinutes($duration);
                }
            }

            $leave->delete();

            DB::commit();
            return redirect()->back()->with('success', 'Congé supprimé avec succès');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur suppression congé: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de la suppression du congé');
        }
    }

    /**
     * Génère les créneaux de rendez-vous pour une disponibilité
     */
    private function generateAppointmentSlots(array $availability, int $weeks = 8): void
    {
        $dayOfWeek = $this->getDayOfWeek($availability['day_of_week']);
        $nextOccurrence = Carbon::now()->next($dayOfWeek);

        for ($week = 0; $week < $weeks; $week++) {
            $date = $nextOccurrence->copy()->addWeeks($week);

            // Vérifier si c'est un jour de congé
            $isLeaveDay = EmployeeLeave::where('employee_id', auth()->id())
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->exists();

            if ($isLeaveDay) {
                continue;
            }

            $start = Carbon::createFromFormat('H:i', $availability['start_time']);
            $end = Carbon::createFromFormat('H:i', $availability['end_time']);
            $duration = (int)$availability['slot_duration'];

            while ($start <= $end) {
                AppointmentSlot::create([
                    'employee_id' => auth()->id(),
                    'date' => $date->format('Y-m-d'),
                    'time' => $start->format('H:i'),
                    'is_available' => true
                ]);

                $start->addMinutes($duration);
            }
        }
    }

    /**
     * Supprime les créneaux existants pour un jour donné
     */
    private function deleteExistingSlots(string $dayOfWeek): void
    {
        $dayOfWeekEn = $this->getDayOfWeek($dayOfWeek);
        $nextOccurrence = Carbon::now()->next($dayOfWeekEn);

        for ($week = 0; $week < 8; $week++) {
            $date = $nextOccurrence->copy()->addWeeks($week);

            AppointmentSlot::where('employee_id', auth()->id())
                ->whereDate('date', $date)
                ->where('is_available', true) // Ne supprimer que les créneaux disponibles
                ->delete();
        }
    }

    /**
     * Convertit les jours français en anglais
     */
    private function getDayOfWeek(string $day): string
    {
        $days = [
            'Lundi' => 'Monday',
            'Mardi' => 'Tuesday',
            'Mercredi' => 'Wednesday',
            'Jeudi' => 'Thursday',
            'Vendredi' => 'Friday',
            'Samedi' => 'Saturday',
            'Dimanche' => 'Sunday',
        ];

        return $days[$day] ?? 'Monday';
    }
}
