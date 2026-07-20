<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markAsRead(Request $request)
    {
        if ($request->user()) {
            $request->user()->unreadNotifications->markAsRead();
        }
        return response()->json(['message' => 'success']);
    }
}
