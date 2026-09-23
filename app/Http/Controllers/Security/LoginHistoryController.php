<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class LoginHistoryController extends Controller
{
    public function index(Request $request): Response
    {
        $entries = Activity::query()
            ->where('log_name', 'auth')
            ->with('causer:id,name,email')
            ->when($request->string('event')->toString(), fn ($query, $event) => $query->where('event', $event))
            ->when($request->string('user_id')->toString(), fn ($query, $userId) => $query->where('causer_id', $userId))
            ->latest()
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Activity $activity) => [
                'id' => $activity->id,
                'event' => $activity->event,
                'description' => $activity->description,
                'user' => $activity->causer?->name,
                'user_email' => $activity->causer?->email,
                'ip' => $activity->properties->get('ip'),
                'user_agent' => $activity->properties->get('user_agent'),
                'reason' => $activity->properties->get('reason'),
                'created_at' => $activity->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('Security/LoginHistory/Index', [
            'entries' => $entries,
            'events' => ['login', 'logout', 'login_failed', 'login_blocked_locked', 'login_blocked_ip'],
            'users' => User::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['event', 'user_id']),
        ]);
    }
}
