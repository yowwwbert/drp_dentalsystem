<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Appointment\Appointment;
use App\Models\Clinic\Branches;
use App\Models\Users\Dentist;
use App\Models\Users\Staff;
use App\Models\Users\Patient;
use App\Models\Clinic\Treatment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OwnerDashboardController extends Controller
{
    public function index()
    {
        // Get appointment statistics
        $appointmentStats = $this->getAppointmentStatistics();
        
        // Get scheduled appointments
        $scheduledAppointments = $this->getScheduledAppointments();
        
        // Get branch statistics
        $branchStats = $this->getBranchStatistics();
        
        // Get financial overview
        $financialOverview = $this->getFinancialOverview();
        
        // Get recent activities
        $recentActivities = $this->getRecentActivities();

        return response()->json([
            'appointmentStats' => $appointmentStats,
            'scheduledAppointments' => $scheduledAppointments,
            'branchStats' => $branchStats,
            'financialOverview' => $financialOverview,
            'recentActivities' => $recentActivities,
        ]);
    }

    private function getAppointmentStatistics()
    {
        $today = Carbon::today();
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();

        // Get appointments for the current week
        $weeklyAppointments = Appointment::whereBetween('created_at', [$weekStart, $weekEnd])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        // Generate week overview data
        $weekOverview = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $weekStart->copy()->addDays($i);
            $dateKey = $date->format('Y-m-d');
            $dateLabel = $date->format('j M');
            
            $appointments = $weeklyAppointments->get($dateKey);
            $total = $appointments ? $appointments->total : 0;
            
            $weekOverview[] = [
                'date' => $dateLabel,
                'completed' => $total, // Simplified for now
                'cancelled' => 0, // Would need status tracking
                'total' => $total
            ];
        }

        // Get overall statistics
        $totalAppointments = Appointment::count();
        $pendingAppointments = Appointment::where('status', 'pending')->count();
        $completedAppointments = Appointment::where('status', 'completed')->count();
        $cancelledAppointments = Appointment::where('status', 'cancelled')->count();

        return [
            'pending' => $pendingAppointments,
            'completed' => $completedAppointments,
            'cancelled' => $cancelledAppointments,
            'total' => $totalAppointments,
            'overview' => $weekOverview
        ];
    }

    private function getScheduledAppointments()
    {
        return Appointment::with(['patient.user', 'dentist.user', 'branch', 'schedule'])
            ->where('status', 'scheduled')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($appointment) {
                return [
                    'patientName' => $appointment->patient->user->first_name . ' ' . $appointment->patient->user->last_name,
                    'date' => $appointment->schedule ? Carbon::parse($appointment->schedule->schedule_date)->format('F j, Y') : 'N/A',
                    'startTime' => $appointment->schedule ? $appointment->schedule->start_time : 'N/A',
                    'branch' => $appointment->branch ? $appointment->branch->branch_name : 'N/A'
                ];
            });
    }

    private function getBranchStatistics()
    {
        $branches = Branches::withCount(['appointments', 'dentists', 'staff'])
            ->get()
            ->map(function ($branch) {
                return [
                    'id' => $branch->branch_id,
                    'name' => $branch->branch_name,
                    'appointmentCount' => $branch->appointments_count,
                    'dentistCount' => $branch->dentists_count,
                    'staffCount' => $branch->staff_count,
                    'status' => 'Active' // Assuming all branches are active
                ];
            });

        return [
            'totalBranches' => $branches->count(),
            'branches' => $branches
        ];
    }

    private function getFinancialOverview()
    {
        // This would need to be implemented based on your billing system
        // For now, returning placeholder data
        return [
            'totalRevenue' => 0,
            'monthlyRevenue' => 0,
            'pendingPayments' => 0,
            'paymentMethods' => ['Cash', 'Credit Card', 'Debit Card', 'Bank Transfer']
        ];
    }

    private function getRecentActivities()
    {
        $recentAppointments = Appointment::with(['patient.user', 'dentist.user', 'branch'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($appointment) {
                return [
                    'type' => 'Appointment',
                    'description' => 'New appointment created for ' . $appointment->patient->user->first_name . ' ' . $appointment->patient->user->last_name,
                    'branch' => $appointment->branch ? $appointment->branch->branch_name : 'N/A',
                    'timestamp' => $appointment->created_at->diffForHumans()
                ];
            });

        return $recentAppointments;
    }

    public function getAppointments()
    {
        $appointments = Appointment::with(['patient.user', 'dentist.user', 'branch', 'schedule'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($appointments);
    }

    public function getBilling()
    {
        // This would need to be implemented based on your billing system
        return response()->json([
            'message' => 'Billing system not yet implemented'
        ]);
    }

    public function getDentists()
    {
        $dentists = Dentist::with(['user', 'branches'])
            ->get()
            ->map(function ($dentist) {
                return [
                    'id' => $dentist->dentist_id,
                    'firstName' => $dentist->user->first_name,
                    'lastName' => $dentist->user->last_name,
                    'email' => $dentist->user->email,
                    'phoneNumber' => $dentist->user->phone_number,
                    'position' => 'Dentist',
                    'branchName' => $dentist->branches->first() ? $dentist->branches->first()->branch_name : 'N/A',
                    'branchId' => $dentist->branches->first() ? $dentist->branches->first()->branch_id : null
                ];
            });

        return response()->json([
            'dentists' => $dentists,
            'branches' => Branches::select('branch_id', 'branch_name')->get(),
            'pagination' => [
                'currentPage' => 1,
                'totalPages' => 1,
                'perPage' => 10,
                'totalRecords' => $dentists->count()
            ]
        ]);
    }

    public function getStaff()
    {
        $staff = Staff::with(['user', 'branches'])
            ->get()
            ->map(function ($staffMember) {
                return [
                    'id' => $staffMember->staff_id,
                    'firstName' => $staffMember->user->first_name,
                    'lastName' => $staffMember->user->last_name,
                    'email' => $staffMember->user->email,
                    'phoneNumber' => $staffMember->user->phone_number,
                    'position' => $staffMember->position,
                    'branchName' => $staffMember->branches->first() ? $staffMember->branches->first()->branch_name : 'N/A'
                ];
            });

        return response()->json([
            'staff' => $staff,
            'branches' => Branches::select('branch_id', 'branch_name')->get()
        ]);
    }

    public function getBranches()
    {
        $branches = Branches::all()
            ->map(function ($branch) {
                return [
                    'id' => $branch->branch_id,
                    'name' => $branch->branch_name,
                    'image' => $branch->branch_logo ? '/storage/' . $branch->branch_logo : '/images/drpalabang.jpg',
                    'description' => 'DRP Dental Clinic - ' . $branch->branch_name,
                    'address' => $branch->branch_address,
                    'map' => $branch->branch_map,
                    'status' => 'Active',
                    'contactNumber' => $branch->branch_contact,
                    'email' => $branch->branch_email
                ];
            });

        return response()->json([
            'branches' => $branches
        ]);
    }
}
