<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;


use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Illuminate\Support\Str;
class PermitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();


        $permits = [];

        for ($i = 0; $i < 500; $i++) {

            // Random year 2020-2025
            $year = $faker->numberBetween(2020, 2025);


            $createdAt = $faker->dateTimeBetween("$year-01-01", "$year-11-06");
            $updatedAt = $faker->dateTimeBetween($createdAt, "$year-12-31");

            // Permit number with year prefix + 5 digits
            $permitNo = 'PERMIT-' . $year . '-' . str_pad($i + 1, 5, '0', STR_PAD_LEFT);

            $permits[] = [
                'id' => Str::uuid(),
                'permit_type' => $faker->randomElement(['Transport', 'Event', 'Business','Construction']),
                'permit_no' => $permitNo,
                'land_owner' => $faker->name,
                'contact_no' => $faker->numerify('0917#######'),
                'location' => $faker->address,
                'area' => $faker->numberBetween(100, 1000) . ' sqm',
                'species' => $faker->word,
                'total_volume' => $faker->numberBetween(10, 500) . ' cubic meters',
                'plate_no' => strtoupper($faker->bothify('???-####')),
                'destination' => $faker->city,
                'expiry_date' => $faker->dateTimeBetween($createdAt, $updatedAt)->format('Y-m-d'),
                'grand_total' => $faker->numberBetween(5000, 500000),
                'remaning_balance' => $faker->numberBetween(0, 50000),
                'issued_date' => $createdAt->format('Y-m-d'),
                'status' => $faker->randomElement(['Pending', 'Expired','Approved','Cancelled']),
                'qrcode' => 'PERMIT-2025-00001.png',
                'lng' => $faker->longitude,
                'lat' => $faker->latitude,
                'created_by' => $faker->randomElement(['ccef24e8-185d-4f34-91f1-8d25da7d1761', 'a0491d0b-cad1-44c7-b6c5-6cf998b163bb']),
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ];
        }

        DB::table('permits')->insert($permits);
    }

}
