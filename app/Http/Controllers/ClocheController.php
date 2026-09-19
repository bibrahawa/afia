<?php

namespace App\Http\Controllers;

use App\Services\Cloche\ClocheService;
use Illuminate\Http\Request;

/** Lot S4 — Rafraîchissement de la cloche sans recharger la page. */
class ClocheController extends Controller
{
    public function __invoke(Request $request, ClocheService $cloche)
    {
        $alertes = $cloche->alertes($request->user());

        return response()->json([
            'total' => $alertes->count(),
            'danger' => $alertes->where('niveau', 'danger')->count(),
            'html' => view('layouts.partials.cloche-liste', ['alertes' => $alertes])->render(),
        ])->header('Cache-Control', 'no-store');
    }
}
