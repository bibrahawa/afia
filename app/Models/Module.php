<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = ['code', 'nom', 'description'];

    public function etablissements()
    {
        return $this->belongsToMany(Etablissement::class)
            ->withPivot(['est_actif', 'active_depuis', 'desactive_le'])
            ->withTimestamps();
    }
}
