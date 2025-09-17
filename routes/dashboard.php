<?php
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request;

use App\Http\Controllers\Patient\PatientListController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Appointment\AppointmentListController;

Route::middleware('auth')->get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');
Route::middleware('auth')->get('/dashboard/patients', [PatientListController::class, 'index'])->name('dashboard.patients');
Route::middleware('auth')->get('/dashboard/appointments', [AppointmentListController::class, 'index'])->name('dashboard.appointments');
