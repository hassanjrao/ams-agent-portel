<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\BookingPassenger;
use App\Models\FlightGroupLeg;
use App\Presenters\BookingPresenter;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Str;

/**
 * A booking as a one-page PDF that AMS sends to the agency (or the agency downloads itself):
 * flights, passengers and the agency, with or without the fares.
 */
class BookingPdf
{
    // Times on the PDF are Pakistan time; flight times stay the airports' local times as typed.
    private const TIMEZONE = 'Asia/Karachi';

    /**
     * "Madina-Travels_MUX-JED-MUX_29-Nov-2026_AMS-261100001.pdf": agency, route, departure date
     * and reference, so a folder of them sorts and reads at a glance.
     */
    public function filename(Booking $booking, bool $withPrice): string
    {
        $booking->loadMissing(['agency', 'flightGroup']);
        $agency = trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', Str::ascii($booking->agency->name)), '-') ?: 'Agency';

        return implode('_', array_filter([
            $agency,
            $booking->flightGroup->sector,
            $booking->flightGroup->departure_date->format('d-M-Y'),
            $booking->reference,
            $withPrice ? null : 'without-price',
        ])).'.pdf';
    }

    /**
     * The PDF's bytes.
     */
    public function render(Booking $booking, bool $withPrice): string
    {
        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        // Everything the page needs is inline (the logo is a data URI): never fetch from the web.
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('pdf.booking', $this->data($booking, $withPrice))->render());
        $dompdf->setPaper('A4');
        $dompdf->render();

        return (string) $dompdf->output();
    }

    /**
     * @return array<string, mixed>
     */
    public function data(Booking $booking, bool $withPrice): array
    {
        $booking->loadMissing(['flightGroup.airline', 'flightGroup.groupType', 'flightGroup.legs', 'passengers', 'agency', 'user']);
        $group = $booking->flightGroup;
        $status = BookingPresenter::status($booking);
        $logo = public_path('images/ams-logo.png');

        return [
            'booking' => $booking,
            'withPrice' => $withPrice,
            'title' => match ($status) {
                BookingStatus::Confirmed->value => 'Booking confirmation',
                BookingStatus::OnHold->value => 'Booking on hold',
                default => 'Booking',
            },
            'status' => $status,
            'statusLabel' => ucfirst(str_replace('_', ' ', $status)),
            'logo' => is_file($logo) ? 'data:image/png;base64,'.base64_encode((string) file_get_contents($logo)) : null,
            'issuedAt' => now()->timezone(self::TIMEZONE)->format('d M Y, H:i'),
            'bookedAt' => $booking->created_at->timezone(self::TIMEZONE)->format('d M Y, H:i'),
            'holdUntil' => $status === BookingStatus::OnHold->value
                ? $booking->hold_expires_at?->timezone(self::TIMEZONE)->format('d M Y, H:i')
                : null,
            'agency' => $booking->agency,
            'agentName' => $booking->user->name,
            'airline' => $group->airline->name,
            'sector' => $group->sector,
            'groupType' => $group->groupType->name,
            'durationDays' => $group->duration_days,
            'legs' => $group->legs->map(fn (FlightGroupLeg $leg) => [
                'flight' => $leg->flight_number,
                'date' => $leg->departs_at->format('D d M Y'),
                'route' => "{$leg->origin} - {$leg->destination}",
                'departs' => $leg->departs_at->format('H:i'),
                'arrives' => $leg->arrives_at->format('H:i').($leg->arrives_at->isSameDay($leg->departs_at) ? '' : ' ('.$leg->arrives_at->format('d M').')'),
                'baggage' => $leg->baggage,
                'meal' => $leg->meal,
            ])->all(),
            'passengers' => $booking->passengers->map(fn (BookingPassenger $p) => [
                'name' => trim("{$p->title} {$p->given_name} {$p->surname}"),
                'type' => ucfirst($p->type->value),
                'passport' => $p->passport_number,
                'passportExpiry' => $p->passport_expiry?->format('d M Y'),
                'dob' => $p->date_of_birth?->format('d M Y'),
                'ticket' => $p->ticket_number,
            ])->all(),
            'fares' => array_values(array_filter([
                ['label' => 'Adult', 'count' => $booking->adults, 'fare' => $booking->adult_fare],
                ['label' => 'Child', 'count' => $booking->children, 'fare' => $booking->child_fare],
                ['label' => 'Infant', 'count' => $booking->infants, 'fare' => $booking->infant_fare],
            ], fn (array $row) => $row['count'] > 0)),
            'total' => $booking->total_amount,
            'paxSummary' => collect([
                [$booking->adults, 'adult', 'adults'],
                [$booking->children, 'child', 'children'],
                [$booking->infants, 'infant', 'infants'],
            ])->filter(fn ($p) => $p[0] > 0)->map(fn ($p) => $p[0].' '.($p[0] === 1 ? $p[1] : $p[2]))->implode(', '),
        ];
    }

    public static function money(int $amount): string
    {
        return 'PKR '.number_format($amount);
    }
}
