<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class HospitalsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	 DB::table('hospitals')->insert([
            'name' => 'Clinic',
            'slogan' => 'Excelling Incase...',
            'logo' => 'uploads/logo.png',
            'address' => 'Lambanyi, Conakry',
            'contact' => '+224 622 09 96 72',
            'email' => 'bibrah@gmail.com',
            'pan_no' => '123',
            'registration_no' => '12345',
            'website' => 'aprosafe.com',
            'description' => 'Our moto healty life.',
            'tax_type' => 'Health Tax',
            'tax_percent' => 5,
            'invoice_prefix'=>'AP-',
            'patient_prefix' => 'AP-',
            'invoice_message'=> 'Invoice',
        ]);

    }
}
