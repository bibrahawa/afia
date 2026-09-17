<?php

namespace App\Http\Controllers\Parcours;

use App\Http\Controllers\Controller;
use App\Models\Parcours\Grossesse;
use App\Models\Patient;
use App\Services\Parcours\GrossesseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GrossesseController extends Controller
{
    public function __construct(private GrossesseService $grossesses)
    {
    }

    public function index()
    {
        $suivis = Grossesse::where('statut', Grossesse::EN_COURS)
            ->with(['patient', 'medecin'])
            ->orderBy('dpa')
            ->get();

        return view('parcours.grossesses.index', ['grossesses' => $suivis]);
    }

    public function show(Grossesse $grossesse)
    {
        $grossesse->load(['patient', 'medecin', 'consultations.medecin']);

        return view('parcours.grossesses.show', [
            'grossesse' => $grossesse,
            'calendrier' => $grossesse->calendrier(),
            'mesures' => $this->grossesses->mesures($grossesse),
        ]);
    }

    public function store(Request $request, int $patientId)
    {
        $donnees = $request->validate([
            'ddr' => ['required', 'date', 'before_or_equal:today'],
            'gestite' => ['nullable', 'integer', 'min:1', 'max:20'],
            'parite' => ['nullable', 'integer', 'min:0', 'max:20'],
            'medecin_id' => ['nullable', 'exists_etablissement:employees,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], ['ddr.required' => 'La date des dernières règles est obligatoire.']);

        $patient = Patient::suivisParEtablissement()->findOrFail($patientId);
        $grossesse = $this->grossesses->ouvrir($patient, Carbon::parse($donnees['ddr']), $donnees, $request->user());

        return redirect()->route('parcours.grossesses.show', $grossesse)
            ->with('success', 'Suivi ouvert. Terme : ' . $grossesse->termeLisible() . ', accouchement prévu le ' . $grossesse->dpa->format('d/m/Y') . '.');
    }

    public function corrigerDdr(Request $request, Grossesse $grossesse)
    {
        $donnees = $request->validate(['ddr' => ['required', 'date', 'before_or_equal:today']]);

        $this->grossesses->corrigerDdr($grossesse, Carbon::parse($donnees['ddr']));

        return back()->with('success', 'Date corrigée : terme et calendrier des CPN recalculés.');
    }

    public function cloturer(Request $request, Grossesse $grossesse)
    {
        $donnees = $request->validate([
            'issue' => ['required', Rule::in(array_keys(Grossesse::ISSUES))],
            'date_issue' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->grossesses->cloturer($grossesse, $donnees['issue'], Carbon::parse($donnees['date_issue']), $donnees['notes'] ?? null);

        return back()->with('success', 'Suivi clôturé.');
    }
}
