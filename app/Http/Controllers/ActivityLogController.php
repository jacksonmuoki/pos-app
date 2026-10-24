<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = ActivityLog::with('user')
            ->when($request->user_id, fn ($q, $id) => $q->where('user_id', $id))
            ->when($request->action, fn ($q, $a) => $q->where('action', 'like', "$a%"))
            ->when($request->from, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('activity.index', [
            'logs'    => $logs,
            'users'   => \App\Models\User::orderBy('name')->get(['id', 'name']),
            'actions' => ActivityLog::select('action')->distinct()->orderBy('action')->pluck('action'),
        ]);
    }
}