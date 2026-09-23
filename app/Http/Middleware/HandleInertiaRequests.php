<?php

namespace App\Http\Middleware;

use App\Models\Accounting\AccountingSettings;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'permissions' => fn () => $request->user()?->getAllPermissions()->pluck('name') ?? [],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            // Shared (rather than passed per-page) so every page — including
            // the guest login screen — can brand itself with the uploaded
            // company logo, falling back to the default mark when unset.
            'company' => [
                'logoUrl' => function () {
                    $path = AccountingSettings::current()->logo_path;

                    // A root-relative path rather than Storage::url()'s
                    // APP_URL-based absolute one: local dev often runs on a
                    // port (e.g. :8000) that APP_URL doesn't reflect, which
                    // would otherwise silently point the browser at the
                    // wrong port.
                    return $path ? '/storage/'.$path : null;
                },
            ],
        ];
    }
}
