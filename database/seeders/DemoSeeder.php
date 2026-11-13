<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Airline;
use App\Models\BankAccount;
use App\Models\FlightGroup;
use App\Models\GroupType;
use App\Models\User;
use App\Services\BookingService;
use App\Services\FlightGroupService;
use Illuminate\Database\Seeder;

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

        $confirmed = $bookings->hold($agent, $made[0], adults: 4);
        $bookings->confirm($confirmed, $admin, 'DEMO01');
        $bookings->hold($agent, $made[1], adults: 2, children: 1);
    }
}
