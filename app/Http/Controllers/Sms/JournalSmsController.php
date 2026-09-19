<?php

namespace App\Http\Controllers\Sms;

use App\Http\Controllers\Controller;
use App\Models\SmsJournal;
use App\Services\SmsService;
use App\Support\EtablissementContext;
use Illuminate\Http\Request;

/**
 * Journal des SMS de la clinique : ce qui est parti, ce qui a échoué, et le
 * renvoi en un clic (ex. un patient qui dit ne pas avoir reçu son rappel).
 * Cloisonné par établissement (trait BelongsToEtablissement sur SmsJournal).
 */
class JournalSmsController extends Controller
{
    public const PERIODES = ['jour' => "Aujourd'hui", '7j' => '7 derniers jours', '30j' => '30 derniers jours', 'tout' => 'Tout'];

    public function index(Request $request, SmsService $sms)
    {
        $periode = array_key_exists($request->query('periode'), self::PERIODES) ? $request->query('periode') : '7j';
        $statut = in_array($request->query('statut'), [SmsJournal::ENVOYE, SmsJournal::ECHEC], true) ? $request->query('statut') : null;
        $type = array_key_exists((string) $request->query('type'), SmsJournal::TYPES) ? $request->query('type') : null;
        $recherche = preg_replace('/\D/', '', (string) $request->query('q'));

        $base = SmsJournal::query()
            ->when($periode === 'jour', fn ($q) => $q->where('created_at', '>=', today()))
            ->when($periode === '7j', fn ($q) => $q->where('created_at', '>=', now()->subDays(7)))
            ->when($periode === '30j', fn ($q) => $q->where('created_at', '>=', now()->subDays(30)));

        $totaux = (clone $base)->selectRaw('statut, COUNT(*) as n')->groupBy('statut')->pluck('n', 'statut');

        $envois = (clone $base)
            ->when($statut, fn ($q) => $q->where('statut', $statut))
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when(strlen($recherche) >= 3, fn ($q) => $q->where('telephone', 'like', '%' . $recherche . '%'))
            ->with(['sujet', 'auteur'])
            ->latest()
            ->paginate(40)
            ->withQueryString();

        $typesPresents = (clone $base)->distinct()->pluck('type');
        $expediteur = $sms->expediteurPour(EtablissementContext::current());

        return view('sms.journal', compact('envois', 'totaux', 'periode', 'statut', 'type', 'recherche', 'typesPresents', 'expediteur'));
    }

    public function renvoyer(Request $request, SmsJournal $journal, SmsService $sms)
    {
        $resultat = $sms->renvoyer($journal, $request->user()?->id);

        return back()->with(
            ($resultat['success'] ?? false) ? 'success' : 'error',
            ($resultat['success'] ?? false) ? 'SMS renvoyé au ' . $journal->telephone . '.' : ($resultat['error'] ?? 'Renvoi impossible.')
        );
    }
}
