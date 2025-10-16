<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Auth::user()->notifications()
            ->latest()
            ->get()
            ->map(function ($notification) {
                return [
                    'notification_id' => $notification->id, // Map id to notification_id
                    'data' => $notification->data,
                    'read_at' => $notification->read_at ? $notification->read_at->toIso8601String() : null,
                    'created_at' => $notification->created_at->toIso8601String(),
                ];
            });

        return response()->json($notifications);
    }

    public function markAsRead(Request $request, $notification_id)
    {
        $notification = Auth::user()->notifications()
            ->where('id', $notification_id)
            ->firstOrFail();
        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }
}