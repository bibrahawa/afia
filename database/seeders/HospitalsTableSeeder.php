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
            'name' => 'Clinique Aprosafe',
            'slogan' => 'Excelling Incase...',
            'logo' => 'assets/logo/logo.png',
            'address' => 'Kiroti, Conakry, Republique de Guinée',
            'contact' => '+224 628 16 44 22',
            'email' => 'infos@cliniqueaprosafe.com',
            'pan_no' => '123',
            'registration_no' => '12345',
            'website' => 'cliniqueaprosafe.com',
            'description' => "La Clinique Aprosafe est un établissement médical dédié à prendre soin de votre santé, en mettant l'accent sur la gynécologie, la santé maternelle et la planification familiale. Notre équipe de professionnels de la santé qualifiés et bienveillants est là pour vous accompagner à chaque étape de votre parcours de santé. À la Clinique Aprosafe, notre engagement envers votre bien-être va au-delà du traitement médical. Nous nous efforçons de créer un environnement accueillant et confortable où vous pouvez vous sentir en confiance pour partager vos préoccupations de santé",
            'tax_type' => 'Health Tax',
            'tax_percent' => 0,
            'invoice_prefix'=>'AP-',
            'patient_prefix' => 'PA-',
            'invoice_message'=> 'Invoice',
        ]);

    }
}
