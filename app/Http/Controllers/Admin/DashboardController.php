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
use App\Presenters\BookingPresenter;
use App\Presenters\PaymentPresenter;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * What needs AMS's attention today: payments to check, holds to confirm, agencies to approve.
     */
    public function __invoke(): Response
    {
        $upcoming = FlightGroup::query()->bookable()->withSeatsTaken()->get(['id', 'total_seats']);

        $holds = Booking::query()
            ->with(['flightGroup:id,sector,departure_date', 'agency:id,name,city'])
            ->where('status', BookingStatus::OnHold)
            ->where('hold_expires_at', '>', now())
            ->orderBy('hold_expires_at')
            ->limit(6)
            ->get();

        $payments = Payment::query()
            ->with(['bankAccount:id,bank_name', 'booking:id,reference', 'agency:id,name,city'])
            ->where('status', PaymentStatus::Pending)
            ->oldest()
            ->limit(6)
            ->get();

        return Inertia::render('admin/Dashboard', [
            'stats' => [
                'pendingAgencies' => Agency::query()->where('status', AgencyStatus::Pending)->count(),
                'pendingPayments' => Payment::query()->where('status', PaymentStatus::Pending)->count(),
                'pendingPaymentsValue' => (int) Payment::query()->where('status', PaymentStatus::Pending)->sum('amount'),
                'activeHolds' => Booking::query()->where('status', BookingStatus::OnHold)->where('hold_expires_at', '>', now())->count(),
                'openGroups' => $upcoming->count(),
                'seatsLeft' => $upcoming->sum(fn (FlightGroup $g) => $g->seatsAvailable()),
                'confirmedThisMonth' => (int) Booking::query()
                    ->where('status', BookingStatus::Confirmed)
                    ->where('confirmed_at', '>=', now()->startOfMonth())
                    ->sum('total_amount'),
            ],
            'holds' => $holds->map(fn (Booking $b) => BookingPresenter::row($b)),
            'payments' => $payments->map(fn (Payment $p) => PaymentPresenter::row($p)),
        ]);
    }
}
