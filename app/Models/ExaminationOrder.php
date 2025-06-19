<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExaminationOrder extends Model
{
    protected $fillable = [
        'consultation_id',
        'examin_name',
        'examin_lists',
        'clinical_notes'
    ];

    protected $casts = [
        'examin_lists' => 'array'
    ];

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

}
