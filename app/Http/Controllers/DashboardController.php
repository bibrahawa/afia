<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
use App\Models\EmployeeBreak;

class DashboardController extends Controller
{
    public function index()
    {
        return view('appointments.rdv.rdv');
    }

    /**
     * Affiche le dashboard principal
     */
    public function admin()
    {
        $rdv_today = Appointment::whereDate('appointment_date', today())->count();
        $hospitalisations_active = Hospitalisation::where('statut', 'active')->count();
        $chambres_libres = Chambre::where('statut', 'libre')->count();
        $patients_assures = Patient::with('hasActiveInsurance')->count();
        $factures_impayees = Invoice::whereIn('insurance_status', ['pending', 'approved'])->count();
        $montant_impaye = Invoice::whereIn('insurance_status', ['pending', 'approved'])->sum('insurance_amount');
        $medicaments_stock_faible = Medicament::count();
        $reclamations_en_attente = InsuranceClaim::where('status', 'draft')->count();
        $rdv_aujourdhui = Appointment::with(['patient', 'employee'])->whereDate('appointment_date', today())->get();
        $total_patient = Patient::count();
        $patientes = Patient::latest()->limit(5)->get();
        $consultations = Consultation::latest()->limit(10)->get();
        $transactions = Transaction::latest()->limit(10)->get();

        return view('dashboard', compact(
            'total_patient', 
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
            'reclamations_en_attente'
        ));
    }

    /**
     * Dashboard professionnel
     */
    public function indexProfessionel()
    {
        $professional = auth()->user();
        return view('professional.dashboard', compact('professional'));
    }

