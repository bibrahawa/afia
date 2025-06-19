<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'status',
        'password'
        // 'role_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // public function scopeActive($query)
    // {
    //     return $query->where('active', 1);
    // }

    public function role()
    {
        return $this->belongsTo('App\Models\Role');
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }


    // public function permissions(){
    //     return $this->belongsToMany('App\Models\Permission', 'permission_role');
    // }

    // public function hasPermission($permissionName)
    // {
    //     // Votre logique de vérification de permission
    //     return $this->permissions()->where('name', $permissionName)->exists() ||
    //            $this->roles->flatMap(function ($role) {
    //                return $role->permissions;
    //            })->where('name', $permissionName)->isNotEmpty();
    // }

}
