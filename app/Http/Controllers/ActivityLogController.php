<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        $query = ActivityLog::with('user')->latest('id');

        if ($search = $request->input('search')) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if ($aksi = $request->input('aksi')) {
            $query->where('aksi', $aksi);
        }

        $logs = $query->paginate(20)->withQueryString();

        return Inertia::render('Log/Index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'aksi']),
        ]);
    }
}
