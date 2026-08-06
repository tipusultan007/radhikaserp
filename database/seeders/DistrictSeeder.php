<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DistrictSeeder extends Seeder
{
    public function run(): void
    {
        $districts = [
            'Cumilla', 'Feni', 'Brahmanbaria', 'Rangamati', 'Noakhali', 'Chandpur',
            'Lakshmipur', 'Chattogram', 'Coxsbazar', 'Khagrachhari', 'Bandarban',
            'Sirajganj', 'Pabna', 'Bogura', 'Rajshahi', 'Natore', 'Joypurhat',
            'Chapainawabganj', 'Naogaon', 'Jashore', 'Satkhira', 'Meherpur',
            'Narail', 'Chuadanga', 'Kushtia', 'Magura', 'Khulna', 'Bagerhat',
            'Jhenaidah', 'Jhalakathi', 'Patuakhali', 'Pirojpur', 'Barisal',
            'Bhola', 'Barguna', 'Sylhet', 'Moulvibazar', 'Habiganj', 'Sunamganj',
            'Narsingdi', 'Gazipur', 'Shariatpur', 'Narayanganj', 'Tangail',
            'Kishoreganj', 'Manikganj', 'Dhaka', 'Munshiganj', 'Rajbari',
            'Madaripur', 'Gopalganj', 'Faridpur', 'Panchagarh', 'Dinajpur',
            'Lalmonirhat', 'Nilphamari', 'Gaibandha', 'Thakurgaon', 'Rangpur',
            'Kurigram', 'Sherpur', 'Mymensingh', 'Jamalpur', 'Netrokona',
        ];

        foreach ($districts as $district) {
            DB::table('districts')->insertOrIgnore([
                'name' => $district,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
