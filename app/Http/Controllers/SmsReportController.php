<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentSmsLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SmsReportController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', '7'); // Par défaut 7 jours
        $startDate = now()->subDays((int)$period);

        // Statistiques générales
        $stats = [
            'total_sent' => AppointmentSmsLog::where('status', 'sent')
                ->where('created_at', '>=', $startDate)
                ->count(),
            
            'total_failed' => AppointmentSmsLog::where('status', 'failed')
                ->where('created_at', '>=', $startDate)
                ->count(),
            
            'success_rate' => 0,
            
            'upcoming_appointments' => Appointment::upcoming()->count(),
            
            'appointments_needing_reminder' => Appointment::needingReminder()->count()
        ];

        $total = $stats['total_sent'] + $stats['total_failed'];
        $stats['success_rate'] = $total > 0 ? round(($stats['total_sent'] / $total) * 100, 2) : 0;

        // Répartition par type de SMS
        $smsTypeDistribution = AppointmentSmsLog::select('sms_type', 
                DB::raw('count(*) as count'),
                DB::raw('sum(case when status = "sent" then 1 else 0 end) as sent'),
                DB::raw('sum(case when status = "failed" then 1 else 0 end) as failed')
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy('sms_type')
            ->get();

        // Évolution quotidienne
        $dailyStats = AppointmentSmsLog::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as total'),
                DB::raw('sum(case when status = "sent" then 1 else 0 end) as sent'),
                DB::raw('sum(case when status = "failed" then 1 else 0 end) as failed')
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        // Derniers logs d'erreur
        $recentErrors = AppointmentSmsLog::where('status', 'failed')
            ->with(['appointment.patient', 'appointment.doctor'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.sms-report', compact(
            'stats', 
            'smsTypeDistribution', 
            'dailyStats', 
            'recentErrors',
            'period'
        ));
    }

    public function resendFailed(Request $request)
    {
        $failedLogs = AppointmentSmsLog::where('status', 'failed')
            ->with('appointment')
            ->get();

        $resent = 0;
        foreach ($failedLogs as $log) {
            if ($log->appointment && $log->appointment->status !== 'cancelled') {
                \App\Jobs\SendAppointmentReminderJob::dispatchSync(
                    $log->appointment, 
                    $log->sms_type
                );
                $resent++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$resent} SMS en échec ont été reprogrammés"
        ]);
    }
}