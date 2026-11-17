<?php

namespace Database\Seeders;

use App\Enums\PaymentStatus;
use App\Models\Agency;
use App\Models\Airline;
use App\Models\BankAccount;
use App\Models\FlightGroup;
use App\Models\GroupType;
use App\Models\Payment;
use App\Models\User;
use App\Services\BookingService;
use App\Services\FlightGroupService;
use App\Services\PaymentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Local-only sample data: an admin, two agencies (one waiting for approval) and a few groups.
 * Every login's password is "password".
 */
class DemoSeeder extends Seeder
{
    public function run(FlightGroupService $groups, BookingService $bookings): void
    {
        $admin = User::factory()->admin()->create(['name' => 'AMS Admin', 'email' => 'admin@amsglobal.test']);

        $agency = Agency::factory()->create(['name' => 'Sample Travel & Tours', 'city' => 'Multan']);
        $agent = User::factory()->agent($agency)->create(['name' => 'Sample Agent', 'email' => 'agent@amsglobal.test']);
        $pending = Agency::factory()->pending()->create(['name' => 'New Horizon Travels', 'city' => 'Lahore']);
        User::factory()->agent($pending)->create(['name' => 'Pending Agent', 'email' => 'pending@amsglobal.test']);

        BankAccount::query()->create(['bank_name' => 'Sample Bank', 'account_title' => 'AMS Global Travel & Tours', 'account_number' => '0000-0000000000', 'iban' => 'PK00SAMP0000000000000000', 'branch' => 'Multan']);

        $umrah = GroupType::query()->where('slug', 'umrah-groups')->firstOrFail();
        $ksa = GroupType::query()->where('slug', 'ksa-one-way')->firstOrFail();
        $saudia = Airline::query()->where('code', 'SV')->firstOrFail();
        $airsial = Airline::query()->where('code', 'PF')->firstOrFail();

        $samples = [
            [$umrah, $saudia, 'MUX-JED-MUX', 12, 21, 26, 175000, [['SV801', 'MUX', 'JED', '16:35', '19:55', '23+7'], ['SV800', 'JED', 'MUX', '08:35', '14:55', '23+23']]],
            [$umrah, $airsial, 'MUX-JED-MUX', 18, 21, 40, 165000, [['PF718', 'MUX', 'JED', '03:10', '06:20', '20+7'], ['PF719', 'JED', 'MUX', '08:00', '14:30', '20+7']]],
            [$ksa, $saudia, 'LHE-RUH', 9, null, 15, 82000, [['SV737', 'LHE', 'RUH', '04:10', '06:50', '23+7']]],
        ];

        $made = [];
        foreach ($samples as [$type, $airline, $sector, $inDays, $duration, $seats, $fare, $flights]) {
            $start = today()->addDays($inDays);
            $legs = [];
            foreach ($flights as $i => [$number, $from, $to, $dep, $arr, $bag]) {
                $day = $i === 0 ? $start : $start->copy()->addDays($duration ?? 0);
                $legs[] = [
                    'flight_number' => $number, 'origin' => $from, 'destination' => $to,
                    'departs_at' => $day->format('Y-m-d')." {$dep}", 'arrives_at' => $day->format('Y-m-d')." {$arr}",
                    'baggage' => $bag, 'meal' => true,
                ];
            }
            $made[] = $groups->save(new FlightGroup, [
                'group_type_id' => $type->id, 'airline_id' => $airline->id, 'sector' => $sector,
                'duration_days' => $duration, 'total_seats' => $seats,
                'adult_fare' => $fare, 'child_fare' => $fare - 15000, 'infant_fare' => 45000,
                'status' => 'published',
            ], $legs, $admin);
        }

        $names = fn (array $people) => array_map(fn (array $p) => [
            'type' => $p[0], 'title' => $p[1], 'given_name' => $p[2], 'surname' => $p[3], 'passport_number' => $p[4] ?? null,
        ], $people);

        $confirmed = $bookings->hold($agent, $made[0], adults: 4, passengers: $names([
            ['adult', 'MR', 'Muhammad Asif', 'Khan', 'AB1234561'], ['adult', 'MRS', 'Nasreen', 'Bibi', 'AB1234562'],
            ['adult', 'MR', 'Abdul Rehman', 'Malik', 'AB1234563'], ['adult', 'MRS', 'Shazia', 'Malik', 'AB1234564'],
        ]));
        $bookings->confirm($confirmed, $admin, 'DEMO01');

        $held = $bookings->hold($agent, $made[1], adults: 2, children: 1, passengers: $names([
            ['adult', 'MR', 'Imran', 'Qureshi', 'CD7654321'], ['adult', 'MRS', 'Sana', 'Qureshi'], ['child', 'MSTR', 'Ahmed', 'Qureshi'],
        ]));

        // A transfer waiting for AMS to check, with a stand-in receipt.
        Storage::disk(PaymentService::RECEIPT_DISK)->put("receipts/{$agency->id}/demo-receipt.svg", '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="400"><rect width="600" height="400" fill="#fbf7ef"/><text x="40" y="80" font-family="Arial" font-size="28" fill="#1d150b">Sample Bank · Funds transfer</text><text x="40" y="150" font-family="Arial" font-size="40" font-weight="bold" fill="#93661a">PKR 490,000</text><text x="40" y="210" font-family="Arial" font-size="20" fill="#5b4a36">To: AMS Global Travel &amp; Tours</text></svg>');
        $payment = new Payment([
            'bank_account_id' => BankAccount::query()->value('id'),
            'booking_id' => $held->id,
            'amount' => $held->total_amount,
            'paid_on' => today()->toDateString(),
            'description' => 'IBFT ref 482913',
            'receipt_path' => "receipts/{$agency->id}/demo-receipt.svg",
        ]);
        $payment->agency_id = $agency->id;
        $payment->user_id = $agent->id;
        $payment->status = PaymentStatus::Pending;
        $payment->save();
    }
}
