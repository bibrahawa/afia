<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paiement extends Model
{
    // use HasFactory;

    protected $fillable = [
        'user_id', 'patient_id','transaction_id',
        'source', 'paiement_no', 'description','type',
        'montant'
    ];

    protected static function booted()
    {
        static::creating(function ($paiement) {
            $annee = now()->year;
            $last = self::whereYear('created_at', $annee)->latest('id')->first();
            $number = $last ? (int)substr($last->paiement_no, -5) + 1 : 1;
            $paiement->paiement_no = 'P-' . $annee . str_pad($number, 5, '0', STR_PAD_LEFT);
        });
    }

    public function transaction() { return $this->belongsTo(Transaction::class); }
    public function patient() { return $this->belongsTo(Patient::class); }
    public function utilisateur() { return $this->belongsTo(User::class, 'user_id'); }
}
