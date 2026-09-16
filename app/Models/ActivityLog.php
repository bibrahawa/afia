<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false; // append-only : created_at géré par useCurrent() en migration

    protected $fillable = [
        'etablissement_id', 'causer_type', 'causer_id',
        'subject_type', 'subject_id', 'action', 'description',
        'proprietes', 'ip_address',
    ];

    protected $casts = ['proprietes' => 'array'];

    public function causer()
    {
        return $this->morphTo();
    }

    public function subject()
    {
        return $this->morphTo();
    }
}
