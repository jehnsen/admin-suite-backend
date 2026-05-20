<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        $holidays = [
            // ---------------------------------------------------------------
            // 2025 Philippine Public Holidays
            // ---------------------------------------------------------------

            // Regular Holidays
            ['holiday_date' => '2025-01-01', 'holiday_name' => "New Year's Day",                      'type' => 'Regular'],
            ['holiday_date' => '2025-04-09', 'holiday_name' => 'Araw ng Kagitingan (Day of Valor)',   'type' => 'Regular'],
            ['holiday_date' => '2025-04-17', 'holiday_name' => 'Maundy Thursday',                     'type' => 'Regular'],
            ['holiday_date' => '2025-04-18', 'holiday_name' => 'Good Friday',                         'type' => 'Regular'],
            ['holiday_date' => '2025-05-01', 'holiday_name' => 'Labor Day',                           'type' => 'Regular'],
            ['holiday_date' => '2025-06-12', 'holiday_name' => 'Independence Day',                    'type' => 'Regular'],
            ['holiday_date' => '2025-08-25', 'holiday_name' => 'National Heroes Day',                 'type' => 'Regular'],
            ['holiday_date' => '2025-11-30', 'holiday_name' => 'Bonifacio Day',                       'type' => 'Regular'],
            ['holiday_date' => '2025-12-25', 'holiday_name' => 'Christmas Day',                       'type' => 'Regular'],
            ['holiday_date' => '2025-12-30', 'holiday_name' => 'Rizal Day',                           'type' => 'Regular'],

            // Special Non-Working Holidays
            ['holiday_date' => '2025-01-29', 'holiday_name' => 'Chinese New Year',                    'type' => 'Special'],
            ['holiday_date' => '2025-02-25', 'holiday_name' => 'EDSA People Power Revolution Anniversary', 'type' => 'Special'],
            ['holiday_date' => '2025-04-19', 'holiday_name' => 'Black Saturday',                      'type' => 'Special'],
            ['holiday_date' => '2025-08-21', 'holiday_name' => 'Ninoy Aquino Day',                    'type' => 'Special'],
            ['holiday_date' => '2025-11-01', 'holiday_name' => "All Saints' Day",                     'type' => 'Special'],
            ['holiday_date' => '2025-11-02', 'holiday_name' => "All Souls' Day",                      'type' => 'Special'],
            ['holiday_date' => '2025-12-08', 'holiday_name' => 'Feast of the Immaculate Conception',  'type' => 'Special'],
            ['holiday_date' => '2025-12-24', 'holiday_name' => 'Christmas Eve',                       'type' => 'Special'],
            ['holiday_date' => '2025-12-31', 'holiday_name' => "New Year's Eve",                      'type' => 'Special'],

            // ---------------------------------------------------------------
            // 2026 Philippine Public Holidays
            // ---------------------------------------------------------------

            // Regular Holidays
            ['holiday_date' => '2026-01-01', 'holiday_name' => "New Year's Day",                      'type' => 'Regular'],
            ['holiday_date' => '2026-04-02', 'holiday_name' => 'Maundy Thursday',                     'type' => 'Regular'],
            ['holiday_date' => '2026-04-03', 'holiday_name' => 'Good Friday',                         'type' => 'Regular'],
            ['holiday_date' => '2026-04-09', 'holiday_name' => 'Araw ng Kagitingan (Day of Valor)',   'type' => 'Regular'],
            ['holiday_date' => '2026-05-01', 'holiday_name' => 'Labor Day',                           'type' => 'Regular'],
            ['holiday_date' => '2026-06-12', 'holiday_name' => 'Independence Day',                    'type' => 'Regular'],
            ['holiday_date' => '2026-08-31', 'holiday_name' => 'National Heroes Day',                 'type' => 'Regular'],
            ['holiday_date' => '2026-11-30', 'holiday_name' => 'Bonifacio Day',                       'type' => 'Regular'],
            ['holiday_date' => '2026-12-25', 'holiday_name' => 'Christmas Day',                       'type' => 'Regular'],
            ['holiday_date' => '2026-12-30', 'holiday_name' => 'Rizal Day',                           'type' => 'Regular'],

            // Special Non-Working Holidays
            ['holiday_date' => '2026-02-17', 'holiday_name' => 'Chinese New Year',                    'type' => 'Special'],
            ['holiday_date' => '2026-02-25', 'holiday_name' => 'EDSA People Power Revolution Anniversary', 'type' => 'Special'],
            ['holiday_date' => '2026-04-04', 'holiday_name' => 'Black Saturday',                      'type' => 'Special'],
            ['holiday_date' => '2026-08-21', 'holiday_name' => 'Ninoy Aquino Day',                    'type' => 'Special'],
            ['holiday_date' => '2026-11-01', 'holiday_name' => "All Saints' Day",                     'type' => 'Special'],
            ['holiday_date' => '2026-11-02', 'holiday_name' => "All Souls' Day",                      'type' => 'Special'],
            ['holiday_date' => '2026-12-08', 'holiday_name' => 'Feast of the Immaculate Conception',  'type' => 'Special'],
            ['holiday_date' => '2026-12-24', 'holiday_name' => 'Christmas Eve',                       'type' => 'Special'],
            ['holiday_date' => '2026-12-31', 'holiday_name' => "New Year's Eve",                      'type' => 'Special'],
        ];

        foreach ($holidays as $holiday) {
            Holiday::firstOrCreate(
                ['holiday_date' => $holiday['holiday_date']],
                ['holiday_name' => $holiday['holiday_name'], 'type' => $holiday['type']]
            );
        }
    }
}
