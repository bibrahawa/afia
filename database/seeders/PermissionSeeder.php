<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Route;

class PermissionSeeder extends Seeder
{
    /**
     * Seed permissions from defined named routes.
     *
     * @return void
     */
    public function run()
    {
        $count = 0;

        $role = Role::where('name', 'admin')->first();

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            // Vérifier si la route a un nom et est protégée par l'auth middleware
            if (!empty($name)) {

                Permission::create(['name' => $name]);
                $role->givePermissionTo($name);

                $count++;
            }
        }

        $this->command->info("$count permissions ont été créées avec succès.");
    }

    /**
      * Vérifie si une route utilise le middleware d'authentification.
      *
      * @param mixed $middleware Le middleware ou tableau de middlewares
      * @return bool
      */

}
