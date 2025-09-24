<?php
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request;

use App\Http\Controllers\Patient\PatientListController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Appointment\AppointmentListController;
use App\Http\Controllers\Clinic\BranchController;
use App\Http\Controllers\Clinic\TreatmentController;
use App\Http\Controllers\Dentist\DentistController;

Route::middleware('auth')->get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');
Route::middleware('auth')->get('/dashboard/patients', [PatientListController::class, 'index'])->name('dashboard.patients');
Route::middleware('auth')->get('/dashboard/appointments', [AppointmentListController::class, 'index'])->name('dashboard.appointments');

Route::prefix('dashboard/owner/api')->group(function () {
    Route::get('/branches', [BranchController::class, 'index']);
    Route::post('/branches', [BranchController::class, 'store']);
    Route::put('/branches/{id}', [BranchController::class, 'update']);
});

Route::prefix('dashboard/clinic/api')->group(function () {
    Route::get('/treatments', [TreatmentController::class, 'getTreatments']);
    Route::post('/treatments', [TreatmentController::class, 'store']);
    Route::put('/treatments/{id}', [TreatmentController::class, 'update']);
});

Route::get('/dashboard/owner/api/dentists', [App\Http\Controllers\Dentist\DentistController::class, 'getDentists'])->name('owner.dentists.data');
Route::put('/dashboard/owner/api/dentists/{dentist_id}', [App\Http\Controllers\Dentist\DentistController::class, 'updateDentist'])->name('owner.dentists.update');

Route::prefix('dashboard/owner/api')->group(function () {
    Route::get('/dentist/{id}', [\App\Http\Controllers\Dentist\DentistController::class, 'getDentistDetail'])->name('owner.dentist.api.detail');
});

Route::get('/pages/Accounts/Owner Dashboard/Own_DentistInformation/{dentist_id?}', [\App\Http\Controllers\Dentist\DentistController::class, 'index'])->name('owner.dentist.records');