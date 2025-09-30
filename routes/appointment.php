<?php

use App\Http\Controllers\Appointment\BranchAppointmentController;
use App\Http\Controllers\Appointment\DentistAppointmentController;
use App\Http\Controllers\Clinic\GenerateScheduleController;
use App\Http\Controllers\Clinic\TreatmentController;
use App\Http\Controllers\Appointment\ScheduleAppointmentController;
use App\Http\Controllers\Appointment\AppointmentController;
use App\Http\Controllers\Patient\PatientListController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Clinic\BranchController;
use App\Http\Controllers\Dentist\DentistController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request;

Route::middleware('auth')->group(function () {
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');
    Route::get('/dashboard/patients', [PatientListController::class, 'index'])->name('dashboard.patients');
    Route::get('/dashboard/appointments', [AppointmentController::class, 'getAppointments'])->name('dashboard.appointments');

    Route::prefix('dashboard/owner/api')->group(function () {
        Route::get('/branches', [BranchController::class, 'index']);
        Route::post('/branches', [BranchController::class, 'store']);
        Route::put('/branches/{id}', [BranchController::class, 'update']);
        Route::get('/dentist/{id}', [DentistController::class, 'getDentistDetail'])->name('owner.dentist.api.detail');
    });

    Route::prefix('dashboard/clinic/api')->group(function () {
        Route::get('/treatments', [TreatmentController::class, 'getTreatments']);
        Route::post('/treatments', [TreatmentController::class, 'store']);
        Route::put('/treatments/{id}', [TreatmentController::class, 'update']);
    });

    Route::get('/dashboard/owner/api/dentists', [DentistController::class, 'getDentists'])->name('owner.dentists.data');
    Route::put('/dashboard/owner/api/dentists/{dentist_id}', [DentistController::class, 'updateDentist'])->name('owner.dentists.update');

    Route::prefix('api')->group(function () {
        Route::post('/appointments/{appointmentId}/cancel', [AppointmentController::class, 'cancel'])->name('appointment.cancel');
        Route::put('/appointments/{appointmentId}', [AppointmentController::class, 'reschedule'])->name('appointment.reschedule');
    });
});

Route::get('/appointment', function () {
    return Inertia::render('appointment/SelectBranch');
})->name('appointment');

Route::get('/fetch-branches', [BranchAppointmentController::class, 'getBranches'])->name('appointment.branches');

Route::post('/appointment', [BranchAppointmentController::class, 'store'])->name('branch.store');

Route::post('/appointment/date-time', [DentistAppointmentController::class, 'store'])->name('dentist.store');

Route::get('/appointment/show-dentists', function () {
    $branch_id = session('selected_branch_id');
    if (!$branch_id) {
        return redirect()->route('appointment')->with('error', 'Please select a branch.');
    }
    return Inertia::render('appointment/SelectDentistAndTreatment', [
        'branch_id' => $branch_id,
    ]);
})->name('appointment.show.dentists');

Route::get('/appointment/show-date-time', function () {
    $branch_id = session('selected_branch_id');
    $dentist_id = session('selected_dentist_id');
    $treatment_ids = session('selected_treatment_ids');

    if (!$branch_id || !$dentist_id || empty($treatment_ids)) {
        return redirect()->route('appointment.show.dentists')
            ->with('error', 'Please select a dentist and at least one treatment.');
    }

    return Inertia::render('appointment/SelectDateAndTime', [
        'branch_id' => $branch_id,
        'dentist_id' => $dentist_id,
        'treatment_ids' => $treatment_ids,
    ]);
})->name('appointment.show.date-time');

Route::get('/appointment/branch/{branch_id}/dentist', [DentistAppointmentController::class, 'getDentistsForBranch'])->name('appointment.dentists');

Route::get('/appointment/treatments', [TreatmentController::class, 'getTreatments'])->name('appointment.treatments');

Route::post('/generate-schedule', [GenerateScheduleController::class, 'generate']);

Route::get('/appointment/branch/{branch_id}/dentist/schedule', [ScheduleAppointmentController::class, 'getDentistSchedule'])->name('appointment.dentist.schedule');

Route::post('/appointment/store', [AppointmentController::class, 'store'])->name('appointment.store');

Route::get('/appointment/confirmation', function (Request $request) {
    $appointmentData = $request->session()->get('appointment');
    if (!$appointmentData) {
        \Illuminate\Support\Facades\Log::error('No appointment data in session for confirmation');
        return redirect()->route('appointment.show.dentists')
            ->with('error', 'No appointment data found. Please select again.');
    }
    return Inertia::render('appointment/ConfirmAppointment', [
        'appointment' => $appointmentData,
    ]);
})->name('appointment.confirmation');

Route::post('/appointment/confirm', [AppointmentController::class, 'confirm'])->name('appointment.confirm');

Route::get('/pages/Accounts/Owner Dashboard/Own_DentistInformation/{dentist_id?}', [DentistController::class, 'index'])->name('owner.dentist.records');

