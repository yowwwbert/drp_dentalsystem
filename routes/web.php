<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Mail;

Route::get('/', function () {
    return Inertia::render('home pages/Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Owner Dashboard Routes
Route::prefix('dashboard/owner')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/', function () {
        return Inertia::render('Accounts/Owner Dashboard/Own_dashboard');
    })->name('owner.dashboard');
    
    // API endpoints for dashboard data
    Route::get('/api/dashboard-data', [App\Http\Controllers\Dashboard\OwnerDashboardController::class, 'index'])->name('owner.dashboard.data');
    Route::get('/appointments/AppointmentList', function () {
        return Inertia::render('Accounts/Owner Dashboard/Own_AppointmentList');
    })->name('owner.appointments');
    
    Route::get('/records/PatientRecords', function () {
        return Inertia::render('Accounts/Owner Dashboard/Own_PatientRecords');
    })->name('owner.patients');
    
    Route::get('/billing/Billing', function () {
        return Inertia::render('Accounts/Owner Dashboard/Own_Billing');
    })->name('owner.billing');
    
    Route::get('/records/DentistRecords', function () {
        return Inertia::render('Accounts/Owner Dashboard/Own_DentistRecords');
    })->name('owner.dentists');
    
    Route::get('/records/StaffRecords', function () {
        return Inertia::render('Accounts/Owner Dashboard/Own_StaffRecords');
    })->name('owner.staff');
    
    Route::get('/clinic/BranchSettings', function () {
        return Inertia::render('Accounts/Owner Dashboard/Own_BranchSettings');
    })->name('owner.branches');
    
    Route::get('/clinic/ServicesList', function () {
        return Inertia::render('Accounts/Owner Dashboard/Own_ServicesList');
    })->name('owner.services');
    
    Route::get('/reports/Reports', function () {
        return Inertia::render('Accounts/Owner Dashboard/Own_Reports');
    })->name('owner.reports');
    
    Route::get('/data/Data', function () {
        return Inertia::render('Accounts/Owner Dashboard/Own_Data');
    })->name('owner.data');
    
    Route::get('/records/UserDetails', function () {
        $user = \Illuminate\Support\Facades\Auth::user();
        $owner = \App\Models\Users\Owner::where('owner_id', $user->user_id)->first();
        
        // Get user details with related data
        $userDetails = [
            'user' => $user,
            'owner' => $owner,
            'branch' => $user->branches()->first(),
        ];
        
        return Inertia::render('Accounts/Owner Dashboard/Own_UserDetails', $userDetails);
    })->name('owner.profile');
});

// Patient Dashboard Routes
Route::prefix('dashboard/patient')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/appointments/AppointmentList', function () {
        return Inertia::render('Accounts/Patient Dashboard/P_AppointmentList');
    })->name('patient.appointments');
    
    Route::get('/records/UserDetails', function () {
        return Inertia::render('Accounts/Patient Dashboard/P_UserDetails');
    })->name('patient.profile');
});

// Dentist Dashboard Routes
Route::prefix('dashboard/dentist')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/appointments/AppointmentList', function () {
        return Inertia::render('Accounts/Dentist Dashboard/D_AppointmentList');
    })->name('dentist.appointments');
    
    Route::get('/records/dentalChart', function () {
        return Inertia::render('Accounts/Dentist Dashboard/D_dentalChart');
    })->name('dentist.dentalChart');
    
    Route::get('/records/UserDetails', function () {
        return Inertia::render('Accounts/Dentist Dashboard/D_UserDetails');
    })->name('dentist.profile');
});

// Receptionist Dashboard Routes
Route::prefix('dashboard/receptionist')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/appointments/AppointmentList', function () {
        return Inertia::render('Accounts/Receptionist Dashboard/R_AppointmentList');
    })->name('receptionist.appointments');
    
    Route::get('/records/PatientRecords', function () {
        return Inertia::render('Accounts/Receptionist Dashboard/R_PatientRecords');
    })->name('receptionist.patients');
    
    Route::get('/billing/Billing', function () {
        return Inertia::render('Accounts/Receptionist Dashboard/R_Billing');
    })->name('receptionist.billing');
    
    Route::get('/records/UserDetails', function () {
        return Inertia::render('Accounts/Receptionist Dashboard/R_UserDetails');
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
