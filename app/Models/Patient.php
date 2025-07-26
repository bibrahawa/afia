<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{


	protected $fillable =
	[
        'first_name', 'middle_name', 'last_name', 'age', 'gender', 'birth_date', 'country', 'state', 'district' , 'location' , 'occupation' ,
        'description' , 'relative_name' , 'relative_phone' , 'marital_status', 'blood_group','user_id'
    ];

    public function appointments()
    {
        return $this->hasMany('App\Models\Appointment');
    }

    public function invoices()
    {
        return $this->hasMany('App\Models\Invoice');
    }

    public function reports()
    {
        return $this->hasMany('App\Models\Report');
    }

    public function packageSales()
    {
        return $this->hasMany('App\Models\PackageSale');
    }

    public function consultations()
    {
        return $this->hasMany('App\Models\Consultation');
    }

    public function paiements(){
        return $this->hasMany('App\Models\Paiement');
    }

    public function account()
    {
        return $this->morphOne(Account::class, 'owner');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function files()
    {
        return $this->hasMany(FichierPatient::class);
    }

    public function antecedant()
    {
        return $this->hasOne(Antecedent::class);
    }

    public function hospitalisations()
    {
        return $this->hasMany(Hospitalisation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
