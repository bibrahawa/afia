<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\Auth;

trait InteractsWithAuthenticatedEmployee
{
    protected function authenticatedEmployeeId(): int
    {
        $employeeId = Auth::user()?->employee?->id;

        abort_unless($employeeId, 403, 'Employé non associé à cet utilisateur.');

        return $employeeId;
    }
}