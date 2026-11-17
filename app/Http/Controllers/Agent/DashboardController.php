<?php

namespace App\Http\Controllers\Agent;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\FlightGroup;
use App\Presenters\BookingPresenter;
use App\Presenters\GroupPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * An agency's home: holds running out, trips coming up, money owed and what's on sale next.
     */
    public function __invoke(Request $request): Response
    {
        $agency = $request->user()->agency;

        $holds = $agency->bookings()
            ->with('flightGroup:id,sector,departure_date')
            ->where('status', BookingStatus::OnHold)
            ->where('hold_expires_at', '>', now())
            ->orderBy('hold_expires_at')
            ->get();

        $upcoming = $agency->bookings()
            ->with('flightGroup:id,sector,departure_date')
            ->where('bookings.status', BookingStatus::Confirmed)
            ->join('flight_groups', 'flight_groups.id', '=', 'bookings.flight_group_id')
            ->whereDate('flight_groups.departure_date', '>=', today())
            ->orderBy('flight_groups.departure_date')
            ->select('bookings.*')
            ->limit(6)
            ->get();

        $onSale = FlightGroup::query()
            ->bookable()
            ->withSeatsTaken()
            ->with(['airline:id,name,code', 'groupType:id,name', 'legs'])
            ->orderBy('departure_date')
            ->limit(30)
            ->get()
            ->filter(fn (FlightGroup $g) => $g->seatsAvailable() > 0)
            ->take(4)
            ->values();

        return Inertia::render('agent/Dashboard', [
            'holds' => $holds->map(fn (Booking $b) => BookingPresenter::row($b)),
            'upcoming' => $upcoming->map(fn (Booking $b) => BookingPresenter::row($b)),
            'onSale' => $onSale->map(fn (FlightGroup $g) => GroupPresenter::card($g)),
            'stats' => [
                'holds' => $holds->count(),
                'holdsValue' => (int) $holds->sum('total_amount'),
                'upcomingTrips' => $agency->bookings()->where('status', BookingStatus::Confirmed)
                    ->whereHas('flightGroup', fn ($q) => $q->whereDate('departure_date', '>=', today()))->count(),
                'pendingPayments' => (int) $agency->payments()->where('status', PaymentStatus::Pending)->sum('amount'),
            ],
        ]);
    }
}
