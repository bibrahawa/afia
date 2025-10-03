<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
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
     * Grouper les permissions par préfixe (patient, consultation, etc.)
     * Ex: patient.view, patient.create -> groupé sous "patient"
     */
    public static function grouped()
    {
        $permissions = self::all();
        $grouped_permission = [];

        foreach ($permissions as $permission) {
            // Extraire le préfixe avant le point
            $parts = explode('.', $permission->name);
            $prefix = $parts[0] ?? 'other';
            
            $grouped_permission[$prefix][] = $permission;
        }
        
        return $grouped_permission;
    }
}