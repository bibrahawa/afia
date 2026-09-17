<?php

namespace App\Http\Controllers\Labo;

use App\Http\Controllers\Controller;
use App\Enums\Labo\StatutDemande;
use App\Enums\Labo\StatutEchantillon;
use App\Enums\Labo\StatutExamen;
use App\Models\Labo\LaboDemande;
use App\Models\Labo\LaboDemandeExamen;
use App\Models\Labo\LaboEchantillon;
use App\Models\Labo\LaboExamen;
use App\Models\Labo\LaboResultat;
use Illuminate\Support\Facades\DB;

class TableauBordController extends Controller
{
    public function index()
    {
        $aujourdhui = today();

        $compteurs = [
            'demandes_du_jour' => LaboDemande::whereDate('created_at', $aujourdhui)->where('statut', '!=', StatutDemande::ANNULEE->value)->count(),
            'a_prelever' => LaboEchantillon::where('statut', StatutEchantillon::ATTENDU->value)->whereHas('demande', fn ($q) => $q->where('statut', '!=', StatutDemande::ANNULEE->value))->count(),
            'en_analyse' => LaboDemandeExamen::whereIn('statut', [StatutExamen::RECU->value, StatutExamen::EN_COURS->value])->count(),
            'a_valider' => LaboDemandeExamen::where('statut', StatutExamen::VALIDE_TECHNIQUE->value)->count(),
            'a_publier' => LaboDemandeExamen::where('statut', StatutExamen::VALIDE_BIOLOGIQUE->value)->count(),
            'mdo_a_declarer' => \App\Models\Labo\LaboDeclarationMdo::where('statut', \App\Models\Labo\LaboDeclarationMdo::A_DECLARER)->count(),
            'urgences' => LaboDemande::where('urgence', true)->whereNotIn('statut', [StatutDemande::PUBLIEE->value, StatutDemande::ANNULEE->value])->count(),
        ];

        $critiquesNonSignales = LaboResultat::with('demandeExamen.demande.patient')
            ->whereIn('flag', ['LL', 'HH'])
            ->whereDoesntHave('alertes', fn ($q) => $q->whereColumn('labo_alertes_critiques.signale_le', '>=', 'labo_resultats.saisi_le'))
            ->whereHas('demandeExamen', fn ($q) => $q->whereNotIn('statut', [StatutExamen::ANNULE->value]))
            ->latest('saisi_le')->limit(10)->get();

        // Examens en retard par rapport au délai de rendu du catalogue (TAT).
        $enRetard = LaboDemandeExamen::with(['demande.patient', 'examen', 'echantillons'])
            ->whereIn('statut', [StatutExamen::RECU->value, StatutExamen::EN_COURS->value, StatutExamen::VALIDE_TECHNIQUE->value])
            ->get()
            ->filter(fn ($l) => $l->echeance()?->isPast())
            ->sortBy(fn ($l) => $l->echeance())
            ->take(10);

        $delaiMoyenHeures = LaboDemandeExamen::where('labo_demande_examens.statut', StatutExamen::PUBLIE->value)
            ->where('labo_demande_examens.publie_le', '>=', now()->subDays(30))
            ->join('labo_demandes', 'labo_demandes.id', '=', 'labo_demande_examens.demande_id')
            ->avg(DB::raw('TIMESTAMPDIFF(MINUTE, labo_demandes.created_at, labo_demande_examens.publie_le)'));

        $topExamens = LaboDemandeExamen::select('examen_id', DB::raw('COUNT(*) as total'))
            ->where('statut', '!=', StatutExamen::ANNULE->value)
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('examen_id')->orderByDesc('total')->limit(8)->get()
            ->map(fn ($r) => ['nom' => LaboExamen::find($r->examen_id)?->nom, 'total' => $r->total]);

        return view('labo.tableau-bord', [
            'compteurs' => $compteurs,
            'critiquesNonSignales' => $critiquesNonSignales,
            'enRetard' => $enRetard,
            'delaiMoyenHeures' => $delaiMoyenHeures ? round($delaiMoyenHeures / 60, 1) : null,
            'topExamens' => $topExamens,
            'catalogueVide' => ! LaboExamen::exists(),
        ]);
    }
}
