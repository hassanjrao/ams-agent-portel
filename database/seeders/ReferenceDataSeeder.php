<?php

namespace Database\Seeders;

use App\Models\Airline;
use App\Models\GroupType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Group categories and the airlines AMS usually buys seats on. Safe to re-run: matches by slug/code.
 */
class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $types = ['Umrah Groups', 'KSA One Way', 'UAE One Way', 'Muscat One Way', 'Bahrain One Way', 'UK One Way'];
        foreach ($types as $i => $name) {
            GroupType::query()->updateOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'sort_order' => $i]);
        }

        $airlines = [
            'SV' => 'Saudia',
            'PF' => 'AirSial',
            'PK' => 'PIA',
            'PA' => 'Airblue',
            '9P' => 'Fly Jinnah',
            'OV' => 'SalamAir',
            'G9' => 'Air Arabia',
            'FZ' => 'flydubai',
            'XY' => 'flynas',
            'F3' => 'flyadeal',
            'QR' => 'Qatar Airways',
            'EK' => 'Emirates',
        ];
        foreach ($airlines as $code => $name) {
            Airline::query()->updateOrCreate(['code' => $code], ['name' => $name]);
        }
    }
}
