<?php

use App\Http\Controllers\Appointment\BranchAppointmentController;
use App\Http\Controllers\Appointment\DentistAppointmentController;
use App\Http\Controllers\Clinic\GenerateScheduleController;
use App\Http\Controllers\Clinic\TreatmentController;
use App\Http\Controllers\Appointment\ScheduleAppointmentController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('appointment', function () {
    return Inertia::render('appointment/SelectBranch');
})->name('appointment');

Route::get('fetch-branches', [BranchAppointmentController::class, 'getBranches'])
    ->name('appointment.branches');

Route::post('appointment', [BranchAppointmentController::class, 'store'])
    ->name('branch.store');

Route::post('appointment/date-time', [DentistAppointmentController::class, 'store'])
    ->name('dentist.store');

Route::get('appointment/show-dentists', function () {
    $branch_id = session('selected_branch_id');
    if (!$branch_id) {
        return redirect()->route('appointment')->with('error', 'Please select a branch.');
    }
    return Inertia::render('appointment/SelectDentistAndTreatment', [
        'branch_id' => $branch_id,
    ]);
})->name('appointment.show.dentists');

Route::get('appointment/show-date-time', function () {
    $branch_id = session('selected_branch_id');
    $dentist_id = session('selected_dentist_id');
    $treatment_id = session('selected_treatment_id');

    if (!$branch_id || !$dentist_id || !$treatment_id) {
        return redirect()->route('appointment.show.dentists')->with('error', 'Please select a dentist and treatment.');
    }

    return Inertia::render('appointment/SelectDateAndTime', [
        'branch_id' => $branch_id,
        'dentist_id' => $dentist_id,
        'treatment_id' => $treatment_id,
    ]);
})->name('appointment.show.date-time');

Route::get('appointment/branch/{branch_id}/dentist', [DentistAppointmentController::class, 'getDentistsForBranch'])
    ->name('appointment.dentists');

Route::get('appointment/treatments', [TreatmentController::class, 'getTreatments'])
    ->name('appointment.treatments');


Route::post('/generate-schedule', [GenerateScheduleController::class, 'generate']);

Route::get('appointment/branch/{branch_id}/dentist/schedule', [ScheduleAppointmentController::class, 'getDentistSchedule'])
    ->name('appointment.dentist.schedule');

Route::post('appointment/schedule', [ScheduleAppointmentController::class, 'store'])
    ->name('appointment.store');

    Route::get('/appointment/confirm', [ScheduleAppointmentController::class, 'confirm'])->name('appointment.confirm');
Route::post('/appointment/save', [ScheduleAppointmentController::class, 'save'])->name('appointment.save');
Route::get('/appointment/success', fn() => Inertia::render('appointment/Success'))->name('appointment.success');