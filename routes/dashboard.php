<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Patient\PatientListController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Appointment\AppointmentListController;
use App\Http\Controllers\Clinic\BranchController;
use App\Http\Controllers\Clinic\TreatmentController;
use App\Http\Controllers\Dentist\DentistController;
use App\Http\Controllers\Billing\BillingController;
use App\Http\Controllers\Billing\PaymentController;
use App\Http\Controllers\Billing\PaymentMethodController;
use App\Http\Controllers\Patient\DentalChartController;
use App\Http\Controllers\Patient\ToothRecordController;
use App\Http\Controllers\Clinic\ToothMarksController;

Route::middleware('auth')->group(function () {
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');
    Route::get('/dashboard/patients', [PatientListController::class, 'index'])->name('dashboard.patients');
    Route::get('/dashboard/appointments', [AppointmentListController::class, 'index'])->name('dashboard.appointments');

    Route::prefix('dashboard/owner/api')->group(function () {
        Route::get('/branches', [BranchController::class, 'index']);
        Route::post('/branches', [BranchController::class, 'store']);
        Route::put('/branches/{id}', [BranchController::class, 'update']);
        Route::get('/dentists', [DentistController::class, 'getDentists'])->name('owner.dentists.data');
        Route::put('/dentists/{dentist_id}', [DentistController::class, 'updateDentist'])->name('owner.dentists.update');
        Route::get('/dentist/{id}', [DentistController::class, 'getDentistDetail'])->name('owner.dentist.api.detail');
    });

    Route::prefix('dashboard/clinic/api')->group(function () {
        Route::get('/treatments', [TreatmentController::class, 'getTreatments']);
        Route::post('/treatments', [TreatmentController::class, 'store']);
        Route::put('/treatments/{id}', [TreatmentController::class, 'update']);
    });

    Route::get('/pages/Accounts/Owner Dashboard/Own_DentistInformation/{dentist_id?}', [DentistController::class, 'index'])->name('owner.dentist.records');

    Route::prefix('api')->group(function () {
        Route::post('/billings', [BillingController::class, 'create']);
        Route::get('/billings', [BillingController::class, 'fetchAll']);
        Route::put('/billings/{billingId}', [BillingController::class, 'update'])->where('billingId', '[0-9a-zA-Z-]+');

        Route::get('/payments', [PaymentController::class, 'index']);
        Route::post('/payments', [PaymentController::class, 'store']);
        Route::put('/payments/{paymentId}', [PaymentController::class, 'update'])->where('paymentId', '[0-9a-zA-Z-]+');

        Route::get('/payment-methods', [PaymentMethodController::class, 'index']);
        Route::post('/payment-methods', [PaymentMethodController::class, 'store']);
        Route::put('/payment-methods/{payment_method_id}', [PaymentMethodController::class, 'update']);
    });

  Route::get('/dental-charts', [DentalChartController::class, 'index'])->name('patient.dental-charts.index');
Route::post('/dental-charts', [DentalChartController::class, 'store'])->name('patient.dental-charts.store');
Route::get('/dental-charts/without', [DentalChartController::class, 'withoutCharts'])->name('patient.dental-charts.without');
Route::get('/dentalChart/{patient_id}', [DentalChartController::class, 'show'])->name('patient.dental.chart');
Route::get('/dentalChart/{patient_id}/tooth-records', [DentalChartController::class, 'getToothRecords'])->name('patient.dental.tooth-records');
Route::get('/tooth/{tooth_id}', [ToothRecordController::class, 'edit'])->name('patient.tooth.edit');
Route::put('/tooth/{tooth_id}', [ToothRecordController::class, 'update'])->name('patient.tooth.update');
    Route::get('/tooth-marks/data', [ToothMarksController::class, 'index'])->name('tooth-marks.data');
    Route::post('/tooth-marks', [ToothMarksController::class, 'store'])->name('tooth-marks.store');
    Route::put('/tooth-marks/{tooth_mark_id}', [ToothMarksController::class, 'update'])->name('tooth-marks.update');
    Route::delete('/tooth-marks/{tooth_mark_id}', [ToothMarksController::class, 'destroy'])->name('tooth-marks.destroy');

});