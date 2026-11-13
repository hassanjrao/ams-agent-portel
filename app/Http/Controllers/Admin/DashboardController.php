<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AgencyStatus;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Booking;
use App\Models\FlightGroup;
use App\Models\Payment;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * What needs AMS's attention today.
     */
    public function __invoke(): Response
    {
        $upcoming = FlightGroup::query()->bookable()->withSeatsTaken()->get(['id', 'total_seats']);

        return Inertia::render('admin/Dashboard', [
            'stats' => [
                'pendingAgencies' => Agency::query()->where('status', AgencyStatus::Pending)->count(),
                'pendingPayments' => Payment::query()->where('status', PaymentStatus::Pending)->count(),
                'activeHolds' => Booking::query()->where('status', BookingStatus::OnHold)->where('hold_expires_at', '>', now())->count(),
                'openGroups' => $upcoming->count(),
                'seatsLeft' => $upcoming->sum(fn (FlightGroup $g) => $g->seatsAvailable()),
                'confirmedThisMonth' => (int) Booking::query()
                    ->where('status', BookingStatus::Confirmed)
                    ->where('confirmed_at', '>=', now()->startOfMonth())
                    ->sum('total_amount'),
            ],
        ]);
    }
}
