<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $guarded = [];

    /**
     * Setup variable mass assignment.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'guard_name',
    ];

    /**
     * Dropdown pour les formulaires
     */
    public static function dropdown()
    {
        return [null => '--Choisir un rôle--'] + self::all()->pluck('name', 'id')->toArray();
    }
}