    /**
     * Récupère les rendez-vous
     */
    public function appointments(Request $request)
    {
        $query = Appointment::with(['patient.user']);
        
        // Recherche
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('patient', function($patientQuery) use ($search) {
                    $patientQuery->where('first_name', 'LIKE', "%{$search}%")
                                ->orWhere('last_name', 'LIKE', "%{$search}%")
                                ->orWhereHas('user', function($userQuery) use ($search) {
                                    $userQuery->where('phone', 'LIKE', "%{$search}%");
                                });
                })
                ->orWhere('notes', 'LIKE', "%{$search}%")
                ->orWhere('appointment_date', 'LIKE', "%{$search}%");
            });
        }
        
        // Filtre par statut
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }
        
        // Filtre par date
        if ($request->has('date_filter') && $request->date_filter != '') {
            $dateFilter = $request->date_filter;
            
            switch ($dateFilter) {
                case 'today':
                    $query->whereDate('appointment_date', Carbon::today());
                    break;
                case 'tomorrow':
                    $query->whereDate('appointment_date', Carbon::tomorrow());
                    break;
                case 'day_after_tomorrow':
                    $query->whereDate('appointment_date', Carbon::today()->addDays(2));
                    break;
                case 'this_week':
                    $query->whereBetween('appointment_date', [
                        Carbon::now()->startOfWeek(),
                        Carbon::now()->endOfWeek()
                    ]);
                    break;
                case 'next_week':
                    $query->whereBetween('appointment_date', [
                        Carbon::now()->addWeek()->startOfWeek(),
                        Carbon::now()->addWeek()->endOfWeek()
                    ]);
                    break;
                case 'this_month':
                    $query->whereMonth('appointment_date', Carbon::now()->month)
                        ->whereYear('appointment_date', Carbon::now()->year);
                    break;
            }
        }
        
        $appointments = $query->orderBy('appointment_date', 'asc')
                            ->orderBy('appointment_time', 'asc')
                            ->paginate(30);
        
        // IMPORTANT: Ajouter les paramètres à la pagination
        $appointments->appends([
            'search' => $request->search,
            'status' => $request->status,
            'date_filter' => $request->date_filter
        ]);
        
        // Calculer les statistiques pour les filtres
        $stats = [
            'today' => Appointment::whereDate('appointment_date', Carbon::today())->count(),
            'tomorrow' => Appointment::whereDate('appointment_date', Carbon::tomorrow())->count(),
            'day_after_tomorrow' => Appointment::whereDate('appointment_date', Carbon::today()->addDays(2))->count(),
            'this_week' => Appointment::whereBetween('appointment_date', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])->count(),
            'next_week' => Appointment::whereBetween('appointment_date', [
                Carbon::now()->addWeek()->startOfWeek(),
                Carbon::now()->addWeek()->endOfWeek()
            ])->count(),
            'this_month' => Appointment::whereMonth('appointment_date', Carbon::now()->month)
                                    ->whereYear('appointment_date', Carbon::now()->year)
                                    ->count(),
            'total' => Appointment::count(),
        ];
        
        // Si c'est une requête AJAX, retourner seulement la liste
        if ($request->ajax()) {
            return view('appointments.partials.list', compact('appointments'))->render();
        }
        
        return view('appointments.appointment', compact('appointments', 'stats'));
    }

    /**
     * Confirme un rendez-vous
     */
    public function confirmAppointment(int $appointmentId): RedirectResponse
    {
        try {

            $appointment = Appointment::findOrFail($appointmentId);

            // if ($appointment->employee_id !== auth()->id()) {
            //     return redirect()->back()->with('error', 'Vous n\'êtes pas autorisé à modifier ce rendez-vous');
            // }

            $appointment->update(['status' => 'confirmed']);

            // TODO: Notifier le patient par SMS
            
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

            // if ($appointment->employee_id !== auth()->id()) {
            //     return redirect()->back()->with('error', 'Vous n\'êtes pas autorisé à modifier ce rendez-vous');
            // }

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
    public function availabilities()
    {
        $availabilities = EmployeeAvailability::where('employee_id', auth()->user()->employee->id)
            ->orderBy('day_of_week')
            ->get();

        return view('appointments.disponibilite', compact('availabilities'));
    }

    /**
     * Crée une nouvelle disponibilité
     * FIX: Génère aussi les créneaux pour aujourd'hui si la disponibilité correspond
     */
    public function storeAvailability(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'day_of_week' => 'required|string|in:Lundi,Mardi,Mercredi,Jeudi,Vendredi,Samedi,Dimanche',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'slot_duration' => 'required|integer|min:10|max:180'
        ]);

        // Vérification des conflits de disponibilité
        $existingAvailability = EmployeeAvailability::where('employee_id', auth()->user()->employee->id)
            ->where('day_of_week', $validated['day_of_week'])
            ->first();

        if ($existingAvailability) {
            return redirect()->back()->with('error', 'Une disponibilité existe déjà pour ce jour');
        }

        DB::beginTransaction();

        try {
            // Créer la disponibilité
            $availability = EmployeeAvailability::create([
                'employee_id' => auth()->user()->employee->id,
                ...$validated,
                'is_active' => true
            ]);

            // FIX: Générer les créneaux en incluant aujourd'hui si applicable
            $this->generateAppointmentSlotsImproved($validated, 8);

            DB::commit();
            return redirect()->back()->with('success', 'Disponibilité et créneaux créés avec succès');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur création disponibilité: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de la création de la disponibilité');
        }
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
            'slot_duration' => 'required|integer|min:10|max:180'
        ]);

        DB::beginTransaction();
        try {
            $availability = EmployeeAvailability::findOrFail($request->id);

            if ($availability->employee_id !== auth()->id()) {
                return redirect()->back()->with('error', 'Vous n\'êtes pas autorisé à modifier cette disponibilité');
            }

            // Supprimer les anciens créneaux futurs
            $this->deleteExistingSlotsImproved($validated['day_of_week']);

            // Mettre à jour la disponibilité
            $availability->update($validated);

            // Générer les nouveaux créneaux (incluant aujourd'hui)
            $this->generateAppointmentSlotsImproved($validated, 8);

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

            if ($availability->employee_id !== auth()->id()) {
                return redirect()->back()->with('error', 'Vous n\'êtes pas autorisé à supprimer cette disponibilité');
            }

            // Supprimer les créneaux futurs associés
            $this->deleteExistingSlotsImproved($availability->day_of_week);

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
     * FIX: Génère les créneaux en incluant aujourd'hui si le jour correspond
     */
    private function generateAppointmentSlotsImproved(array $availability, int $weeks = 8): void
    {
        $dayOfWeek = $this->getDayOfWeek($availability['day_of_week']);
        $today = Carbon::now();
        $todayDayOfWeek = $today->dayOfWeek;
        
        // Convertir le jour de la semaine français en numéro (0 = dimanche, 1 = lundi, etc.)
        $targetDayNumber = Carbon::parse('next ' . $dayOfWeek)->dayOfWeek;
        
        // Si aujourd'hui correspond au jour de disponibilité, commencer aujourd'hui
        $startDate = ($todayDayOfWeek === $targetDayNumber) 
            ? $today->copy()
            : $today->next($dayOfWeek);

        for ($week = 0; $week < $weeks; $week++) {
            $date = $startDate->copy()->addWeeks($week);

            // Vérifier si c'est un jour de congé
            $isLeaveDay = EmployeeLeave::where('employee_id', auth()->user()->employee->id)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->exists();

            if ($isLeaveDay) {
                continue;
            }

            $start = Carbon::createFromFormat('H:i', $availability['start_time']);
            $end = Carbon::createFromFormat('H:i', $availability['end_time']);
            $duration = (int)$availability['slot_duration'];
            
            // Si c'est aujourd'hui, ne générer que les créneaux futurs
            if ($date->isToday()) {
                $now = Carbon::now();
                while ($start < $now) {
                    $start->addMinutes($duration);
                }
            }

            while ($start < $end) {
                AppointmentSlot::firstOrCreate([
                    'employee_id' => auth()->id(),
                    'date' => $date->format('Y-m-d'),
                    'time' => $start->format('H:i'),
                ], [
                    'is_available' => true
                ]);

                $start->addMinutes($duration);
            }
        }
    }

    /**
     * FIX: Supprime les créneaux existants pour un jour donné (incluant aujourd'hui)
     */
    private function deleteExistingSlotsImproved(string $dayOfWeek): void
    {
        $dayOfWeekEn = $this->getDayOfWeek($dayOfWeek);
        $today = Carbon::now();
        $todayDayOfWeek = $today->dayOfWeek;
        $targetDayNumber = Carbon::parse('next ' . $dayOfWeekEn)->dayOfWeek;
        
        // Si aujourd'hui correspond, commencer par aujourd'hui
        $startDate = ($todayDayOfWeek === $targetDayNumber) 
            ? $today->copy()
            : $today->next($dayOfWeekEn);

        for ($week = 0; $week < 8; $week++) {
            $date = $startDate->copy()->addWeeks($week);

            AppointmentSlot::where('employee_id', auth()->user()->employee->id)
                ->whereDate('date', $date)
                ->where('is_available', true)
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

    /**
     * Crée une demande de congé
     */
    public function storeLeave(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:Vacance,Maladie,Conference,Autre',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'reason' => 'nullable|string|max:500'
        ], [
            'type.required' => 'Le type de congé est obligatoire.',
            'type.in' => 'Le type de congé sélectionné est invalide.',
            'start_date.required' => 'La date de début est obligatoire.',
            'start_date.after_or_equal' => 'La date de début doit être aujourd\'hui ou ultérieure.',
            'end_date.required' => 'La date de fin est obligatoire.',
            'end_date.after' => 'La date de fin doit être après la date de début.',
            'reason.max' => 'La raison ne peut pas dépasser 500 caractères.'
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        // Vérifier les conflits
        if ($this->hasConflictingLeave($startDate, $endDate, auth()->id())) {
            return redirect()->back()->with('error', 'Un congé existe déjà pour cette période');
        }

        DB::beginTransaction();
        
        try {
            $leave = EmployeeLeave::create([
                'employee_id' => auth()->id(),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'type' => $validated['type'],
                'reason' => $validated['reason'],
                'status' => 'pending',
            ]);

            $deletedCount = $this->deleteSlotsForPeriod($startDate, $endDate, auth()->id());

            // Job optionnel pour notifier
            dispatch(new ProcessDoctorUnavailabilityJob(
                auth()->id(),
                $startDate,
                $endDate,
                $validated['type']
            ));

            DB::commit();
            
            Log::info('Congé créé', [
                'leave_id' => $leave->id,
                'employee_id' => auth()->id(),
                'deleted_slots' => $deletedCount
            ]);
            
            return redirect()->back()->with('success', "Congé créé. {$deletedCount} créneaux supprimés.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur création congé: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de la création');
        }
    }

    /**
     * Met à jour une demande de congé
     */
    public function updateLeave(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id' => 'required|exists:employee_leaves,id',
            'type' => 'required|in:Vacance,Maladie,Conference,Autre',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'reason' => 'nullable|string|max:500'
        ], [
            'id.exists' => 'Le congé sélectionné n\'existe pas.',
            'type.required' => 'Le type de congé est obligatoire.',
            'start_date.required' => 'La date de début est obligatoire.',
            'end_date.after' => 'La date de fin doit être après la date de début.',
        ]);

        DB::beginTransaction();
        try {
            $leave = EmployeeLeave::findOrFail($request->id);

            if ($leave->employee_id !== auth()->id()) {
                return redirect()->back()->with('error', 'Non autorisé');
            }

            if (in_array($leave->status, ['approved', 'rejected'])) {
                return redirect()->back()->with('error', 'Ce congé ne peut plus être modifié');
            }

            // Restaurer les créneaux de l'ancienne période
            $restoredCount = $this->restoreSlotsForPeriod(
                $leave->start_date,
                $leave->end_date,
                auth()->id()
            );

            // Mettre à jour le congé
            $leave->update([
                'type' => $validated['type'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'reason' => $validated['reason'],
            ]);

            // Supprimer les créneaux de la nouvelle période
            $deletedCount = $this->deleteSlotsForPeriod(
                $validated['start_date'],
                $validated['end_date'],
                auth()->id()
            );

            DB::commit();
            
            Log::info('Congé mis à jour', [
                'leave_id' => $leave->id,
                'restored_slots' => $restoredCount,
                'deleted_slots' => $deletedCount
            ]);
            
            return redirect()->back()->with('success', "Congé mis à jour. {$restoredCount} créneaux restaurés, {$deletedCount} supprimés.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur mise à jour congé: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de la mise à jour');
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
            
            if ($leave->employee_id !== auth()->id()) {
                return redirect()->back()->with('error', 'Vous n\'êtes pas autorisé à supprimer ce congé');
            }

            if ($leave->status === 'approved' && $leave->start_date <= now()) {
                return redirect()->back()->with('error', 'Un congé déjà approuvé et commencé ne peut être supprimé');
            }
            
            // Restaurer les créneaux de la période du congé
            $restoredCount = $this->restoreSlotsForPeriod(
                $leave->start_date,
                $leave->end_date,
                auth()->id()
            );

            $leave->delete();

            DB::commit();
            
            Log::info("Congé supprimé", [
                'leave_id' => $leave->id,
                'employee_id' => auth()->id(),
                'restored_slots' => $restoredCount
            ]);
            
            return redirect()->back()->with('success', "Congé supprimé avec succès. {$restoredCount} créneaux restaurés.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur suppression congé: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de la suppression du congé: ' . $e->getMessage());
        }
    }

    /**
     * Restaure les créneaux pour une période donnée
     */
    private function restoreSlotsForPeriod($startDate, $endDate, $employeeId): int
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $restoredCount = 0;
        $currentDate = $start->copy()->startOfDay();

        while ($currentDate <= $end->endOfDay()) {
            $dayName = ucfirst($currentDate->locale('fr')->isoFormat('dddd'));
            
            $availability = EmployeeAvailability::where('employee_id', $employeeId)
                ->where('day_of_week', $dayName)
                ->where('is_active', true)
                ->first();

            if ($availability) {
                $startTime = Carbon::createFromFormat('H:i', $availability->start_time->format('H:i'));
                $endTime = Carbon::createFromFormat('H:i', $availability->end_time->format('H:i'));
                $duration = (int)$availability->slot_duration;
                
                // Ajustement pour le premier jour
                if ($currentDate->isSameDay($start)) {
                    $leaveStart = Carbon::parse($startDate);
                    $startTime = $startTime->max(Carbon::createFromFormat('H:i', $leaveStart->format('H:i')));
                }
                
                // Ajustement pour le dernier jour
                if ($currentDate->isSameDay($end)) {
                    $leaveEnd = Carbon::parse($endDate);
                    $endTime = $endTime->min(Carbon::createFromFormat('H:i', $leaveEnd->format('H:i')));
                }
                
                // Ne pas créer de créneaux dans le passé
                if ($currentDate->isToday()) {
                    $now = Carbon::now();
                    $startTime = $startTime->max($now);
                }
                
                $slotTime = $startTime->copy();
                while ($slotTime < $endTime) {
                    $slot = AppointmentSlot::firstOrCreate([
                        'employee_id' => $employeeId,
                        'date' => $currentDate->format('Y-m-d'),
                        'time' => $slotTime->format('H:i:s'),
                    ], [
                        'is_available' => true
                    ]);
                    
                    if ($slot->wasRecentlyCreated) $restoredCount++;
                    $slotTime->addMinutes($duration);
                }
            }
            
            $currentDate->addDay();
        }
        
        return $restoredCount;
    }

    /**
     * Supprime les créneaux pour une période donnée
     */
    private function deleteSlotsForPeriod($startDate, $endDate, $employeeId): int
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $deletedCount = 0;
        $currentDate = $start->copy()->startOfDay();

        while ($currentDate <= $end->endOfDay()) {
            $dayName = ucfirst($currentDate->locale('fr')->isoFormat('dddd'));
            
            $availability = EmployeeAvailability::where('employee_id', $employeeId)
                ->where('day_of_week', $dayName)
                ->where('is_active', true)
                ->first();

            if ($availability) {
                $startTime = Carbon::createFromFormat('H:i', $availability->start_time->format('H:i'));
                $endTime = Carbon::createFromFormat('H:i', $availability->end_time->format('H:i'));
                $duration = (int)$availability->slot_duration;
                
                // Ajustement pour le premier jour
                if ($currentDate->isSameDay($start)) {
                    $leaveStart = Carbon::parse($startDate);
                    $startTime = $startTime->max(Carbon::createFromFormat('H:i', $leaveStart->format('H:i')));
                }
                
                // Ajustement pour le dernier jour
                if ($currentDate->isSameDay($end)) {
                    $leaveEnd = Carbon::parse($endDate);
                    $endTime = $endTime->min(Carbon::createFromFormat('H:i', $leaveEnd->format('H:i')));
                }
                
                $slotTime = $startTime->copy();
                while ($slotTime < $endTime) {
                    $deleted = AppointmentSlot::where('employee_id', $employeeId)
                        ->where('date', $currentDate->format('Y-m-d'))
                        ->where('time', $slotTime->format('H:i:s'))
                        ->where('is_available', true)
                        ->delete();
                    
                    $deletedCount += $deleted;
                    $slotTime->addMinutes($duration);
                }
            }
            
            $currentDate->addDay();
        }
        
        return $deletedCount;
    }

    /**
     * Vérifie s'il existe un congé en conflit pour la période donnée
     */
    private function hasConflictingLeave($startDate, $endDate, $employeeId, $excludeLeaveId = null): bool
    {
        $query = EmployeeLeave::where('employee_id', $employeeId)
            ->where('status', '!=', 'rejected')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q2) use ($startDate, $endDate) {
                        $q2->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            });
        
        if ($excludeLeaveId) {
            $query->where('id', '!=', $excludeLeaveId);
        }
        
        return $query->exists();
    }

    /**
     * Affiche la page des congés ET pauses
     */
    public function leaves()
    {
        $leaves = EmployeeLeave::where('employee_id', auth()->user()->employee->id)
            ->orderBy('start_date', 'desc')
            ->get();
        
        // Récupérer aussi les pauses
        $breaks = EmployeeBreak::where('employee_id', auth()->user()->employee->id)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return view('appointments.leaves', compact('leaves', 'breaks'));
    }

    /**
     * Créer une pause (supprime UNIQUEMENT les créneaux existants)
     */
    public function storeBreak(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'day_of_week' => 'required|in:Lundi,Mardi,Mercredi,Jeudi,Vendredi,Samedi,Dimanche',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'label' => 'nullable|string|max:255',
        ], [
            'day_of_week.required' => 'Le jour de la semaine est requis.',
            'start_time.required' => 'L\'heure de début est requise.',
            'end_time.required' => 'L\'heure de fin est requise.',
            'end_time.after' => 'L\'heure de fin doit être après l\'heure de début.',
        ]);


        // Vérifier les chevauchements
        $existingBreak = EmployeeBreak::where('employee_id', auth()->user()->employee->id)
            ->where('day_of_week', $validated['day_of_week'])
            ->where(function($query) use ($validated) {
                $query->where(function($q) use ($validated) {
                    $q->where('start_time', '<=', $validated['start_time'])
                    ->where('end_time', '>', $validated['start_time']);
                })
                ->orWhere(function($q) use ($validated) {
                    $q->where('start_time', '<', $validated['end_time'])
                    ->where('end_time', '>=', $validated['end_time']);
                })
                ->orWhere(function($q) use ($validated) {
                    $q->where('start_time', '>=', $validated['start_time'])
                    ->where('end_time', '<=', $validated['end_time']);
                });
            })
            ->exists();

        if ($existingBreak) {
            return redirect()->back()->with('error', 'Une pause existe déjà dans cette plage horaire pour ce jour.');
        }

        DB::beginTransaction();
        
        try {
            // Créer la pause
            $break = EmployeeBreak::create([
                'employee_id' => auth()->id(),
                'day_of_week' => $validated['day_of_week'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'label' => $validated['label'] ?? 'Pause',
                'is_active' => true,
            ]);

            // Supprimer UNIQUEMENT les créneaux existants
            $deletedCount = $this->deleteSlotsForBreakOptimized($break);

            DB::commit();
            
            Log::info('Pause créée', [
                'break_id' => $break->id,
                'employee_id' => auth()->id(),
                'deleted_slots' => $deletedCount
            ]);
            
            return redirect()->back()->with('success', "Pause créée avec succès. {$deletedCount} créneau(x) supprimé(s).");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur création pause: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de la création de la pause');
        }
    }

    public function break($id){
        $break = EmployeeBreak::where('employee_id', auth()->user()->employee->id)->findOrFail($id);
        dd($this->restoreSlotsForBreakOptimized($break));

    }

    /**
     * Mettre à jour une pause
     */
    public function updateBreak(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id' => 'required|exists:employee_breaks,id',
            'day_of_week' => 'required|in:Lundi,Mardi,Mercredi,Jeudi,Vendredi,Samedi,Dimanche',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'label' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        
        try {
            $break = EmployeeBreak::findOrFail($request->id);

            if ($break->employee_id !== auth()->id()) {
                return redirect()->back()->with('error', 'Non autorisé');
            }

            // Vérifier les chevauchements (sauf avec cette pause)
            $existingBreak = EmployeeBreak::where('employee_id', auth()->user()->employee->id)
                ->where('id', '!=', $request->id)
                ->where('day_of_week', $validated['day_of_week'])
                ->where(function($query) use ($validated) {
                    $query->where(function($q) use ($validated) {
                        $q->where('start_time', '<=', $validated['start_time'])
                        ->where('end_time', '>', $validated['start_time']);
                    })
                    ->orWhere(function($q) use ($validated) {
                        $q->where('start_time', '<', $validated['end_time'])
                        ->where('end_time', '>=', $validated['end_time']);
                    })
                    ->orWhere(function($q) use ($validated) {
                        $q->where('start_time', '>=', $validated['start_time'])
                        ->where('end_time', '<=', $validated['end_time']);
                    });
                })
                ->exists();

            if ($existingBreak) {
                return redirect()->back()->with('error', 'Une pause existe déjà dans cette plage horaire.');
            }

            // Restaurer les créneaux de l'ancienne pause
            $restoredCount = $this->restoreSlotsForBreakOptimized($break);

            // Mettre à jour la pause
            $break->update([
                'day_of_week' => $validated['day_of_week'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'label' => $validated['label'] ?? 'Pause',
            ]);

            // Supprimer les créneaux de la nouvelle pause
            $deletedCount = $this->deleteSlotsForBreakOptimized($break);

            DB::commit();
            
            Log::info('Pause mise à jour', [
                'break_id' => $break->id,
                'restored_slots' => $restoredCount,
                'deleted_slots' => $deletedCount
            ]);
            
            return redirect()->back()->with('success', "Pause mise à jour. {$restoredCount} créneau(x) restauré(s), {$deletedCount} supprimé(s).");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur mise à jour pause: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de la mise à jour');
        }
    }

    /**
     * Supprimer une pause
     */
    public function destroyBreak(Request $request): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $break = EmployeeBreak::findOrFail($request->input('id'));
            
            if ($break->employee_id !== auth()->id()) {
                return redirect()->back()->with('error', 'Non autorisé');
            }
            
            // Restaurer les créneaux
            $restoredCount = $this->restoreSlotsForBreakOptimized($break);

            $break->delete();

            DB::commit();
            
            Log::info("Pause supprimée", [
                'break_id' => $break->id,
                'employee_id' => auth()->id(),
                'restored_slots' => $restoredCount
            ]);
            
            return redirect()->back()->with('success', "Pause supprimée. {$restoredCount} créneau(x) restauré(s).");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur suppression pause: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de la suppression');
        }
    }

    /**
     * Toggle actif/inactif (AJAX)
     */
    public function toggleBreak(Request $request, int $id)
    {
        try {

            $break = EmployeeBreak::where('employee_id', auth()->user()->employee->id)->findOrFail($id);
            
            DB::beginTransaction();
            
            // Convertir explicitement en boolean
            $isActive = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
            
            if ($isActive) {
                // Activer = supprimer les créneaux
                $deletedCount = $this->deleteSlotsForBreakOptimized($break);
                $break->update(['is_active' => true]);
                $message = "Pause activée. {$deletedCount} créneau(x) supprimé(s).";
            } else {
                // Désactiver = restaurer les créneaux
                $restoredCount = $this->restoreSlotsForBreakOptimized($break);
                $break->update(['is_active' => false]);
                $message = "Pause désactivée. {$restoredCount} créneau(x) restauré(s).";
            }

            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => $message
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur toggle pause: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de la mise à jour de la pause');
        }
    }

    /**
     * VERSION OPTIMISÉE : Supprimer UNIQUEMENT les créneaux existants
     * Ne touche QUE les créneaux déjà créés dans la plage de dates configurée
     */
    private function deleteSlotsForBreakOptimized(EmployeeBreak $break): int
    {
        $dayOfWeekEn = $this->getDayOfWeek($break->day_of_week);
        
        // Récupérer la date min et max des créneaux existants pour cet employé
        $dateRange = AppointmentSlot::where('employee_id', $break->employee_id)
            ->selectRaw('MIN(date) as min_date, MAX(date) as max_date')
            ->first();
        
        if (!$dateRange || !$dateRange->min_date) {
            Log::info('Aucun créneau existant trouvé pour cet employé');
            return 0;
        }
        
        $startDate = Carbon::parse($dateRange->min_date);
        $endDate = Carbon::parse($dateRange->max_date);
        
        Log::info('Plage de créneaux existants', [
            'start' => $startDate->format('Y-m-d'),
            'end' => $endDate->format('Y-m-d')
        ]);
        
        // Supprimer UNIQUEMENT les créneaux qui existent dans cette plage
        $deletedCount = AppointmentSlot::where('employee_id', $break->employee_id)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereRaw('DAYNAME(date) = ?', [$dayOfWeekEn])
            ->where('time', '>=', $break->start_time->format('H:i:s'))
            ->where('time', '<', $break->end_time->format('H:i:s'))
            ->where('is_available', true)
            ->delete();
        
        Log::info("Créneaux supprimés pour la pause", [
            'break_id' => $break->id,
            'day' => $break->day_of_week,
            'deleted_count' => $deletedCount
        ]);
        
        return $deletedCount;
    }

    /**
     * VERSION OPTIMISÉE : Restaurer UNIQUEMENT là où il y a une disponibilité
     * Ne recrée QUE les créneaux dans la plage de dates où des créneaux existent déjà
     */
    private function restoreSlotsForBreakOptimized($break): int
    {
        // Récupérer la disponibilité pour ce jour        
        $availability = EmployeeAvailability::where('employee_id', $break->employee_id)
            ->where('day_of_week', $break->day_of_week)
            ->where('is_active', true)
            ->first();

        if (!$availability) {
            Log::info('Aucune disponibilité configurée pour ce jour');
            return 0;
        }
        
        // Récupérer la plage de dates des créneaux existants
        $dateRange = AppointmentSlot::where('employee_id', $break->employee_id)
            ->selectRaw('MIN(date) as min_date, MAX(date) as max_date')
            ->first();
        
        if (!$dateRange || !$dateRange->min_date) {
            Log::info('Aucun créneau existant trouvé');
            return 0;
        }
        
        $startDate = Carbon::parse($dateRange->min_date);
        $endDate = Carbon::parse($dateRange->max_date);
        $dayOfWeekEn = $this->getDayOfWeek($break->day_of_week);
        $restoredCount = 0;
        
        // Ne parcourir QUE les jours qui correspondent dans cette plage
        $currentDate = $startDate->copy();
        
        // Aller au premier jour correspondant
        while ($currentDate <= $endDate) {
            if ($currentDate->format('l') === $dayOfWeekEn) {
                break;
            }
            $currentDate->addDay();
        }
        
        // Parcourir tous les jours correspondants dans la plage
        while ($currentDate <= $endDate) {
            // Vérifier si c'est un jour de congé
            $isLeaveDay = EmployeeLeave::where('employee_id', $break->employee_id)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $currentDate)
                ->whereDate('end_date', '>=', $currentDate)
                ->exists();
            
            if (!$isLeaveDay) {
                $startTime = Carbon::createFromFormat('H:i', $break->start_time->format('H:i'));
                $endTime = Carbon::createFromFormat('H:i', $break->end_time->format('H:i'));
                $duration = (int)$availability->slot_duration;
                
                // Si c'est aujourd'hui, ne restaurer que les créneaux futurs
                if ($currentDate->isToday()) {
                    $now = Carbon::now();
                    while ($startTime < $now) {
                        $startTime->addMinutes($duration);
                    }
                }
                
                // Restaurer les créneaux
                $slotTime = $startTime->copy();
                while ($slotTime < $endTime) {
                    $slot = AppointmentSlot::firstOrCreate([
                        'employee_id' => $break->employee_id,
                        'date' => $currentDate->format('Y-m-d'),
                        'time' => $slotTime->format('H:i:s'),
                    ], [
                        'is_available' => true
                    ]);
                    
                    if ($slot->wasRecentlyCreated) {
                        $restoredCount++;
                    }
                    
                    $slotTime->addMinutes($duration);
                }
            }
            
            // Passer au même jour la semaine prochaine
            $currentDate->addWeek();
        }
        
        Log::info("Créneaux restaurés pour la pause", [
            'break_id' => $break->id,
            'day' => $break->day_of_week,
            'restored_count' => $restoredCount
        ]);
        
        return $restoredCount;
    }

}