<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\BookingPdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class BookingPdfController extends Controller
{
    public function __construct(private BookingPdf $pdf) {}

    /**
     * A booking as a PDF, for AMS to send to the agency or for the agency itself.
     * ?price=0 leaves the fares out, for an agency to pass on to its own customer.
     */
    public function __invoke(Request $request, Booking $booking): Response
    {
        Gate::authorize('view', $booking);

        $withPrice = $request->boolean('price', true);

        return response($this->pdf->render($booking, $withPrice), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->pdf->filename($booking).'"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
