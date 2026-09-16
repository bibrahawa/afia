<?php

namespace App\Models;

use App\Enums\TypeRelationFamiliale;
use Illuminate\Database\Eloquent\Model;

class RelationFamiliale extends Model
{
    protected $fillable = ['patient_id', 'personne_liee_id', 'type_relation', 'verifie_le'];

    protected $table = 'relations_familiales';

    protected $casts = [
        'type_relation' => TypeRelationFamiliale::class,
        'verifie_le' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function personneLiee()
    {
        return $this->belongsTo(Patient::class, 'personne_liee_id');
    }
}
