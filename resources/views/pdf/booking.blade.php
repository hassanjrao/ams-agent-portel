@php($money = fn (int $amount) => \App\Services\BookingPdf::money($amount))
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $booking->reference }}</title>
    <style>
        @page { margin: 28px 32px 40px; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1d150b; margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }
        .muted { color: #6f6253; }
        .gold { color: #93661a; }
        .label { font-size: 7.5px; font-weight: bold; letter-spacing: 1.2px; text-transform: uppercase; color: #93661a; }
        .head td { padding: 0; }
        .logo { height: 46px; }
        .title { font-size: 18px; font-weight: bold; margin: 0; }
        .ref { font-size: 13px; font-weight: bold; letter-spacing: 0.5px; }
        .pill { display: inline-block; padding: 2px 8px; border-radius: 8px; font-size: 8.5px; font-weight: bold; }
        .pill-confirmed { background: #dcf3e6; color: #11663a; }
        .pill-on_hold { background: #fbefd2; color: #85570c; }
        .pill-cancelled, .pill-expired { background: #f8dede; color: #9b1c1c; }
        .rule { height: 2px; background: #c8962b; margin: 12px 0 14px; }
        .card { border: 1px solid #e7dcc6; border-radius: 6px; padding: 10px 12px; }
        .grid td { width: 50%; }
        .section { margin-top: 14px; }
        .section h2 { font-size: 11px; margin: 0 0 6px; }
        .rows th { background: #f6efe1; text-align: left; font-size: 7.5px; text-transform: uppercase; letter-spacing: 0.8px; color: #6f6253; padding: 6px 7px; border-bottom: 1px solid #e7dcc6; }
        .rows td { padding: 6px 7px; border-bottom: 1px solid #efe6d4; }
        .rows .num { text-align: right; }
        .mono { font-family: 'DejaVu Sans Mono', monospace; font-size: 9px; }
        .strong { font-weight: bold; }
        .total td { font-size: 12px; font-weight: bold; border-bottom: 0; border-top: 1.5px solid #c8962b; padding-top: 8px; }
        .note { margin-top: 14px; padding: 8px 10px; background: #fbf7ef; border-left: 3px solid #c8962b; font-size: 9px; }
        .foot { position: fixed; bottom: -24px; left: 0; right: 0; font-size: 8px; color: #8a7d6c; text-align: center; }
    </style>
</head>
<body>
    <div class="foot">AMS Global Travel &amp; Tours · amsglobal.pk · Issued {{ $issuedAt }} (PKT)</div>

    <table class="head">
        <tr>
            <td>
                @if ($logo)
                    <img class="logo" src="{{ $logo }}" alt="AMS Global Travel & Tours">
                @endif
            </td>
            <td style="text-align: right;">
                <p class="title">{{ $title }}</p>
                <div class="ref">{{ $booking->reference }}</div>
                <div style="margin-top: 4px;">
                    <span class="pill pill-{{ $status }}">{{ $statusLabel }}</span>
                    @if ($booking->pnr)
                        <span class="pill" style="background: #1d150b; color: #fff;">PNR {{ $booking->pnr }}</span>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="rule"></div>

    <table class="grid">
        <tr>
            <td style="padding-right: 6px;">
                <div class="card">
                    <div class="label">Agency</div>
                    <div class="strong" style="font-size: 12px; margin-top: 3px;">{{ $agency->name }}</div>
                    <div style="margin-top: 2px;">Agent: <span class="strong">{{ $agentName }}</span></div>
                    <div class="muted" style="margin-top: 2px;">{{ $agency->city }} · {{ $agency->phone }}</div>
                </div>
            </td>
            <td style="padding-left: 6px;">
                <div class="card">
                    <div class="label">Group</div>
                    <div class="strong" style="font-size: 12px; margin-top: 3px;">{{ $sector }}</div>
                    <div style="margin-top: 2px;">{{ $airline }} · {{ $groupType }}@if ($durationDays) · {{ $durationDays }} days @endif</div>
                    <div class="muted" style="margin-top: 2px;">{{ $paxSummary }} · booked {{ $bookedAt }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section">
        <h2>Flights</h2>
        <table class="rows">
            <thead>
                <tr><th>Flight</th><th>Date</th><th>Route</th><th>Departs</th><th>Arrives</th><th>Baggage</th><th>Meal</th></tr>
            </thead>
            <tbody>
                @foreach ($legs as $leg)
                    <tr>
                        <td class="strong">{{ $leg['flight'] }}</td>
                        <td>{{ $leg['date'] }}</td>
                        <td class="strong">{{ $leg['route'] }}</td>
                        <td>{{ $leg['departs'] }}</td>
                        <td>{{ $leg['arrives'] }}</td>
                        <td>{{ $leg['baggage'] ? $leg['baggage'].' kg' : '-' }}</td>
                        <td>{{ $leg['meal'] ? 'Yes' : 'No' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="muted" style="margin-top: 4px; font-size: 8px;">Times are local at each airport, 24-hour.</div>
    </div>

    <div class="section">
        <h2>Passengers</h2>
        <table class="rows">
            <thead>
                <tr><th>#</th><th>Name</th><th>Type</th><th>Passport</th><th>Date of birth</th><th>Ticket</th></tr>
            </thead>
            <tbody>
                @forelse ($passengers as $i => $p)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td class="strong">{{ $p['name'] }}</td>
                        <td>{{ $p['type'] }}</td>
                        <td>
                            @if ($p['passport'])
                                <span class="mono">{{ $p['passport'] }}</span>
                                @if ($p['passportExpiry'])<div class="muted" style="font-size: 8px;">exp. {{ $p['passportExpiry'] }}</div>@endif
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $p['dob'] ?? '-' }}</td>
                        <td class="mono">{{ $p['ticket'] ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">Names not added yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($withPrice)
        <div class="section">
            <h2>Fare</h2>
            <table class="rows" style="width: 60%; margin-left: 40%;">
                @foreach ($fares as $fare)
                    <tr>
                        <td>{{ $fare['count'] }} × {{ $fare['label'] }}</td>
                        <td class="num muted">{{ $money($fare['fare']) }} each</td>
                        <td class="num">{{ $money($fare['count'] * $fare['fare']) }}</td>
                    </tr>
                @endforeach
                <tr class="total">
                    <td colspan="2">Total</td>
                    <td class="num">{{ $money($total) }}</td>
                </tr>
            </table>
        </div>
    @endif

    @if ($holdUntil)
        <div class="note">
            Seats are held until <strong>{{ $holdUntil }}</strong> (PKT). Send payment before then to confirm the booking.
        </div>
    @endif
</body>
</html>
