<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LienCourt extends Model
{
    protected $table = 'liens_courts';

    protected $fillable = ['code', 'url_cible', 'expire_le'];

    protected $casts = [
        'expire_le' => 'datetime',
        'utilise_le' => 'datetime',
    ];

    public function estExpire(): bool
    {
        return $this->expire_le && $this->expire_le->isPast();
    }
}
