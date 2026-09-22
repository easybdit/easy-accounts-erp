<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $activities = Activity::query()
            ->with('causer:id,name')
            ->when($request->string('subject_type')->toString(), fn ($query, $type) => $query->where('subject_type', $type))
            ->when($request->string('event')->toString(), fn ($query, $event) => $query->where('event', $event))
            ->latest()
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Activity $activity) => [
                'id' => $activity->id,
                'description' => $activity->description,
                'event' => $activity->event,
                'subject_type' => $activity->subject_type ? class_basename($activity->subject_type) : null,
                'subject_id' => $activity->subject_id,
                'causer' => $activity->causer?->name,
                'attribute_changes' => $activity->attribute_changes,
                'created_at' => $activity->created_at?->toDateTimeString(),
            ]);

        $subjectTypes = Activity::query()
            ->whereNotNull('subject_type')
            ->distinct()
            ->orderBy('subject_type')
            ->pluck('subject_type')
            ->map(fn (string $type) => ['value' => $type, 'label' => class_basename($type)])
            ->values();

        return Inertia::render('Security/AuditLog/Index', [
            'activities' => $activities,
            'subjectTypes' => $subjectTypes,
            'filters' => $request->only(['subject_type', 'event']),
        ]);
    }
}
