<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'status',
        'password',
        // CORRIGÉ : absents d'ici, ces champs étaient ignorés en silence par
        // update() à la connexion — le compteur d'échecs ne se remettait
        // jamais à zéro et le verrouillage ne s'enregistrait jamais.
        'last_login_at',
        'login_attempts',
        'locked_until',
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

    // Relations
    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function patient()
    {
        return $this->hasOne(Patient::class);
    }

    /**
     * Les comptes utilisateurs ne sont pas filtrés automatiquement (la
     * connexion doit pouvoir trouver n'importe quel compte). Toute gestion
     * des utilisateurs côté établissement passe par ce scope.
     */
    public function scopeDeMonEtablissement($query)
    {
        $id = \App\Support\EtablissementContext::id();

        if ($id) {
            return $query->where('users.etablissement_id', $id);
        }

        return \App\Support\EtablissementContext::estAdministrateurPlateforme() ? $query : $query->whereRaw('1 = 0');
    }
}