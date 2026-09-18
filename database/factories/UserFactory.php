<?php

namespace Database\Factories;

use App\Models\Etablissement;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * Un compte du personnel.
     *
     * `phone` est obligatoire en base (c'est l'identifiant de connexion le plus
     * utilisé en Guinée) : sans lui, toute fabrique d'utilisateur échouait.
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => (string) fake()->unique()->numberBetween(600000000, 699999999),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => ['email_verified_at' => null]);
    }

    /**
     * Rattache le compte à un établissement — hors $fillable, donc posé après
     * création, comme le fait l'application.
     */
    public function pour(Etablissement|int|null $etablissement = null): static
    {
        return $this->afterCreating(function ($user) use ($etablissement) {
            $id = $etablissement instanceof Etablissement ? $etablissement->id : $etablissement;

            $id ??= Etablissement::query()->value('id') ?? Etablissement::create([
                'nom' => 'Établissement de test',
                'slug' => 'etablissement-test-' . Str::lower(Str::random(6)),
                'type' => 'clinique',
                'statut' => 'actif',
            ])->id;

            $user->forceFill(['etablissement_id' => $id])->save();
        });
    }
}
