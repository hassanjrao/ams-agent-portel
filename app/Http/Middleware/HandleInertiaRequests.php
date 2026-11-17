<?php

namespace App\Http\Middleware;

use App\Enums\AgencyStatus;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Agency;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\FreePlan;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            // The logged-in agency's standing, shown in the header on every page. Named so it can't
            // be shadowed by a page's own "agency" prop (AMS's agency pages have one).
            'myAgency' => fn () => $user && ! $user->isAdmin() && $user->agency
                ? [
                    'id' => $user->agency->id,
                    'name' => $user->agency->name,
                    'status' => $user->agency->status->value,
                    'balance' => $user->agency->balance(),
                    // Beyond the free plan: can look, can't act.
                    'locked' => ! FreePlan::covers($user->agency),
                    'lockedMessage' => FreePlan::lockedMessage(),
                ]
                : null,
            // For AMS staff: the free plan's limit and whether agencies are locked out by it.
            'freePlan' => fn () => $user?->isAdmin() ? FreePlan::summary() : null,
            // What is waiting for AMS, as badges in the admin sidebar.
            'adminQueue' => fn () => $user?->isAdmin()
                ? [
                    'payments' => Payment::query()->where('status', PaymentStatus::Pending)->count(),
                    'agencies' => Agency::query()->where('status', AgencyStatus::Pending)->count(),
                    'holds' => Booking::query()->where('status', BookingStatus::OnHold)->where('hold_expires_at', '>', now())->count(),
                ]
                : null,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
