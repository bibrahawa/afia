<?php

namespace App\Http\Controllers\Parcours;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\Parcours\StatistiquesParcoursService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StatistiqueController extends Controller
{
    public function index(Request $request, StatistiquesParcoursService $statistiques)
    {
        $filtres = $request->validate([
            'depuis' => ['nullable', 'date'],
            'jusqu_a' => ['nullable', 'date', 'after_or_equal:depuis'],
            'medecin_id' => ['nullable', 'exists_etablissement:employees,id'],
        ]);

        $debut = Carbon::parse($filtres['depuis'] ?? today()->startOfMonth()->toDateString());
        $fin = Carbon::parse($filtres['jusqu_a'] ?? today()->toDateString());

        return view('parcours.statistiques.index', [
            'stats' => $statistiques->resume($debut, $fin, $filtres['medecin_id'] ?? null),
            'medecins' => Employee::where('type', 'Doctor')->orderBy('first_name')->get(),
            'filtres' => ['depuis' => $debut->toDateString(), 'jusqu_a' => $fin->toDateString(), 'medecin_id' => $filtres['medecin_id'] ?? null],
        ]);
    }
}
