<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class AppointmentController extends Controller
{
    public function __invoke(Request $request)
    {
        Inertia::setRootView('web/Appointment');
    }
}
