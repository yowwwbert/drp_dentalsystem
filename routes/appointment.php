<?php

use App\Http\Controllers\Appointment\BranchAppointmentController;
use App\Http\Controllers\Appointment\DentistAppointmentController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

    Route::get('appointment', function () {
        return Inertia::render('appointment/SelectBranch');
    })->name('appointment');
    Route::get('fetch-branches', [BranchAppointmentController::class, 'getBranchesForAppointment'])
        ->name('appointment.branches');

    Route::post('appointment', [BranchAppointmentController::class, 'store'])
        ->name('branch.store');

    Route::get('appointment/branch/{branch_id}/dentist', [DentistAppointmentController::class, 'getDentistsForBranch'])
        ->name('appointment.dentists');


    Route::get('appointment/branch/{branch_id}', function ($branch_id) {
        return Inertia::render('appointment/SelectDateAndTime', [
            'branch_id' => $branch_id,
        ]);
    })->name('appointment.date-time');

?>