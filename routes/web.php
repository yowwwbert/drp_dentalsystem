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

Route::get('appointment', function () {
    return Inertia::render('web/Appointment');
})->name('appointment');

Route::get('/test-email', function () {
    Mail::raw('This is a test email from Laravel.', function ($message) {
        $message->to('robertaceatencio@gmail.com')
                ->subject('Test Email');
    });
    return 'Test email sent successfully!';
})->name('test.email');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
