<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Mail;

Route::get('/', function () {
    return Inertia::render('home pages/Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard/Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::prefix('dashboard/owner')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/api/dashboard-data', [App\Http\Controllers\Dashboard\OwnerDashboardController::class, 'index'])->name('owner.dashboard.data');

    Route::get('/appointments/AppointmentList', function () {
        return Inertia::render('Dashboard/General/AppointmentList');
    })->name('owner.appointments');

    Route::get('/records/PatientRecords', function () {
        return Inertia::render('Dashboard/Clinic/PatientRecords');
    })->name('owner.patients');

    Route::get('/billing', function () {
        return Inertia::render('Dashboard/General/Billing');
    })->name('owner.billing');

    Route::get('/billing/payment', function () {
        return Inertia::render('Dashboard/General/Payment');
    })->name('owner.payment');

    Route::get('/clinic/PaymentMethod', function () {
        return Inertia::render('Dashboard/Clinic/PaymentMethods');
    })->name('owner.payment.method');

    Route::get('/records/DentistRecords', function () {
        return Inertia::render('Dashboard/Clinic/DentistRecords');
    })->name('owner.dentists');

    Route::get('/records/StaffRecords', function () {
        return Inertia::render('Dashboard/Clinic/StaffRecords');
    })->name('owner.staff');

    Route::get('/clinic/BranchSettings', function () {
        return Inertia::render('Dashboard/Clinic/BranchSettings');
    })->name('owner.branches');

    Route::get('/clinic/ServicesList', function () {
        return Inertia::render('Dashboard/Clinic/ServicesList');
    })->name('owner.services');

    Route::get('/reports', function () {
        return Inertia::render('Dashboard/Owner/Reports');
    })->name('owner.reports');

    Route::get('/clinic/ToothMarks', function () {
        return Inertia::render('Dashboard/Clinic/ToothMarks');
    })->name('owner.tooth.marks');

});

Route::prefix('dashboard/patient')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/appointments/AppointmentList', function () {
        return Inertia::render('Dashboard/General/AppointmentList');
    })->name('patient.appointments');

    Route::get('/dentalChart', function () {
        return Inertia::render('Dashboard/General/DentalChart');
    })->name('patient.dental.chart');

    Route::get('/billing', function () {
        return Inertia::render('Dashboard/General/Billing');
    })->name('patient.billing');

    Route::get('/billing/payment', function () {
        return Inertia::render('Dashboard/General/Payment');
    })->name('patient.payment');
});

Route::prefix('dashboard/dentist')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/appointments/AppointmentList', function () {
        return Inertia::render('Dashboard/Dentist Dashboard/D_AppointmentList');
    })->name('dentist.appointments');

    Route::get('/records/dentalChart', function () {
        return Inertia::render('Dashboard/Dentist Dashboard/D_dentalChart');
    })->name('dentist.dentalChart');

    Route::get('/records/UserDetails', function () {
        return Inertia::render('Dashboard/Dentist Dashboard/D_UserDetails');
    })->name('dentist.profile');
});

Route::prefix('dashboard/receptionist')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/appointments/AppointmentList', function () {
        return Inertia::render('Dashboard/Receptionist Dashboard/R_AppointmentList');
    })->name('receptionist.appointments');

    Route::get('/records/PatientRecords', function () {
        return Inertia::render('Dashboard/Receptionist Dashboard/R_PatientRecords');
    })->name('receptionist.patients');

    Route::get('/billing/Billing', function () {
        return Inertia::render('Dashboard/Receptionist Dashboard/R_Billing');
    })->name('receptionist.billing');

    Route::get('/records/UserDetails', function () {
        return Inertia::render('Dashboard/Receptionist Dashboard/R_UserDetails');
    })->name('receptionist.profile');
});

Route::get('branches', function () {
    return Inertia::render('home pages/Branches');
})->name('branches');

Route::get('about', function () {
    return Inertia::render('home pages/About Us');
})->name('about');

Route::get('services', function () {
    return Inertia::render('home pages/Services');
})->name('services');

Route::get('contact', function () {
    return Inertia::render('home pages/Contact Us');
})->name('contact');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/appointment.php';
require __DIR__.'/dashboard.php';
